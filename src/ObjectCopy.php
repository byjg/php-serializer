<?php

namespace ByJG\Serializer;

use BackedEnum;
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use ReflectionClass;
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
     * Static cache of how a value is written into a class, by target name: the member (a setter or a public
     * property), whether it is a setter, and the backed enum it takes (or null); false when the class has
     * neither, and only the instance can have it (a dynamic or magic property).
     *
     * @var array<string, array<string, array{0: string, 1: bool, 2: class-string<BackedEnum>|null}|false>>
     */
    private static array $writerCache = [];

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
        if (is_array($source)) {
            self::copyArray($source, $target, $propertyHandler);
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
     * Fast copy path for an array source (a database row, a request body...), with or without a property
     * handler. Uses direct iteration instead of the full Serialize pipeline, which for an array source with
     * withStopAtFirstLevel() makes exactly the same calls: each key in order, a backed enum as its value (and
     * applyToTarget() builds the enum again for a member typed with it), then applyAttribute() with the key as
     * the property name and the whole source array.
     *
     * @param array $source The source array
     * @param object|array $target The target object
     * @param PropertyHandlerInterface|null $propertyHandler The property handler
     * @return void
     */
    private static function copyArray(array $source, object|array &$target, ?PropertyHandlerInterface $propertyHandler): void
    {
        foreach ($source as $key => $value) {
            if ($value instanceof BackedEnum) {
                $value = $value->value;
            }

            self::applyAttribute(null, $value, $key, (string)$key, $propertyHandler, $target, $source);
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
     * Assign a single value to an object target: with the setter when there is one (in any case), else the
     * public property (exact case, else any case), else a property the instance has (dynamic, or magic
     * __isset()/__set()). A value for a member typed with a backed enum is made that enum.
     *
     * How the class takes each name is resolved once (see writerOf()); only the last case depends on the instance.
     *
     * @param string $targetName
     * @param mixed $value
     * @param object $target
     * @return void
     */
    private static function applyToTarget(string $targetName, mixed $value, object $target): void
    {
        if ($target instanceof stdClass) {
            $target->{$targetName} = $value;
            return;
        }

        $writer = self::writerFor(get_class($target), $targetName);

        if ($writer === false) {
            if (isset($target->{$targetName})) {
                $target->{$targetName} = $value;
            }
            return;
        }

        [$member, $isSetter, $enum] = $writer;
        // A database row holds "PAID", the member is typed ?PaymentState. An unknown value is a ValueError
        if ($enum !== null && (is_int($value) || is_string($value))) {
            $value = $enum::from($value);
        }

        if ($isSetter) {
            $target->{$member}($value);
            return;
        }
        $target->{$member} = $value;
    }

    /**
     * How copy() writes a value with this name into an object of this class, resolved once per class and name:
     * [member, is setter, backed enum or null] for its setter (in any case), else its public property (exact case,
     * else any case); false when it has neither (copy() then writes it only to a property the instance has: dynamic,
     * or magic __isset()/__set()).
     *
     * For code that writes many values into objects of the same class with the same rules as copy(), such as an
     * entity hydrator: a backed enum member takes BackedEnum::from() of an int or string value.
     *
     * @param class-string $className
     * @return array{0: string, 1: bool, 2: class-string<BackedEnum>|null}|false
     */
    public static function writerFor(string $className, string $targetName): array|false
    {
        if (!isset(self::$writerCache[$className][$targetName])) {
            self::$writerCache[$className][$targetName] = self::writerOf($className, $targetName);
        }
        return self::$writerCache[$className][$targetName];
    }

    /**
     * How a class takes a value by a name: [member, is setter, backed enum or null], or false when it has
     * neither a setter nor a public property with that name
     *
     * @param class-string $className
     * @return array{0: string, 1: bool, 2: class-string<BackedEnum>|null}|false
     */
    private static function writerOf(string $className, string $targetName): array|false
    {
        $setter = 'set' . $targetName;
        if (method_exists($className, $setter)) {
            $type = ((new ReflectionMethod($className, $setter))->getParameters()[0] ?? null)?->getType();
            return [$setter, true, self::backedEnumOf($type)];
        }

        $properties = self::propNameLower($className);
        $property = in_array($targetName, $properties, true)
            ? $targetName
            : ($properties[strtolower($targetName)] ?? null);
        if ($property === null) {
            return false;
        }
        return [$property, false, self::backedEnumOf((new ReflectionProperty($className, $property))->getType())];
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
     * Map of lowercase property name => declared property name for a class (its public instance properties).
     *
     * @param class-string $className
     * @return array<string, string>
     */
    private static function propNameLower(string $className): array
    {
        if (!isset(self::$propNameLowerCache[$className])) {
            self::$propNameLowerCache[$className] = [];

            // The public instance properties: a static property is class state, not data of the object
            foreach ((new ReflectionClass($className))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
                if (!$property->isStatic()) {
                    self::$propNameLowerCache[$className][strtolower($property->getName())] = $property->getName();
                }
            }
        }

        return self::$propNameLowerCache[$className];
    }
}
