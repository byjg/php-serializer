<?php

namespace ByJG\Serializer;

use ByJG\Serializer\Formatter\JsonFormatter;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use ByJG\Serializer\Formatter\XmlFormatter;
use ByJG\Serializer\Formatter\YamlFormatter;
use Closure;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use stdClass;
use Symfony\Component\Yaml\Yaml;

class Serialize
{
    private static array $cache = [];

    protected mixed $_model = null;
    protected array $_methodPattern = ['/([^A-Za-z0-9])/', ''];
    protected string $_methodGetPrefix = 'get';
    protected bool $_stopAtFirstLevel = false;
    protected bool $_onlyString = false;
    protected int $_currentLevel = 0;
    protected array $_doNotParse = [];
    protected bool $_serializeNull = true;
    protected array $_ignoreProperties = [];

    protected function __construct(mixed $model)
    {
        $this->_model = $model;
    }

    public static function from(object|array $model): static
    {
        return new Serialize($model);
    }

    public static function fromYaml(string $content): static
    {
        return new Serialize(Yaml::parse($content));
    }

    public static function fromJson(string $content): static
    {
        return new Serialize(json_decode($content, true));
    }

    public static function fromPhpSerialize(string $content): static
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

    public function parseAttributes(?Closure $attributeFunction, string $attributeClass = null): array
    {
        return $this->parseProperties($this->_model, 1, $attributeClass, $attributeFunction);
    }


    protected function parseProperties($property, $startLevel = null, ?string $attributeClass = null, ?Closure $attributeFunction = null): mixed
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
            return $this->parseArray($property, $attributeFunction);
        }

        if ($property instanceof stdClass) {
            return $this->parseStdClass($property, $attributeFunction);
        }

        if (is_object($property)) {
            return $this->parseObject($property, $attributeClass, $attributeFunction);
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
     * @return $this
     */
    public function withStopAtFirstLevel(): static
    {
        $this->_stopAtFirstLevel = true;
        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    protected function parseArray(array $array, ?\Closure $attributeFunction = null): array
    {
        $result = [];
        $this->_currentLevel++;

        foreach ($array as $key => $value) {
            if (in_array($key, $this->_ignoreProperties)) {
                continue;
            }

            $parsedValue = $this->parseProperties($value);

            if (!is_null($attributeFunction)) {
                $parsedValue = $attributeFunction(null, $parsedValue, $key, $key, null);
            }

            if ($parsedValue === null && !$this->isCopyingNullValues()) {
                continue;
            }

            $result[$key] = $parsedValue;
        }

        return $result;
    }

    /**
     * @param stdClass $stdClass
     * @return array
     */
    protected function parseStdClass(stdClass $stdClass, ?\Closure $attributeFunction = null): array
    {
        return $this->parseArray((array)$stdClass, $attributeFunction);
    }

    protected function cache(string $objectName, string $propertyName = null, string $getter = null, string $keyName = null, object $attribute = null): mixed
    {
        // If the object is not in the cache, create it
        if (!isset(self::$cache[$objectName][$this->getMethodPattern(0)][$this->getMethodPattern(1)])) {
            self::$cache[$objectName][$this->getMethodPattern(0)][$this->getMethodPattern(1)] = [];
        }

        // If the property is not passed, return the object
        if (empty($propertyName)) {
            return self::$cache[$objectName][$this->getMethodPattern(0)][$this->getMethodPattern(1)];
        }

        // If the property is not in the cache, create it
        if (!isset(self::$cache[$objectName][$this->getMethodPattern(0)][$this->getMethodPattern(1)][$propertyName])) {
            self::$cache[$objectName][$this->getMethodPattern(0)][$this->getMethodPattern(1)][$propertyName] = [
                "getter" => null,
                "keyName" => null,
                "attributes" => []
            ];
        }

        // If the getter is passed, cache it
        if (!empty($getter)) {
            self::$cache[$objectName][$this->getMethodPattern(0)][$this->getMethodPattern(1)][$propertyName]["getter"] = $getter;
        }

        if (!empty($keyName)) {
            self::$cache[$objectName][$this->getMethodPattern(0)][$this->getMethodPattern(1)][$propertyName]["keyName"] = $keyName;
        }

        // If the attribute is passed, cache it
        if (!empty($attribute)) {
            self::$cache[$objectName][$this->getMethodPattern(0)][$this->getMethodPattern(1)][$propertyName]["attributes"][] = $attribute;
        }

        return self::$cache[$objectName][$this->getMethodPattern(0)][$this->getMethodPattern(1)][$propertyName];

    }

    /**
     * @param object $object
     * @param string|null $attributeClass
     * @param Closure|null $attributeFunction
     * @return array|object
     * @throws ReflectionException
     */
    protected function parseObject(object $object, ?string $attributeClass = null, ?Closure $attributeFunction = null): array|object
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


        $cachedObject = $this->cache(get_class($object));

        // Parse the object properties and cache the attributes
        if (empty($cachedObject)) {
            foreach ((array)$object as $key => $value) {
                $propertyName = $key;
                $getter = null;
                $keyName = null;
                if (str_starts_with($key, "\0")) {
                    // validate protected;
                    $keyName = trim(substr($key, strrpos($key, "\0")));
                    $propertyName = preg_replace($this->getMethodPattern(0), $this->getMethodPattern(1), $keyName);

                    if (!method_exists($object, $this->getMethodGetPrefix() . $propertyName)) {
                        continue;
                    }

                    $getter = $this->getMethodGetPrefix() . $propertyName;
                }

                $keyName = $keyName ?? $propertyName;

                $this->cache(get_class($object), $propertyName, getter: $getter, keyName: $keyName);

                $reflection = new ReflectionClass($object);
                $attributes = $reflection->getProperty($keyName)->getAttributes(null, ReflectionAttribute::IS_INSTANCEOF);
                foreach ($attributes as $attribute) {
                    $newAttribute = $attribute->newInstance();
                    $this->cache(get_class($object), $propertyName, attribute: $newAttribute);
                }
            }

            $cachedObject = $this->cache(get_class($object));
        }

        // Get the values based on the cached properties
        foreach ($cachedObject as $propertyName => $cachedProperty) {
            if (in_array($propertyName, $this->_ignoreProperties)) {
                continue;
            }

            $getter = $cachedProperty["getter"];
            $keyName = $cachedProperty["keyName"];
            if (!empty($getter)) {
                $value = $object->$getter();
            } else {
                $value = $object->$propertyName;
            }

            $parsedValue = $this->parseProperties($value);

            if (!is_null($attributeFunction)) {
                $attributes = $cachedProperty["attributes"];
                if (count($attributes) == 0 || $attributeClass === null) {
                    $parsedValue = $attributeFunction(null, $parsedValue, $keyName, $propertyName, $getter);
                } else {
                    foreach ($attributes as $attribute) {
                        if ($attribute instanceof $attributeClass) {
                            $parsedValue = $attributeFunction($attribute, $parsedValue, $keyName, $propertyName, $getter);
                        }
                    }
                }
            }

            if ($parsedValue === null && !$this->isCopyingNullValues()) {
                continue;
            }

            $result[$propertyName] = $parsedValue;
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
     * @return $this
     */
    public function withMethodPattern($search, $replace): static
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
     * @return $this
     */
    public function withMethodGetPrefix(string $methodGetPrefix): static
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
    public function withOnlyString(bool $value = true): static
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
    public function withDoNotParse(array $doNotParse): static
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
    public function withDoNotParseNullValues(): static
    {
        $this->_serializeNull = false;
        return $this;
    }

    public function withIgnoreProperties(array $properties): static
    {
        $this->_ignoreProperties = $properties;
        return $this;
    }

    public function withoutIgnoreProperties(): static
    {
        $this->_ignoreProperties = [];
        return $this;
    }
}
