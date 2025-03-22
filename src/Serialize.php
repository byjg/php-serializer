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
    private static array $reflectionCache = [];
    private static array $methodExistsCache = [];

    protected mixed $_model = null;
    protected array $_methodPattern = ['/([^A-Za-z0-9])/', ''];
    protected string $_methodGetPrefix = 'get';
    protected bool $_stopAtFirstLevel = false;
    protected bool $_onlyString = false;
    protected int $_currentLevel = 0;
    protected array $_doNotParse = [];
    protected bool $_serializeNull = true;
    protected array $_ignoreProperties = [];
    protected array $_ignorePropertiesMap = [];

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

        // Fast type detection using gettype
        $type = gettype($property);
        
        if ($type === 'array') {
            return $this->parseArray($property, $attributeFunction);
        }

        if ($type === 'object') {
            if ($property instanceof stdClass) {
                return $this->parseStdClass($property, $attributeFunction);
            }
            return $this->parseObject($property, $attributeClass, $attributeFunction);
        }

        if ($this->isOnlyString() && $type !== 'string') {
            $property = (string)$property;
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
        
        // Check if we need to filter null values
        $copyNulls = $this->isCopyingNullValues();
        $ignorePropertiesMap = $this->_ignorePropertiesMap;
        $hasIgnoreProperties = !empty($ignorePropertiesMap);

        foreach ($array as $key => $value) {
            // Fast check if property should be ignored - using isset is much faster than in_array
            if ($hasIgnoreProperties && isset($ignorePropertiesMap[$key])) {
                continue;
            }

            $parsedValue = $this->parseProperties($value);

            if (!is_null($attributeFunction)) {
                $parsedValue = $attributeFunction(null, $parsedValue, $key, $key, null);
            }

            // Skip null values if needed
            if ($parsedValue === null && !$copyNulls) {
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
        $className = get_class($object);
        $cachedObject = $this->cacheGet($className);
        if (!empty($cachedObject)) {
            return $cachedObject;
        }

        // Cache reflection object for this class
        if (!isset(self::$reflectionCache[$className])) {
            self::$reflectionCache[$className] = new ReflectionClass($object);
        }
        $reflection = self::$reflectionCache[$className];

        // Parse the object properties and cache the attributes
        foreach ((array)$object as $key => $value) {
            $propertyName = $key;
            $getter = null;
            $keyName = null;
            
            // More efficient property name extraction using regex
            if (str_starts_with($key, "\0")) {
                if (preg_match('/^\0[^\\0]*\0(.+)$/', $key, $matches)) {
                    $keyName = $matches[1];
                    
                    // For anonymous classes, extract just the property name (remove path and class)
                    if (str_contains($keyName, '$0')) {
                        $keyName = substr($keyName, strrpos($keyName, '$0') + 2);
                    }
                    
                    $propertyName = preg_replace($this->getMethodPattern(0), $this->getMethodPattern(1), $keyName);
                    
                    $getterMethod = $this->getMethodGetPrefix() . $propertyName;
                    if (!$this->methodExists($object, $getterMethod)) {
                        continue;
                    }
                    
                    $getter = $getterMethod;
                }
            }

            $keyName = $keyName ?? $propertyName;

            $this->cacheSetGetter($className, $propertyName, getter: $getter, keyName: $keyName);

            try {
                $property = $reflection->getProperty($keyName);
                $attributes = $property->getAttributes(null, ReflectionAttribute::IS_INSTANCEOF);
                foreach ($attributes as $attribute) {
                    $newAttribute = $attribute->newInstance();
                    $this->cacheSetAttributes($className, $propertyName, attribute: $newAttribute);
                }
            } catch (ReflectionException) {
                // Property doesn't exist in the reflection API, skip attributes
            }

            $this->setValue($result, $object, $propertyName, $this->cacheGetProperty($className, $propertyName), $attributeClass, $attributeFunction);
        }

        return [];  // Don't need to parse inside the object
    }

    // Once the properties are cached, we can get the array based on the cached properties
    protected function setValue(array &$result, object $object, string $propertyName, array $cachedProperty, ?string $attributeClass, ?Closure $attributeFunction): void
    {
        // Fast check if property should be ignored - using isset is much faster than in_array
        if (isset($this->_ignorePropertiesMap[$propertyName])) {
            return;
        }

        $getter = $cachedProperty["getter"];
        $keyName = $cachedProperty["keyName"];
        $objectClass = get_class($object);
        
        // Get property value using getter or direct access
        $value = !empty($getter) ? $object->$getter() : $object->$propertyName;
        
        // Parse the value
        $parsedValue = $this->parseProperties($value);

        // Process with attribute function if provided
        if (!is_null($attributeFunction)) {
            // Only look up attributes if we have an attribute class
            $attributes = $attributeClass ? 
                $this->cacheGetAttributes($objectClass, $propertyName, $attributeClass) : 
                null;
                
            $parsedValue = $attributeFunction($attributes, $parsedValue, $keyName, $propertyName, $getter);
        }

        // Skip null values if not copying them
        if ($parsedValue === null && !$this->isCopyingNullValues()) {
            return;
        }

        // For anonymous classes, use the simple property name instead of the internal representation
        if (str_starts_with($objectClass, "class@anonymous")) {
            // Extract the simple property name if it's a getter
            if (!empty($getter)) {
                $simplePropertyName = lcfirst(substr($getter, strlen($this->_methodGetPrefix)));
                $result[$simplePropertyName] = $parsedValue;
                return;
            }
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
        $className = get_class($object);
        
        // Quick check for non-parseable objects
        foreach ($this->_doNotParse as $class) {
            if ($className === $class || is_subclass_of($object, $class)) {
                return $object;
            }
        }

        // Special case for anonymous classes
        if (str_starts_with($className, "class@anonymous")) {
            return $this->parseAnonymousClass($object, $attributeClass, $attributeFunction);
        }

        // Start Serialize object
        $result = [];
        $this->_currentLevel++;

        $cachedObject = $this->cacheObject($object, $result, $attributeClass, $attributeFunction);

        // Get the values based on the cached properties
        if (!empty($cachedObject)) {
            foreach ($cachedObject as $propertyName => $cachedProperty) {
                $this->setValue($result, $object, $propertyName, $cachedProperty, $attributeClass, $attributeFunction);
            }
        }

        return $result;
    }

    /**
     * Parse an anonymous class using its getter methods
     */
    protected function parseAnonymousClass(object $object, ?string $attributeClass = null, ?Closure $attributeFunction = null): array
    {
        $result = [];
        $this->_currentLevel++;

        // Use reflection to get property names with original casing
        $reflectionClass = new ReflectionClass($object);
        $propertyMap = [];
        
        // Map all property names to their original casing
        foreach ($reflectionClass->getProperties() as $property) {
            $propertyMap[strtolower($property->getName())] = $property->getName();
        }

        // Process getters
        $methods = get_class_methods($object);
        $prefix = $this->getMethodGetPrefix();
        $prefixLen = strlen($prefix);
        $processedProperties = [];

        foreach ($methods as $method) {
            // Check if method starts with the getter prefix
            if (str_starts_with($method, $prefix) && strlen($method) > $prefixLen) {
                // Extract property name from getter (e.g., "getId" -> "id")
                $propertyKey = lcfirst(substr($method, $prefixLen));
                
                // Use original property casing if available
                $propertyName = $propertyMap[strtolower($propertyKey)] ?? $propertyKey;
                
                // Get the value using the getter
                $value = $object->$method();
                
                // Parse the value
                $parsedValue = $this->parseProperties($value);
                
                // Add to result array
                $result[$propertyName] = $parsedValue;
                $processedProperties[strtolower($propertyName)] = true;
            }
        }

        // Then process public properties
        $objVars = get_object_vars($object);
        foreach ($objVars as $key => $value) {
            // Only add public properties that weren't already processed via getters
            if (!isset($processedProperties[strtolower($key)])) {
                // Parse the value
                $parsedValue = $this->parseProperties($value);
                
                // Add to result array
                $result[$key] = $parsedValue;
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
        $this->_ignorePropertiesMap = array_flip($properties);
        return $this;
    }

    public function withoutIgnoreProperties(): static
    {
        $this->_ignoreProperties = [];
        $this->_ignorePropertiesMap = [];
        return $this;
    }

    protected function methodExists(object $object, string $method): bool
    {
        $className = get_class($object);
        $key = $className . '::' . $method;
        
        if (!isset(self::$methodExistsCache[$key])) {
            self::$methodExistsCache[$key] = method_exists($object, $method);
        }
        
        return self::$methodExistsCache[$key];
    }
}
