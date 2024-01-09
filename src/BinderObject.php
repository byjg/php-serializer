<?php

namespace ByJG\Serializer;

use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;
use stdClass;

class BinderObject
{
    protected array $propNameLower = [];

    /**
     * Bind the properties from a source object to the properties matching to a target object
     *
     * @param mixed $source
     * @param mixed $target
     * @param PropertyPatternInterface|null $propertyPattern
     */
    public static function bind(object|array $source, object|array $target, ?PropertyPatternInterface $propertyPattern = null): void
    {
        $binderObject = new BinderObject();
        $binderObject->bindObjectInternal($source, $target, $propertyPattern);
    }

    /**
     * Bind the properties from a source object to the properties matching to a target object
     *
     * @param mixed $source
     * @param mixed $target
     * @param PropertyPatternInterface|null $propertyPattern
     */
    protected function bindObjectInternal(object|array $source, object $target, ?PropertyPatternInterface $propertyPattern = null): void
    {
        $sourceArray = SerializerObject::instance($source)
                    ->withStopAtFirstLevel()
                    ->toArray();

        foreach ($sourceArray as $propName => $value) {
            if (!is_null($propertyPattern)) {
                if (!is_null($propertyPattern->getCallback())) {
                    $propName = preg_replace_callback($propertyPattern->getRegEx(), $propertyPattern->getCallback(), $propName);
                } else {
                    $propName = preg_replace($propertyPattern->getRegEx(), $propertyPattern->getReplacement(), $propName);
                }
            }
            $this->setPropValue($target, $propName, $value);
        }
    }

    /**
     * Set the property value
     *
     * @param mixed $obj
     * @param string $propName
     * @param string $value
     */
    protected function setPropValue(object $obj, string $propName, mixed $value): void
    {
        if (method_exists($obj, 'set' . $propName)) {
            $obj->{'set' . $propName}($value);
        } elseif (isset($obj->{$propName}) || $obj instanceof stdClass) {
            $obj->{$propName} = $value;
        } else {
            // Check if source property have property case name different from target
            $className = get_class($obj);
            if (!isset($this->propNameLower[$className])) {
                $this->propNameLower[$className] = [];

                $classVars = get_class_vars($className);
                foreach ($classVars as $varKey => $varValue) {
                    $this->propNameLower[$className][strtolower($varKey)] = $varKey;
                }
            }

            $propLower = strtolower($propName);
            if (isset($this->propNameLower[$className][$propLower])) {
                $obj->{$this->propNameLower[$className][$propLower]} = $value;
            }
        }
    }
}
