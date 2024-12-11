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

    public function toYaml(): string|bool
    {
        $yamlFormatter = new YamlFormatter();
        return $yamlFormatter->process($this->parseProperties($this->_model, 1));
    }

    public function toJson(): string|bool
    {
        $jsonFormatter = new JsonFormatter();
        return $jsonFormatter->process($this->parseProperties($this->_model, 1));
    }

    public function toXml(): string|bool
    {
        $xmlFormatter = new XmlFormatter();
        return $xmlFormatter->process($this->parseProperties($this->_model, 1));
    }

    public function toPlainText(): string|bool
    {
        $plainTextFormatter = new PlainTextFormatter();
        return $plainTextFormatter->process($this->parseProperties($this->_model, 1));
    }

    public function parseAttributes(?Closure $attributeFunction, ?string $attributeClass = null): array
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

    protected function _cacheKey(string $objectName)
    {
        return $objectName . '|' . $this->_methodPattern[0] . '|' . $this->_methodPattern[1];
    }

    protected function cacheGet($objectName): array
    {
        $key = $this->_cacheKey($objectName);

        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = [];
        }

        return self::$cache[$key];
    }

    protected function cacheGetProperty(string $objectName, string $propertyName): array
    {
        $key = $this->_cacheKey($objectName);

        if (!isset(self::$cache[$key][$propertyName])) {
            self::$cache[$key][$propertyName] = [
                "getter" => null,
                "keyName" => null,
                "attributes" => []
            ];
        }

        return self::$cache[$key][$propertyName];
    }

    protected function cacheSetGetter(string $objectName, string $propertyName, ?string $getter, ?string $keyName): void
    {
        $key = $this->_cacheKey($objectName);

        self::$cache[$key][$propertyName]["getter"] = $getter;
        self::$cache[$key][$propertyName]["keyName"] = $keyName;
    }

    protected function cacheSetAttributes(string $objectName, string $propertyName, object $attribute): void
    {
        $key = $this->_cacheKey($objectName);

        self::$cache[$key][$propertyName]["attributes"][get_class($attribute)] = $attribute;
    }

    protected function cacheGetAttributes(string $objectName, string $propertyName, string $attribute): object|null
    {
        $key = $this->_cacheKey($objectName);

        return self::$cache[$key][$propertyName]["attributes"][$attribute] ?? null;
    }

    // This is the stage to get the first parse of the object and cache the properties
    public function cacheObject(object $object, array &$result, ?string $attributeClass, ?Closure $attributeFunction): array
    {
        $cachedObject = $this->cacheGet(get_class($object));
        if (!empty($cachedObject)) {
            return $cachedObject;
        }

        // Parse the object properties and cache the attributes
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

            $this->cacheSetGetter(get_class($object), $propertyName, getter: $getter, keyName: $keyName);

            $reflection = new ReflectionClass($object);
            $attributes = $reflection->getProperty($keyName)->getAttributes(null, ReflectionAttribute::IS_INSTANCEOF);
            foreach ($attributes as $attribute) {
                $newAttribute = $attribute->newInstance();
                $this->cacheSetAttributes(get_class($object), $propertyName, attribute: $newAttribute);
            }

            $this->setValue($result, $object, $propertyName, $this->cacheGetProperty(get_class($object), $propertyName), $attributeClass, $attributeFunction);
        }

        return [];  // Don't need to parse inside the object
    }

    // Once the properties are cached, we can get the array based on the cached properties
    protected function setValue(array &$result, object $object, string $propertyName, array $cachedProperty, ?string $attributeClass, ?Closure $attributeFunction): void
    {
        if (in_array($propertyName, $this->_ignoreProperties)) {
            return;
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
            $attributes = $this->cacheGetAttributes(get_class($object), $propertyName, $attributeClass ?? '.');
            $parsedValue = $attributeFunction($attributes ?? null, $parsedValue, $keyName, $propertyName, $getter);
        }

        if ($parsedValue === null && !$this->isCopyingNullValues()) {
            return;
        }

        $result[$propertyName] = $parsedValue;
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

        $cachedObject = $this->cacheObject($object, $result, $attributeClass, $attributeFunction);

        // Get the values based on the cached properties
        foreach ($cachedObject as $propertyName => $cachedProperty) {
            $this->setValue($result, $object, $propertyName, $cachedProperty, $attributeClass, $attributeFunction);
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
