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
    public static function copy(object|array $source, object|array $target, PropertyPatternInterface|Closure|null $propertyPattern = null, Closure $changeValue = null): void
    {
        $sourceArray = Serialize::from($source)
            ->withStopAtFirstLevel()
            ->toArray();
        
        $propNameLower = [];

        $setPropValue = function(object $obj, string $propName, mixed $value) use ($propNameLower) {
            if (method_exists($obj, 'set' . $propName)) {
                $obj->{'set' . $propName}($value);
            } elseif (isset($obj->{$propName}) || $obj instanceof stdClass) {
                $obj->{$propName} = $value;
            } else {
                // Check if source property have property case name different from target
                $className = get_class($obj);
                if (!isset($propNameLower[$className])) {
                    $propNameLower[$className] = [];

                    $classVars = get_class_vars($className);
                    foreach ($classVars as $varKey => $varValue) {
                        $propNameLower[$className][strtolower($varKey)] = $varKey;
                    }
                }

                $propLower = strtolower($propName);
                if (isset($propNameLower[$className][$propLower])) {
                    $obj->{$propNameLower[$className][$propLower]} = $value;
                }
            }
        };

        foreach ($sourceArray as $propName => $value) {
            $targetName = $propName;
            if (!is_null($propertyPattern)) {
                $targetName = $propertyPattern instanceof PropertyPatternInterface ? $propertyPattern->map($propName) : $propertyPattern($propName);
            }
            if (!is_null($changeValue)) {
                $value = $changeValue($propName, $targetName, $value);
            }
            $setPropValue($target, $targetName, $value);
        }
    }
}
