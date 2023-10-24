<?php

namespace ByJG\Serializer;

use ByJG\Serializer\Exception\InvalidArgumentException;
use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;
use stdClass;

class BinderObject
{
    protected $propNameLower = [];

    /**
     * Bind the properties from a source object to the properties matching to a target object
     *
     * @param mixed $source
     * @param mixed $target
     * @param PropertyPatternInterface|null $propertyPattern
     * @throws InvalidArgumentException
     */
    public static function bind($source, $target, ?PropertyPatternInterface $propertyPattern = null)
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
     * @throws InvalidArgumentException
     */
    protected function bindObjectInternal($source, $target, ?PropertyPatternInterface $propertyPattern = null)
    {
        if (is_array($target) || !is_object($target)) {
            throw new InvalidArgumentException('Target object must have to be an object instance');
        }

        $sourceArray = SerializerObject::instance($source)
                    ->withStopAtFirstLevel()
                    ->serialize();

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
    protected function setPropValue($obj, $propName, $value)
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
