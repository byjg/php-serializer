<?php

namespace ByJG\Serializer;

use ByJG\Serializer\Formatter\JsonFormatter;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use ByJG\Serializer\Formatter\XmlFormatter;
use ByJG\Serializer\Formatter\YamlFormatter;
use stdClass;
use Symfony\Component\Yaml\Yaml;

class Serialize
{
    protected mixed $_model = null;
    protected array $_methodPattern = ['/([^A-Za-z0-9])/', ''];
    protected string $_methodGetPrefix = 'get';
    protected bool $_stopAtFirstLevel = false;
    protected bool $_onlyString = false;
    protected int $_currentLevel = 0;
    protected array $_doNotParse = [];
    protected bool $_serializeNull = true;

    protected function __construct(mixed $model)
    {
        $this->_model = $model;
    }

    public static function from(mixed $model): self
    {
        return new Serialize($model);
    }

    public static function fromYaml(string $content): self
    {
        return new Serialize(Yaml::parse($content));
    }

    public static function fromJson(string $content): self
    {
        return new Serialize(json_decode($content, true));
    }

    public static function fromPhpSerialize(string $content): self
    {
        return new Serialize(unserialize($content));
    }

    /**
     * Build the array based on the object properties
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->parseProperties($this->_model, 1);
    }

    public function toPhpSerialize(bool $parse = false): string
    {
        if ($parse) {
            return serialize($this->parseProperties($this->_model, 1));
        }
        return serialize($this->_model);
    }

    public function toYaml(): string
    {
        $yamlFormatter = new YamlFormatter();
        return $yamlFormatter->process($this->parseProperties($this->_model, 1));
    }

    public function toJson(): string
    {
        $jsonFormatter = new JsonFormatter();
        return $jsonFormatter->process($this->parseProperties($this->_model, 1));
    }

    public function toXml(): string
    {
        $xmlFormatter = new XmlFormatter();
        return $xmlFormatter->process($this->parseProperties($this->_model, 1));
    }

    public function toPlainText(): string
    {
        $plainTextFormatter = new PlainTextFormatter();
        return $plainTextFormatter->process($this->parseProperties($this->_model, 1));
    }


    protected function parseProperties($property, $startLevel = null): mixed
    {
        if (!empty($startLevel)) {
            $this->_currentLevel = $startLevel;
        }

        // If Stop at First Level is active and the current level is greater than 1 return the
        // original object instead convert it to array;
        if ($this->isStoppingAtFirstLevel() && $this->_currentLevel > 1) {
            return $property;
        }

        if (is_array($property)) {
            return $this->parseArray($property);
        }

        if ($property instanceof stdClass) {
            return $this->parseStdClass($property);
        }

        if (is_object($property)) {
            return $this->parseObject($property);
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
     * @return Serialize
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
    protected function parseArray(array $array): array
    {
        $result = [];
        $this->_currentLevel++;

        foreach ($array as $key => $value) {
            $result[$key] = $this->parseProperties($value);

            if ($result[$key] === null && !$this->isCopyingNullValues()) {
                unset($result[$key]);
            }
        }

        return $result;
    }

    /**
     * @param stdClass $stdClass
     * @return array
     */
    protected function parseStdClass(stdClass $stdClass): array
    {
        return $this->parseArray((array)$stdClass);
    }

    /**
     * @param object $object
     * @return array|object
     */
    protected function parseObject(object $object): array|object
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

            $result[$propertyName] = $this->parseProperties($value);

            if ($result[$propertyName] === null && !$this->isCopyingNullValues()) {
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
     * @return Serialize
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
     * @return Serialize
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
    public function isCopyingNullValues(): bool
    {
        return $this->_serializeNull;
    }

    /**
     * @return $this
     */
    public function withDoNotNullValues(): self
    {
        $this->_serializeNull = false;
        return $this;
    }

}
