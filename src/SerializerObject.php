<?php

namespace ByJG\Serializer;

use stdClass;
use Symfony\Component\Yaml\Yaml;

class SerializerObject
{
    protected mixed $_model = null;
    protected array $_methodPattern = ['/([^A-Za-z0-9])/', ''];
    protected string $_methodGetPrefix = 'get';
    protected bool $_stopAtFirstLevel = false;
    protected bool $_onlyString = false;
    protected int $_currentLevel = 0;
    protected array $_doNotParse = [];
    protected bool $_serializeNull = true;

    protected string $_sourceType = "OBJECT";

    public function __construct(mixed $model)
    {
        $this->_model = $model;
    }

    public static function instance(mixed $model): self
    {
        return new SerializerObject($model);
    }

    public function fromYaml(): self
    {
        $this->_sourceType = "YAML";
        return $this;
    }

    public function fromJson(): self
    {
        $this->_sourceType = "JSON";
        return $this;
    }

    /**
     * Build the array based on the object properties
     *
     * @return array
     */
    public function serialize(): array
    {
        if ($this->_sourceType == "YAML") {
            return Yaml::parse($this->_model);
        } elseif ($this->_sourceType == "JSON") {
            return json_decode($this->_model, true);
        }

        $this->_currentLevel = 1;
        return $this->serializeProperties($this->_model);
    }

    protected function serializeProperties($property): mixed
    {
        // If Stop at First Level is active and the current level is greater than 1 return the
        // original object instead convert it to array;
        if ($this->isStoppingAtFirstLevel() && $this->_currentLevel > 1) {
            return $property;
        }

        if (is_array($property)) {
            return $this->serializeArray($property);
        }

        if ($property instanceof stdClass) {
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
    public function isStoppingAtFirstLevel(): bool
    {
        return $this->_stopAtFirstLevel;
    }

    /**
     * @return SerializerObject
     */
    public function withStopAtFirstLevel(): self
    {
        $this->_stopAtFirstLevel = true;
        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    protected function serializeArray(array $array): array
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
    protected function serializeStdClass(stdClass $stdClass): array
    {
        return $this->serializeArray((array)$stdClass);
    }

    /**
     * @param object $object
     * @return array|object
     */
    protected function serializeObject(object $object): array|object
    {
        // Check if this object can serialize
        foreach ($this->_doNotParse as $class) {
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
     * @param int $key
     * @return string
     */
    public function getMethodPattern(int $key): string
    {
        return $this->_methodPattern[$key];
    }

    /**
     * @param $search
     * @param $replace
     * @return SerializerObject
     */
    public function withMethodPattern($search, $replace): self
    {
        $this->_methodPattern = [$search, $replace];
        return $this;
    }

    /**
     * @return string
     */
    public function getMethodGetPrefix(): string
    {
        return $this->_methodGetPrefix;
    }

    /**
     * @param string $methodGetPrefix
     * @return SerializerObject
     */
    public function withMethodGetPrefix(string $methodGetPrefix): self
    {
        $this->_methodGetPrefix = $methodGetPrefix;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isOnlyString(): bool
    {
        return $this->_onlyString;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function withOnlyString(bool $value = true): self
    {
        $this->_onlyString = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getDoNotParse(): array
    {
        return $this->_doNotParse;
    }

    /**
     * @param array $doNotParse
     * @return $this
     */
    public function withDoNotParse(array $doNotParse): self
    {
        $this->_doNotParse = $doNotParse;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSerializingNull(): bool
    {
        return $this->_serializeNull;
    }

    /**
     * @return $this
     */
    public function withDoNotSerializeNull(): self
    {
        $this->_serializeNull = false;
        return $this;
    }

}
