<?php

namespace ByJG\Serializer;

use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;
use stdClass;

abstract class ObjectCopy implements ObjectCopyInterface
{
    /**
     * @param array|object $source
     * @param PropertyPatternInterface|null $propertyPattern
     */
    public function copyFrom(array|object $source, ?PropertyPatternInterface $propertyPattern = null): void
    {
        ObjectCopy::copy($source, $this, $propertyPattern);
    }

    /**
     * @param array|object $target
     * @param PropertyPatternInterface|null $propertyPattern
     */
    public function copyTo(array|object $target, ?PropertyPatternInterface $propertyPattern = null): void
    {
        ObjectCopy::copy($this, $target, $propertyPattern);
    }

    /**
     * Copy the properties from a source object to the properties matching to a target object
     *
     * @param mixed $source
     * @param mixed $target
     * @param PropertyPatternInterface|null $propertyPattern
     */
    public static function copy(object|array $source, object|array $target, ?PropertyPatternInterface $propertyPattern = null): void
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
            if (!is_null($propertyPattern)) {
                if (!is_null($propertyPattern->getCallback())) {
                    $propName = preg_replace_callback($propertyPattern->getRegEx(), $propertyPattern->getCallback(), $propName);
                } else {
                    $propName = preg_replace($propertyPattern->getRegEx(), $propertyPattern->getReplacement(), $propName);
                }
            }
            $setPropValue($target, $propName, $value);
        }
    }
}
