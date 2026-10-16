<?php

namespace ByJG\Serializer;

use BackedEnum;
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use stdClass;

/**
 * Final class for copying properties between objects
 */
final class ObjectCopy
{
    /**
     * Static cache for target class property names (class => [lowercase => original]).
     * Persisted across copy() calls to avoid repeated get_class_vars() + strtolower().
     *
     * @var array<string, array<string, string>>
     */
    private static array $propNameLowerCache = [];

    /**
     * Static cache of the backed enum a member takes ("Class::setName" for a setter's parameter,
     * "Class::$name" for a property), or null when it does not take one.
     *
     * @var array<string, class-string<BackedEnum>|null>
     */
    private static array $backedEnumCache = [];

    /**
     * Copy the properties from a source object to the properties matching to a target object
     *
     * @param object|array $source The source object
     * @param object|array $target The target object
     * @param PropertyHandlerInterface|null $propertyHandler The property handler
     * @return void
     */
    public static function copy(object|array $source, object|array &$target, ?PropertyHandlerInterface $propertyHandler = null): void
    {
        if (is_null($propertyHandler) && is_array($source)) {
            self::copyDirect($source, $target);
            return;
        }

        Serialize::from($source)
            ->withStopAtFirstLevel()
            ->parseAttributes(
                function ($attribute, $value, $keyName, $propertyName) use ($propertyHandler, &$target, $source) {
                    self::applyAttribute($attribute, $value, $keyName, $propertyName, $propertyHandler, $target, $source);
                }
            );
    }

    /**
     * Fast copy path for the common case: an array source and no property handler.
     * Uses direct iteration instead of the full Serialize pipeline, which for an array
     * source with withStopAtFirstLevel() produces exactly the same assignments.
     *
     * @param array $source The source array
     * @param object|array $target The target object
     * @return void
     */
    private static function copyDirect(array $source, object|array &$target): void
    {
        foreach ($source as $propertyName => $value) {
            if (is_array($target)) {
                $target[$propertyName] = $value;
                continue;
            }

            self::applyToTarget((string)$propertyName, $value, $target);
        }
    }

    /**
     * @param mixed $attribute
     * @param mixed $value
     * @param mixed $keyName
     * @param string $propertyName
     * @param PropertyHandlerInterface|null $propertyHandler
     * @param object|array $target
     * @param object|array $source
     * @return void
     */
    private static function applyAttribute(mixed $attribute, mixed $value, mixed $keyName, string $propertyName, ?PropertyHandlerInterface $propertyHandler, object|array &$target, object|array $source): void
    {
        // ----------------------------------------------
        // Extract the target name
        $targetName = $propertyName;
        if (!is_null($propertyHandler)) {
            $targetName = $propertyHandler->mapName($propertyName);
            // Pass the full source instance to allow a property handler to access other properties
            $value = $propertyHandler->transformValue($propertyName, $targetName, $value, $source);
        }

        if (is_array($target)) {
            $target[$targetName] = $value;
            return;
        }

        // ----------------------------------------------
        // Set the value to the target
        self::applyToTarget($targetName, $value, $target);
    }

    /**
     * Assign a single value to an object target, using the setter when available and
     * falling back to a case-insensitive property match.
     *
     * @param string $targetName
     * @param mixed $value
     * @param object $target
     * @return void
     */
    private static function applyToTarget(string $targetName, mixed $value, object $target): void
    {
        if (method_exists($target, 'set' . $targetName)) {
            $target->{'set' . $targetName}(self::toBackedEnum($target, 'set' . $targetName, true, $value));
            return;
        }

        if ($target instanceof stdClass) {
            $target->{$targetName} = $value;
            return;
        }

        if (isset($target->{$targetName})) {
            $target->{$targetName} = self::toBackedEnum($target, $targetName, false, $value);
            return;
        }

        // Check if source property have property case name different from target
        $propNameLower = self::propNameLower(get_class($target));

        $propLower = strtolower($targetName);
        if (isset($propNameLower[$propLower])) {
            $target->{$propNameLower[$propLower]} = self::toBackedEnum($target, $propNameLower[$propLower], false, $value);
        }
    }

    /**
     * The value as the backed enum the member takes, when it takes one and the value is the
     * enum's int or string: a database row holds "PAID", the property is typed ?PaymentState.
     * An unknown value is a ValueError from BackedEnum::from(). Anything else is returned as it is.
     *
     * @param string $member A setter name, or a property name
     */
    private static function toBackedEnum(object $target, string $member, bool $isSetter, mixed $value): mixed
    {
        if (!is_int($value) && !is_string($value)) {
            return $value;
        }

        $key = get_class($target) . ($isSetter ? '::' : '::$') . $member;
        if (!array_key_exists($key, self::$backedEnumCache)) {
            $type = null;
            if ($isSetter) {
                $type = ((new ReflectionMethod($target, $member))->getParameters()[0] ?? null)?->getType();
            } elseif (property_exists($target, $member)) {
                $type = (new ReflectionProperty($target, $member))->getType();
            }
            self::$backedEnumCache[$key] = self::backedEnumOf($type);
        }

        $enum = self::$backedEnumCache[$key];
        return $enum === null ? $value : $enum::from($value);
    }

    /**
     * The backed enum of a type (S, ?S), or null when the type has none, or also accepts a
     * scalar (string|S, mixed) and so can take the value as it is.
     *
     * @return class-string<BackedEnum>|null
     */
    private static function backedEnumOf(?ReflectionType $type): ?string
    {
        $types = match (true) {
            $type instanceof ReflectionNamedType => [$type],
            $type instanceof ReflectionUnionType => $type->getTypes(),
            default => [],
        };

        $enum = null;
        foreach ($types as $namedType) {
            if (!$namedType instanceof ReflectionNamedType) {
                return null;
            }
            $name = $namedType->getName();
            if ($namedType->isBuiltin()) {
                if ($name !== 'null') {
                    return null;
                }
                continue;
            }
            if (is_subclass_of($name, BackedEnum::class)) {
                $enum = $name;
            }
        }
        return $enum;
    }

    /**
     * Map of lowercase property name => declared property name for a class.
     *
     * @param string $className
     * @return array<string, string>
     */
    private static function propNameLower(string $className): array
    {
        if (!isset(self::$propNameLowerCache[$className])) {
            self::$propNameLowerCache[$className] = [];

            foreach (get_class_vars($className) as $varKey => $varValue) {
                self::$propNameLowerCache[$className][strtolower($varKey)] = $varKey;
            }
        }

        return self::$propNameLowerCache[$className];
    }
}
