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
    private static array $_cache = [];
    private static array $_reflectionCache = [];
    private static array $_methodExistsCache = [];

    protected mixed $model = null;
    protected array $methodPattern = ['/([^A-Za-z0-9])/', ''];
    protected string $methodGetPrefix = 'get';
    protected bool $stopAtFirstLevel = false;
    protected bool $onlyString = false;
    protected int $currentLevel = 0;
    protected array $doNotParse = [];
    protected bool $serializeNull = true;
    protected array $ignoreProperties = [];
    protected array $ignorePropertiesMap = [];

    protected function __construct(mixed $model)
    {
        $this->model = $model;
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
        return $this->parseProperties($this->model, 1);
    }

    /**
     * Convert the model to PHP serialized format
     *
     * @param bool $parse Whether to parse the model before serializing
     * @return string Serialized PHP string
     */
    public function toPhpSerialize(bool $parse = false): string
    {
        if ($parse) {
            return serialize($this->parseProperties($this->model, 1));
        }
        return serialize($this->model);
    }

    /**
     * Process the model with a formatter
     *
     * @param object $formatter The formatter to use
     * @return string|bool The formatted output
     */
    private function _processWithFormatter(object $formatter): string|bool
    {
        return $formatter->process($this->parseProperties($this->model, 1));
    }

    /**
     * Convert the model to YAML format
     *
     * @return string|bool YAML representation of the model
     */
    public function toYaml(): string|bool
    {
        return $this->_processWithFormatter(new YamlFormatter());
    }

    /**
     * Convert the model to JSON format
     *
     * @return string|bool JSON representation of the model
     */
    public function toJson(): string|bool
    {
        return $this->_processWithFormatter(new JsonFormatter());
    }

    /**
     * Convert the model to XML format
     *
     * @return string|bool XML representation of the model
     */
    public function toXml(): string|bool
    {
        return $this->_processWithFormatter(new XmlFormatter());
    }

    /**
     * Convert the model to plain text format
     *
     * @return string|bool Plain text representation of the model
     */
    public function toPlainText(): string|bool
    {
        return $this->_processWithFormatter(new PlainTextFormatter());
    }

    public function parseAttributes(?Closure $attributeFunction, ?string $attributeClass = null): array
    {
        return $this->parseProperties($this->model, 1, $attributeClass, $attributeFunction);
    }


    protected function parseProperties($property, $startLevel = null, ?string $attributeClass = null, ?Closure $attributeFunction = null): mixed
    {
        if (!empty($startLevel)) {
            $this->currentLevel = $startLevel;
        }

        // If Stop at First Level is active and the current level is greater than 1 return the
        // original object instead convert it to array;
        if ($this->isStoppingAtFirstLevel() && $this->currentLevel > 1) {
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
        return $this->stopAtFirstLevel;
    }

    /**
     * @return $this
     */
    public function withStopAtFirstLevel(): static
    {
        $this->stopAtFirstLevel = true;
        return $this;
    }

    /**
     * @param array $array
     * @return array
     */
    protected function parseArray(array $array, ?\Closure $attributeFunction = null): array
    {
        $result = [];
        $this->currentLevel++;
        
        // Check if we need to filter null values
        $copyNulls = $this->isCopyingNullValues();
        $ignorePropertiesMap = $this->ignorePropertiesMap;
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

    protected function getCacheKey(string $objectName): string
    {
        return $objectName . '|' . $this->methodPattern[0] . '|' . $this->methodPattern[1];
    }

    protected function cacheGet(string $objectName): array
    {
        $key = $this->getCacheKey($objectName);

        if (!isset(self::$_cache[$key])) {
            self::$_cache[$key] = [];
        }

        return self::$_cache[$key];
    }

    protected function cacheGetProperty(string $objectName, string $propertyName): array
    {
        $key = $this->getCacheKey($objectName);

        if (!isset(self::$_cache[$key][$propertyName])) {
            self::$_cache[$key][$propertyName] = [
                "getter" => null,
                "keyName" => null,
                "attributes" => []
            ];
        }

        return self::$_cache[$key][$propertyName];
    }

    protected function cacheSetGetter(string $objectName, string $propertyName, ?string $getter, ?string $keyName): void
    {
        $key = $this->getCacheKey($objectName);

        self::$_cache[$key][$propertyName]["getter"] = $getter;
        self::$_cache[$key][$propertyName]["keyName"] = $keyName;
    }

    protected function cacheSetAttributes(string $objectName, string $propertyName, object $attribute): void
    {
        $key = $this->getCacheKey($objectName);

        self::$_cache[$key][$propertyName]["attributes"][get_class($attribute)] = $attribute;
    }

    protected function cacheGetAttributes(string $objectName, string $propertyName, string $attribute): object|null
    {
        $key = $this->getCacheKey($objectName);

        return self::$_cache[$key][$propertyName]["attributes"][$attribute] ?? null;
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
        if (!isset(self::$_reflectionCache[$className])) {
            self::$_reflectionCache[$className] = new ReflectionClass($object);
        }
        $reflection = self::$_reflectionCache[$className];

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
        if (isset($this->ignorePropertiesMap[$propertyName])) {
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
                $simplePropertyName = lcfirst(substr($getter, strlen($this->methodGetPrefix)));
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
        foreach ($this->doNotParse as $class) {
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
        $this->currentLevel++;

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
        $this->currentLevel++;

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
        return $this->methodPattern[$key];
    }

    /**
     * @param $search
     * @param $replace
     * @return $this
     */
    public function withMethodPattern($search, $replace): static
    {
        $this->methodPattern = [$search, $replace];
        return $this;
    }

    /**
     * @return string
     */
    public function getMethodGetPrefix(): string
    {
        return $this->methodGetPrefix;
    }

    /**
     * @param string $methodGetPrefix
     * @return $this
     */
    public function withMethodGetPrefix(string $methodGetPrefix): static
    {
        $this->methodGetPrefix = $methodGetPrefix;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isOnlyString(): bool
    {
        return $this->onlyString;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function withOnlyString(bool $value = true): static
    {
        $this->onlyString = $value;
        return $this;
    }

    /**
     * @return array
     */
    public function getDoNotParse(): array
    {
        return $this->doNotParse;
    }

    /**
     * @param array $doNotParse
     * @return $this
     */
    public function withDoNotParse(array $doNotParse): static
    {
        $this->doNotParse = $doNotParse;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCopyingNullValues(): bool
    {
        return $this->serializeNull;
    }

    /**
     * @return $this
     */
    public function withDoNotParseNullValues(): static
    {
        $this->serializeNull = false;
        return $this;
    }

    public function withIgnoreProperties(array $properties): static
    {
        $this->ignoreProperties = $properties;
        $this->ignorePropertiesMap = array_flip($properties);
        return $this;
    }

    public function withoutIgnoreProperties(): static
    {
        $this->ignoreProperties = [];
        $this->ignorePropertiesMap = [];
        return $this;
    }

    protected function methodExists(object $object, string $method): bool
    {
        $className = get_class($object);
        $key = $className . '::' . $method;
        
        if (!isset(self::$_methodExistsCache[$key])) {
            self::$_methodExistsCache[$key] = method_exists($object, $method);
        }
        
        return self::$_methodExistsCache[$key];
    }
}
