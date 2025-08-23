<?php

namespace ByJG\Serializer;

use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use stdClass;

/**
 * Final class for copying properties between objects
 */
final class ObjectCopy
{
    /**
     * Copy the properties from a source object to the properties matching to a target object
     *
     * @param object|array $source The source object
     * @param object|array $target The target object
     * @param PropertyHandlerInterface|null $propertyHandler The property handler
     * @return void
     */
    public static function copy(object|array $source, object|array $target, ?PropertyHandlerInterface $propertyHandler = null): void
    {
        $propNameLower = [];

        $sourceArray = Serialize::from($source)
            ->withStopAtFirstLevel()
            ->parseAttributes(
                function ($attribute, $value, $keyName, $propertyName) use ($propertyHandler, $target, &$propNameLower, $source) {
                    self::applyAttribute($attribute, $value, $keyName, $propertyName, $propertyHandler, $target, $propNameLower, $source);
                }
            );
    }

    /**
     * @param mixed $attribute
     * @param mixed $value
     * @param mixed $keyName
     * @param string $propertyName
     * @param PropertyHandlerInterface|null $propertyHandler
     * @param object|array $target
     * @param array $propNameLower
     * @param object|array $source
     * @return void
     */
    private static function applyAttribute(mixed $attribute, mixed $value, mixed $keyName, string $propertyName, ?PropertyHandlerInterface $propertyHandler, object|array $target, array &$propNameLower, object|array $source): void
    {
        // ----------------------------------------------
        // Extract the target name
        $targetName = $propertyName;
        if (!is_null($propertyHandler)) {
            $targetName = $propertyHandler->mapName($propertyName);
            // Pass the full source instance to allow property handler to access other properties
            $value = $propertyHandler->transformValue($propertyName, $targetName, $value, $source);
        }

        // ----------------------------------------------
        // Set the value to the target
        if (method_exists($target, 'set' . $targetName)) {
            $target->{'set' . $targetName}($value);
            return;
        }

        if (isset($target->{$targetName}) || $target instanceof stdClass) {
            $target->{$targetName} = $value;
            return;
        }

        // Check if source property have property case name different from target
        $className = get_class($target);
        if (!isset($propNameLower[$className])) {
            $propNameLower[$className] = [];

            $classVars = get_class_vars($className);
            foreach ($classVars as $varKey => $varValue) {
                $propNameLower[$className][strtolower($varKey)] = $varKey;
            }
        }

        $propLower = strtolower($targetName);
        if (isset($propNameLower[$className][$propLower])) {
            $target->{$propNameLower[$className][$propLower]} = $value;
        }
    }
}
