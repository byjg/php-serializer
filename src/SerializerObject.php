<?php

namespace ByJG\Serializer;

use stdClass;

class SerializerObject
{
    protected $_model = null;
    protected $_methodPattern = ['/([^A-Za-z0-9])/', ''];
    protected $_methodGetPrefix = 'get';
    protected $_stopFirstLevel = false;
    protected $_onlyString = false;
    protected $_currentLevel = 0;
    protected $_doNotParse = [];

    public function __construct($model)
    {
        $this->_model = $model;
    }

    /**
     * Build the array based on the object properties
     *
     * @return array
     */
    public function build()
    {
        $this->_currentLevel = 1;
        return $this->buildProperty($this->_model);
    }

    public function buildProperty($property)
    {
        // If Stop at First Level is active and the current level is greater than 1 return the
        // original object instead convert it to array;
        if ($this->getStopFirstLevel() && $this->_currentLevel > 1) {
            return $property;
        }

        if (is_array($property)) {
            return $this->buildArray($property);
        }

        if ($property instanceof \stdClass) {
            return $this->buildStdClass($property);
        }

        if (is_object($property)) {
            return $this->buildObject($property);
        }

        if ($this->isOnlyString()) {
            $property = "$property";
        }
        return $property;
    }

    /**
     * @return bool
     */
    public function getStopFirstLevel()
    {
        return $this->_stopFirstLevel;
    }

    /**
     * @param bool $stopAtFirstLevel
     */
    public function setStopFirstLevel($stopAtFirstLevel)
    {
        $this->_stopFirstLevel = $stopAtFirstLevel;
    }

    /**
     * @param array $array
     * @return array
     */
    public function buildArray(array $array)
    {
        $result = [];
        $this->_currentLevel++;

        foreach ($array as $key => $value) {
            $result[$key] = $this->buildProperty($value);
        }

        return $result;
    }

    /**
     * @param stdClass $stdClass
     * @return array
     */
    public function buildStdClass(\stdClass $stdClass)
    {
        return $this->buildArray((array)$stdClass);
    }

    public function buildObject($object)
    {
        // Check if this object can serialized
        foreach ((array)$this->_doNotParse as $class) {
            if (is_a($object, $class)) {
                return $object;
            }
        }
        
        // Start Serialize object
        $result = [];
        $this->_currentLevel++;

        foreach ((array)$object as $key => $value) {

            if ($key[0] == "\0") {
                // validate protected;
                $keyName = substr($key, strrpos($key, "\0"));
                $propertyName = preg_replace($this->getMethodPattern(0), $this->getMethodPattern(1), $keyName);

                if (method_exists($object, $this->getMethodGetPrefix() . $propertyName)) {
                    $value = $object->{$this->getMethodGetPrefix() . $propertyName}();
                    $result[$propertyName] = $this->buildProperty($value);
                }
            } else {
                $result[$key] = $this->buildProperty($value);
            }
        }

        return $result;
    }

    /**
     * @param $key
     * @return array
     */
    public function getMethodPattern($key)
    {
        return $this->_methodPattern[$key];
    }

    /**
     * @param $search
     * @param $replace
     */
    public function setMethodPattern($search, $replace)
    {
        $this->_methodPattern = [$search, $replace];
    }

    /**
     * @return string
     */
    public function getMethodGetPrefix()
    {
        return $this->_methodGetPrefix;
    }

    /**
     * @param string $methodGetPrefix
     */
    public function setMethodGetPrefix($methodGetPrefix)
    {
        $this->_methodGetPrefix = $methodGetPrefix;
    }

    /**
     * @return boolean
     */
    public function isOnlyString()
    {
        return $this->_onlyString;
    }

    /**
     * @param boolean $onlyString
     */
    public function setOnlyString($onlyString)
    {
        $this->_onlyString = $onlyString;
    }

    /**
     * @return array
     */
    public function getDoNotParse()
    {
        return $this->_doNotParse;
    }

    /**
     * @param array $doNotParse
     */
    public function setDoNotParse(array $doNotParse)
    {
        $this->_doNotParse = $doNotParse;
    }

}
