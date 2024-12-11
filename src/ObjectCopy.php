<?php

namespace ByJG\Serializer;

use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;
use Closure;
use stdClass;

abstract class ObjectCopy implements ObjectCopyInterface
{
    /**
     * @param array|object $source
     * @param \Closure|PropertyPatternInterface|null $propertyPattern
     */
    public function copyFrom(array|object $source, PropertyPatternInterface|\Closure|null $propertyPattern = null): void
    {
        ObjectCopy::copy($source, $this, $propertyPattern);
    }

    /**
     * @param array|object $target
     * @param Closure|PropertyPatternInterface|null $propertyPattern
     */
    public function copyTo(array|object $target, PropertyPatternInterface|Closure|null $propertyPattern = null): void
    {
        ObjectCopy::copy($this, $target, $propertyPattern);
    }

    /**
     * Copy the properties from a source object to the properties matching to a target object
     *
     * @param mixed $source
     * @param mixed $target
     * @param PropertyPatternInterface|Closure|null $propertyPattern
     * @param Closure|null $changeValue
     */
    public static function copy(object|array $source, object|array $target, PropertyPatternInterface|Closure|null $propertyPattern = null, ?Closure $changeValue = null): void
    {
        $propNameLower = [];

        $sourceArray = Serialize::from($source)
            ->withStopAtFirstLevel()
            ->parseAttributes(
                function ($attribute, $value, $keyName, $propertyName, $getterName) use ($propertyPattern, $changeValue, $target, $propNameLower) {
                    // ----------------------------------------------
                    // Extract the target name
                    $targetName = $propertyName;
                    if (!is_null($propertyPattern)) {
                        $targetName = $propertyPattern instanceof PropertyPatternInterface ? $propertyPattern->map($propertyName) : $propertyPattern($propertyName);
                    }
                    if (!is_null($changeValue)) {
                        $value = $changeValue($propertyName, $targetName, $value);
                    }

                    // ----------------------------------------------
                    // Set the value to the target
                    if (method_exists($target, 'set' . $targetName)) {
                        $target->{'set' . $targetName}($value);
                    } elseif (isset($target->{$targetName}) || $target instanceof stdClass) {
                        $target->{$targetName} = $value;
                    } else {
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
            );
    }
}
