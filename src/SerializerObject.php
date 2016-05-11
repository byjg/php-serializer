<?php

namespace ByJG\Serialize;

use stdClass;

class SerializerObject
{
    protected $_model = null;
    protected $_methodPattern = ['/([^A-Za-z0-9])/', ''];
    protected $_methodGetPrefix = 'get';
    protected $_stopFirstLevel = false;
    protected $_currentLevel = 0;

    public function __construct($model)
    {
        $this->_model = $model;
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
     * @internal param array $methodPattern
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

    public function build()
    {
        $this->_currentLevel = 1;
        return $this->buildProperty($this->_model);
    }

    public function buildProperty($property)
    {
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

        return $property;
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
        $result = [];
        $this->_currentLevel++;

        foreach ((array)$object as $key => $value) {

            if ($key[0] == "\0") {
                // validate protected;
                $keyName = substr($key, strrpos($key, "\0"));
                $propertyName = preg_replace($this->getMethodPattern(0), $this->getMethodPattern(1), $keyName);

                if (method_exists($object, $this->getMethodGetPrefix() . $propertyName)) {
                    $result[$propertyName] = $this->buildProperty($value);
                }
            } else {
                $result[$key] = $this->buildProperty($value);
            }
        }

        return $result;
    }
    
}
