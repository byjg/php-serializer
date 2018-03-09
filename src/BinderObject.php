<?php

namespace ByJG\Serializer;

use stdClass;

class BinderObject implements DumpToArrayInterface
{

    protected $propNameLower = [];

    /**
     * Bind the properties from a source object to the properties matching to a target object
     *
     * @param mixed $source
     * @param mixed $target
     * @param string $propertyPattern Regular Expression -> /searchPattern/replace/
     * @throws \Exception
     */
    public static function bindObject($source, $target, $propertyPattern = null)
    {
        $binderObject = new BinderObject();
        $binderObject->bindObjectInternal($source, $target, $propertyPattern);
    }

    /**
     * Bind the properties from an object to the properties matching to the current instance
     *
     * @param mixed $source
     * @param null|string $propertyPattern Regular Expression -> /searchPattern/replace/
     * @throws \Exception
     */
    public function bind($source, $propertyPattern = null)
    {
        $this->bindObjectInternal($source, $this, $propertyPattern);
    }

    /**
     * Bind the properties from a source object to the properties matching to a target object
     *
     * @param mixed $source
     * @param mixed $target
     * @param string $propertyPattern Regular Expression -> /searchPattern/replace/
     * @throws \Exception
     */
    protected function bindObjectInternal($source, $target, $propertyPattern = null)
    {
        if (is_array($target) || !is_object($target)) {
            throw new \InvalidArgumentException('Target object must have to be an object instance');
        }

        $sourceArray = self::toArrayFrom($source, true);

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
     * Get all properties from a source object as an associative array
     *
     * @param mixed $source
     * @param bool $firstLevel
     * @param array $excludeClasses
     * @param array|null $propertyPattern
     * @return array
     * @throws \Exception
     */
    public static function toArrayFrom($source, $firstLevel = false, $excludeClasses = [], $propertyPattern = null)
    {
        // Prepare the source object type
        $object = new SerializerObject($source);
        $object->setStopFirstLevel($firstLevel);
        $object->setDoNotParse($excludeClasses);
        if (!is_null($propertyPattern)) {
            if (!is_array($propertyPattern)) {
                throw new \InvalidArgumentException(
                    'Property pattern must be an array with 2 regex elements (Search and Replace)'
                );
            }
            $object->setMethodPattern($propertyPattern[0], $propertyPattern[1]);
        }
        return $object->build();
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

    /**
     * Bind the properties from the current instance to the properties matching to an object
     *
     * @param mixed $target
     * @param null|string $propertyPattern Regular Expression -> /searchPattern/replace/
     * @throws \Exception
     */
    public function bindTo($target, $propertyPattern = null)
    {
        $this->bindObjectInternal($this, $target, $propertyPattern);
    }

    /**
     * Get all properties from the current instance as an associative array
     *
     * @return array The object properties as array
     * @throws \Exception
     */
    public function toArray()
    {
        return self::toArrayFrom($this);
    }
}
