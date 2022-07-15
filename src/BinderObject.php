<?php

namespace ByJG\Serializer;

use ByJG\Serializer\Exception\InvalidArgumentException;
use stdClass;

class BinderObject
{

    protected $propNameLower = [];

    /**
     * Bind the properties from a source object to the properties matching to a target object
     *
     * @param mixed $source
     * @param mixed $target
     * @param string $propertyPattern Regular Expression -> /searchPattern/replace/
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public static function bind($source, $target, $propertyPattern = null)
    {
        $binderObject = new BinderObject();
        $binderObject->bindObjectInternal($source, $target, $propertyPattern);
    }

    /**
     * Bind the properties from a source object to the properties matching to a target object
     *
     * @param mixed $source
     * @param mixed $target
     * @param string $propertyPattern Regular Expression -> /searchPattern/replace/
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    protected function bindObjectInternal($source, $target, $propertyPattern = null)
    {
        if (is_array($target) || !is_object($target)) {
            throw new InvalidArgumentException('Target object must have to be an object instance');
        }

        $sourceArray = SerializerObject::instance($source)
                    ->withStopAtFirstLevel()
                    ->serialize();

        foreach ($sourceArray as $propName => $value) {
            if (!is_null($propertyPattern)) {
                $propAr = explode($propertyPattern[0], $propertyPattern);
                $propName = preg_replace(
                    $propertyPattern[0] . $propAr[1] . $propertyPattern[0],
                    $propAr[2],
                    $propName
                );
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
