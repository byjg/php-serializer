<?php

namespace ByJG\Serializer;

use stdClass;

class SerializerObject
{
    protected $_model = null;
    protected $_methodPattern = ['/([^A-Za-z0-9])/', ''];
    protected $_methodGetPrefix = 'get';
    protected $_stopAtFirstLevel = false;
    protected $_onlyString = false;
    protected $_currentLevel = 0;
    protected $_doNotParse = [];
    protected $_serializeNull = true;

    public function __construct($model)
    {
        $this->_model = $model;
    }

    public static function instance($model)
    {
        return new SerializerObject($model);
    }

    /**
     * Build the array based on the object properties
     *
     * @return array
     */
    public function serialize()
    {
        $this->_currentLevel = 1;
        return $this->serializeProperties($this->_model);
    }

    protected function serializeProperties($property)
    {
        // If Stop at First Level is active and the current level is greater than 1 return the
        // original object instead convert it to array;
        if ($this->isStoppingAtFirstLevel() && $this->_currentLevel > 1) {
            return $property;
        }

        if (is_array($property)) {
            return $this->serializeArray($property);
        }

        if ($property instanceof \stdClass) {
            return $this->serializeStdClass($property);
        }

        if (is_object($property)) {
            return $this->serializeObject($property);
        }

        if ($this->isOnlyString()) {
            $property = "$property";
        }
        return $property;
    }

    /**
     * @return bool
     */
    public function isStoppingAtFirstLevel()
    {
        return $this->_stopAtFirstLevel;
    }

    /**
     * @param bool $stopAtFirstLevel
     */
    public function withStopAtFirstLevel()
    {
        $this->_stopAtFirstLevel = true;
        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    protected function serializeArray(array $array)
    {
        $result = [];
        $this->_currentLevel++;

        foreach ($array as $key => $value) {
            $result[$key] = $this->serializeProperties($value);

            if ($result[$key] === null && !$this->isSerializingNull()) {
                unset($result[$key]);
            }
        }

        return $result;
    }

    /**
     * @param stdClass $stdClass
     * @return array
     */
    protected function serializeStdClass(\stdClass $stdClass)
    {
        return $this->serializeArray((array)$stdClass);
    }

    /**
     * @param stdClass|object $object
     * @return array|object
     */
    protected function serializeObject($object)
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
            $propertyName = $key;
            if ($key[0] == "\0") {
                // validate protected;
                $keyName = substr($key, strrpos($key, "\0"));
                $propertyName = preg_replace($this->getMethodPattern(0), $this->getMethodPattern(1), $keyName);

                if (!method_exists($object, $this->getMethodGetPrefix() . $propertyName)) {
                    continue;
                }
                $value = $object->{$this->getMethodGetPrefix() . $propertyName}();
            }

            $result[$propertyName] = $this->serializeProperties($value);

            if ($result[$propertyName] === null && !$this->isSerializingNull()) {
                unset($result[$propertyName]);
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
    public function withMethodPattern($search, $replace)
    {
        $this->_methodPattern = [$search, $replace];
        return $this;
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
    public function withMethodGetPrefix($methodGetPrefix)
    {
        $this->_methodGetPrefix = $methodGetPrefix;
        return $this;
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
     * @return $this
     */
    public function withOnlyString($value = true)
    {
        $this->_onlyString = $value;
        return $this;
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
     * @return $this
     */
    public function withDoNotParse(array $doNotParse)
    {
        $this->_doNotParse = $doNotParse;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSerializingNull()
    {
        return $this->_serializeNull;
    }

    /**
     * @param bool $buildNull
     * @return $this
     */
    public function withDoNotSerializeNull()
    {
        $this->_serializeNull = false;
        return $this;
    }

}
