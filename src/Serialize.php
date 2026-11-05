<?php

namespace ByJG\Serializer;

use ByJG\Serializer\Formatter\CsvFormatter;
use ByJG\Serializer\Formatter\JsonFormatter;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use ByJG\Serializer\Formatter\XmlFormatter;
use ByJG\Serializer\Formatter\YamlFormatter;
use ByJG\Serializer\Reader\CsvReader;
use ByJG\Serializer\Reader\FileBlocks;
use ByJG\Serializer\Reader\XmlBlockReader;
use BackedEnum;
use Closure;
use DOMDocument;
use DOMNode;
use Generator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use SimpleXMLElement;
use stdClass;
use Symfony\Component\Yaml\Yaml;

class Serialize
{
    private static array $_cache = [];
    private static array $_reflectionCache = [];
    private static array $_methodExistsCache = [];

    protected mixed $value = null;
    protected array $methodPattern = [
        self::METHOD_PATTERN_SEARCH_KEY => '/([^A-Za-z0-9])/',
        self::METHOD_PATTERN_REPLACE_KEY => ''
    ];
    protected string $methodGetPrefix = 'get';
    protected bool $stopAtFirstLevel = false;
    protected bool $onlyString = false;
    protected int $currentLevel = 0;
    protected array $doNotParse = [];
    protected bool $serializeNull = true;
    protected array $ignoreProperties = [];
    protected array $ignorePropertiesMap = [];
    protected array $onlyProperties = [];
    protected array $onlyPropertiesMap = [];
    /** The level of the properties withOnlyProperties() filters: the model's, or each item's for a list */
    private int $onlyPropertiesLevel = 2;

    private const string METHOD_PATTERN_SEARCH_KEY = "search";
    private const string METHOD_PATTERN_REPLACE_KEY = "replace";

    protected function __construct(mixed $value)
    {
        $this->value = $value;
    }

    public static function from(object|array $value): static
    {
        return new static($value);
    }

    /**
     * @throws \TypeError when the YAML is a scalar (a string, a number...): there is nothing to serialize
     * @throws \Symfony\Component\Yaml\Exception\ParseException when the content is not valid YAML
     */
    public static function fromYaml(string $content): static
    {
        return static::from(Yaml::parse($content));
    }

    /**
     * @throws \TypeError when the JSON is a scalar (a string, a number...): there is nothing to serialize
     * @throws \JsonException when the content is not valid JSON
     */
    public static function fromJson(string $content): static
    {
        return static::from(json_decode($content, true, flags: JSON_THROW_ON_ERROR));
    }

    /**
     * Create a Serialize instance from CSV content. A quoted value may contain line breaks (RFC 4180),
     * and the lines may end in LF or CRLF. An unclosed quote is not detected: fgetcsv() reads it to the end
     * of the content, which becomes part of that value.
     *
     * @param string $content CSV content
     * @param bool $hasHeader Whether the CSV has a header row (default: true)
     * @return static
     * @throws Exception\InvalidArgumentException when a record does not have as many fields as the header
     */
    public static function fromCsv(string $content, bool $hasHeader = true): static
    {
        $stream = fopen('php://memory', 'r+');
        if ($stream === false) {
            throw new \RuntimeException("Cannot open a memory stream to read the CSV");
        }
        fwrite($stream, trim($content));
        rewind($stream);

        try {
            return new static(iterator_to_array(CsvReader::records($stream, $hasHeader), false));
        } finally {
            fclose($stream);
        }
    }

    /**
     * Create a Serialize instance from a file, by its extension: .csv, .xml, .json, .yaml or .yml.
     *
     * A CSV or XML file is read block by block, each time it is used, so its content is never all in memory:
     * toIterator() returns one block at a time, and every to*() method works over all the blocks (as a list).
     * A block is a CSV record, keyed by the header row, or an XML element (see $blockElement). A block keeps
     * the value read (getValue()) and converts it only when a to*() method is called; toXml() of an XML
     * block writes the original element (see toXml()).
     *
     * PHP has no streaming parser for JSON or YAML: the file is read here, as fromJson() and fromYaml() do,
     * and its blocks are the items of a top-level list (see toIterator()).
     *
     * @param string $filename
     * @param string|null $blockElement XML only: the name of the elements that are the blocks, at any depth;
     *                                  null makes each child of the root element a block
     * @return static
     * @throws Exception\InvalidArgumentException when the file cannot be read, its extension is not one of
     *         those, or $blockElement is given for a file that is not XML
     */
    public static function fromFile(string $filename, ?string $blockElement = null): static
    {
        if (!is_file($filename) || !is_readable($filename)) {
            throw new Exception\InvalidArgumentException("Cannot read the file '$filename'");
        }

        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($blockElement !== null && $extension !== 'xml') {
            throw new Exception\InvalidArgumentException("The block element is for an XML file, not '$filename'");
        }

        return match ($extension) {
            'csv' => new static(new CsvReader($filename)),
            'xml' => new static(new XmlBlockReader($filename, $blockElement)),
            'json' => static::fromJson(self::readFile($filename)),
            'yaml', 'yml' => static::fromYaml(self::readFile($filename)),
            default => throw new Exception\InvalidArgumentException(
                "Cannot detect the format of '$filename': the extension must be csv, xml, json, yaml or yml"
            ),
        };
    }

    private static function readFile(string $filename): string
    {
        $content = file_get_contents($filename);
        if ($content === false) {
            throw new Exception\InvalidArgumentException("Cannot read the file '$filename'");
        }
        return $content;
    }

    /**
     * The blocks, one at a time, each a Serialize with the same options (withOnlyProperties()...).
     *
     * For fromFile() of a CSV or XML file, the blocks are read from the file. Otherwise they are the items of
     * the value when it is a list of arrays or objects, or else the value itself as a single block.
     *
     * @return Generator<int, static>
     */
    public function toIterator(): Generator
    {
        $blocks = $this->value instanceof FileBlocks ? $this->value : self::blocksOf($this->value);
        foreach ($blocks as $index => $value) {
            $block = clone $this;
            $block->value = $value;
            yield $index => $block;
        }
    }

    private static function blocksOf(mixed $value): array
    {
        return self::isListOfBlocks($value) ? $value : [$value];
    }

    /**
     * A list whose items are all arrays or objects: each item is a block
     */
    private static function isListOfBlocks(mixed $value): bool
    {
        if (!is_array($value) || !array_is_list($value)) {
            return false;
        }
        foreach ($value as $item) {
            if (!is_array($item) && !is_object($item)) {
                return false;
            }
        }
        return true;
    }

    /**
     * The original value, not converted: for a block of fromFile(), what the file gave (the DOMElement of an
     * XML block, the record of a CSV block); for the Serialize of a CSV or XML file, the list of the values of
     * all the blocks (all in memory); otherwise the value given to from*().
     */
    public function getValue(): mixed
    {
        if ($this->value instanceof FileBlocks) {
            return iterator_to_array($this->value, false);
        }
        return $this->value;
    }

    /**
     * The value parsed as an array; for the blocks of a file, the list of each block parsed. An XML element is
     * read as fromXml() reads it.
     */
    private function parseValue(?string $attributeClass = null, ?Closure $attributeFunction = null): mixed
    {
        if (!$this->value instanceof FileBlocks) {
            $value = $this->value instanceof DOMNode ? (new XmlFormatter())->parse($this->value) : $this->value;
            $this->onlyPropertiesLevel = self::isListOfBlocks($value) ? 3 : 2;
            return $this->parseProperties($value, 1, $attributeClass, $attributeFunction);
        }
        $result = [];
        foreach ($this->toIterator() as $block) {
            $result[] = $block->parseValue($attributeClass, $attributeFunction);
        }
        return $result;
    }

    /**
     * Create a Serialize instance from XML. The root element is not part of the data; an
     * element's attributes are under `@attributes`, and its text under `@value` when it also
     * has attributes or children. Every value is a string.
     *
     * @param string|SimpleXMLElement|DOMNode $content XML string, SimpleXMLElement or DOM node
     * @param string|null $listElement Elements with this name are list entries, as toXml() writes
     *                                 them; null reads only repeated siblings as a list
     * @param bool $listElementSuffix The list entries are numbered (item0, item1, ...)
     * @return static
     * @throws Exception\InvalidArgumentException When the string is not valid XML
     */
    public static function fromXml(
        string|SimpleXMLElement|DOMNode $content,
        ?string $listElement = 'item',
        bool $listElementSuffix = false
    ): static {
        return new static((new XmlFormatter())
            ->withListElement($listElement)
            ->withListElementSuffix($listElementSuffix)
            ->parse($content));
    }

    public static function fromPhpSerialize(string $content): static
    {
        return new static(unserialize($content));
    }

    /**
     * Build the array based on the object properties
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->parseValue();
    }

    /**
     * Convert the model to PHP serialized format
     *
     * @param bool $parse Whether to parse the model before serializing
     * @return string Serialized PHP string
     */
    public function toPhpSerialize(bool $parse = false): string
    {
        // A DOM element cannot be serialized: an XML block is serialized parsed
        if ($parse || $this->value instanceof DOMNode || $this->value instanceof XmlBlockReader) {
            return serialize($this->parseValue());
        }
        return serialize($this->getValue());
    }

    /**
     * Process the model with a formatter
     *
     * @param object $formatter The formatter to use
     * @return string|bool The formatted output
     */
    private function _processWithFormatter(object $formatter): string|bool
    {
        return $formatter->process($this->parseValue());
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
     * Convert the model to XML format. `@attributes` keys become attributes and `@value`
     * keys become the element's text.
     *
     * An XML block of fromFile() is written as its original element, without a conversion, which would lose
     * the order of the siblings, namespaced elements and mixed text: a block is that element, and an XML file
     * is its blocks inside a `root` element. With an option (withOnlyProperties()...) or a parameter, the
     * element is converted to an array first, so they apply.
     *
     * @param string $rootElement The element that wraps the content
     * @param string $listElement The element of each list entry
     * @param bool $listElementSuffix Number the list entries (item0, item1, ...)
     * @return string|bool XML representation of the model
     */
    public function toXml(string $rootElement = 'root', string $listElement = 'item', bool $listElementSuffix = false): string|bool
    {
        if ($rootElement === 'root' && $listElement === 'item' && !$listElementSuffix && $this->hasDefaultOptions()) {
            if ($this->value instanceof DOMNode) {
                return self::originalXml([$this->value], null);
            }
            if ($this->value instanceof XmlBlockReader) {
                return self::originalXml($this->value, $rootElement);
            }
        }

        return $this->_processWithFormatter((new XmlFormatter())
            ->withRootElement($rootElement)
            ->withListElement($listElement)
            ->withListElementSuffix($listElementSuffix));
    }

    /**
     * @param iterable<DOMNode> $nodes
     * @param string|null $rootElement The element around the nodes; null for a single node
     */
    private static function originalXml(iterable $nodes, ?string $rootElement): string|bool
    {
        $document = new DOMDocument('1.0');
        $parent = $rootElement === null ? $document : $document->appendChild($document->createElement($rootElement));
        foreach ($nodes as $node) {
            $parent->appendChild($document->importNode($node, true));
        }
        return $document->saveXML();
    }

    /**
     * No option changes the output
     */
    private function hasDefaultOptions(): bool
    {
        $default = new self(null);
        return $this->methodPattern === $default->methodPattern
            && $this->methodGetPrefix === $default->methodGetPrefix
            && $this->stopAtFirstLevel === $default->stopAtFirstLevel
            && $this->onlyString === $default->onlyString
            && $this->doNotParse === $default->doNotParse
            && $this->serializeNull === $default->serializeNull
            && $this->ignoreProperties === $default->ignoreProperties
            && $this->onlyProperties === $default->onlyProperties;
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

    /**
     * Convert the model to CSV format
     *
     * @return string|bool CSV representation of the model
     */
    public function toCsv(): string|bool
    {
        return $this->_processWithFormatter(new CsvFormatter());
    }

    public function parseAttributes(?Closure $attributeFunction, ?string $attributeClass = null): array
    {
        return $this->parseValue($attributeClass, $attributeFunction);
    }


    protected function parseProperties($property, $startLevel = null, ?string $attributeClass = null, ?Closure $attributeFunction = null): mixed
    {
        if (!empty($startLevel)) {
            $this->currentLevel = $startLevel;
        }

        // A backed enum is its value, as json_encode() writes it. Before the first-level check:
        // a model's enum property must reach the database (micro-orm saves at the first level) as
        // its value, not as the enum object.
        if ($property instanceof BackedEnum) {
            $property = $property->value;
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
        $onlyPropertiesMap = $this->onlyPropertiesMap;
        $hasOnlyProperties = $this->isFilteringOnlyProperties();

        foreach ($array as $key => $value) {
            if ($hasIgnoreProperties && isset($ignorePropertiesMap[$key])) {
                continue;
            }
            if ($hasOnlyProperties && !isset($onlyPropertiesMap[$key])) {
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

        $this->currentLevel--;
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
        return $objectName . '|' . $this->methodPattern[self::METHOD_PATTERN_SEARCH_KEY] . '|' . $this->methodPattern[self::METHOD_PATTERN_REPLACE_KEY];
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
                "attributes" => [],
                "reflection" => null
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

    protected function cacheSetReflection(string $objectName, string $propertyName, ReflectionProperty $property): void
    {
        $key = $this->getCacheKey($objectName);

        self::$_cache[$key][$propertyName]["reflection"] = $property;
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
        foreach ($reflection->getProperties() as $property) {
            $keyName = $property->getName();
            $getter = null;

            // Check if there's a getter method for this property
            $search = $this->getMethodSearchPattern();
            $replace = $this->getMethodReplacePattern();
            /** @var string $propertyNameForGetter */
            $propertyNameForGetter = preg_replace($search, $replace, $keyName);
            $getterMethod = $this->getMethodGetPrefix() . $propertyNameForGetter;

            if ($this->methodExists($object, $getterMethod)) {
                $getter = $getterMethod;
                $propertyName = $propertyNameForGetter;
            } else {
                // Skip non-public properties without getters
                if (!$property->isPublic()) {
                    continue;
                }
                $propertyName = $keyName;
            }

            $this->cacheSetGetter($className, $propertyName, getter: $getter, keyName: $keyName);

            // For direct (public, no getter) access we keep the ReflectionProperty so we can
            // check whether a typed property was initialized before reading it.
            if ($getter === null) {
                $this->cacheSetReflection($className, $propertyName, $property);
            }

            // Get property attributes
            $attributes = $property->getAttributes(null, ReflectionAttribute::IS_INSTANCEOF);
            foreach ($attributes as $attribute) {
                $newAttribute = $attribute->newInstance();
                $this->cacheSetAttributes($className, $propertyName, attribute: $newAttribute);
            }

            $this->setValue($result, $object, $propertyName, $this->cacheGetProperty($className, $propertyName), $attributeClass, $attributeFunction);
        }

        return [];  // Don't need to parse inside the object
    }

    // Once the properties are cached, we can get the array based on the cached properties
    protected function setValue(array &$result, object $object, string $propertyName, array $cachedProperty, ?string $attributeClass, ?Closure $attributeFunction): void
    {
        if ($this->isSkippingProperty($propertyName)) {
            return;
        }

        $getter = $cachedProperty["getter"];
        $keyName = $cachedProperty["keyName"];
        $objectClass = get_class($object);

        // Get property value using getter or direct access
        if (!empty($getter)) {
            $value = $object->$getter();
        } else {
            // A typed property that was never initialized has no value to read. Accessing it
            // directly would raise "must not be accessed before initialization", so we treat it
            // as absent and skip it (as if the property did not exist on the object).
            $reflection = $cachedProperty["reflection"] ?? null;
            if ($reflection instanceof ReflectionProperty && !$reflection->isInitialized($object)) {
                return;
            }
            $value = $object->$propertyName;
        }

        // Only look up attributes if there is a function and an attribute class
        $attribute = $attributeFunction !== null && $attributeClass !== null
            ? $this->cacheGetAttributes($objectClass, $propertyName, $attributeClass)
            : null;

        $this->addProperty($result, $propertyName, $value, $attributeFunction, $attribute, $keyName, $getter);
    }

    /**
     * A property of an object or a key of an array ignored, or not kept by withOnlyProperties()
     */
    private function isSkippingProperty(string $name): bool
    {
        return isset($this->ignorePropertiesMap[$name])
            || ($this->isFilteringOnlyProperties() && !isset($this->onlyPropertiesMap[$name]));
    }

    /**
     * Adds a property of an object to the result: parsed, through the function of parseAttributes(), and left
     * out when it is null and the null values are not kept
     */
    private function addProperty(
        array &$result,
        string $propertyName,
        mixed $value,
        ?Closure $attributeFunction,
        ?object $attribute,
        ?string $keyName,
        ?string $getter
    ): void {
        $parsedValue = $this->parseProperties($value);

        if ($attributeFunction !== null) {
            $parsedValue = $attributeFunction($attribute, $parsedValue, $keyName, $propertyName, $getter);
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

        $this->currentLevel--;
        return $result;
    }

    /**
     * withOnlyProperties() filters the properties of the model (of each item for a list), not the ones of
     * their values: a property kept is kept whole
     */
    private function isFilteringOnlyProperties(): bool
    {
        return !empty($this->onlyPropertiesMap) && $this->currentLevel === $this->onlyPropertiesLevel;
    }

    /**
     * Parse an anonymous class using its getter methods, then its public properties
     */
    protected function parseAnonymousClass(object $object, ?string $attributeClass = null, ?Closure $attributeFunction = null): array
    {
        $result = [];
        $this->currentLevel++;

        $reflectionClass = new ReflectionClass($object);
        $attributeOf = fn(string $name): ?object => $attributeFunction === null
            ? null
            : self::propertyAttribute($reflectionClass, $name, $attributeClass);

        // Getters take precedence over a public property with the same name
        $processedProperties = [];
        foreach ($this->anonymousGetters($object, $reflectionClass) as $propertyName => [$method, $keyName]) {
            $processedProperties[strtolower($propertyName)] = true;
            if (!$this->isSkippingProperty($propertyName)) {
                $this->addProperty($result, $propertyName, $object->$method(), $attributeFunction, $attributeOf($propertyName), $keyName, $method);
            }
        }

        foreach (get_object_vars($object) as $key => $value) {
            if (!isset($processedProperties[strtolower($key)]) && !$this->isSkippingProperty($key)) {
                $this->addProperty($result, $key, $value, $attributeFunction, $attributeOf($key), $key, null);
            }
        }

        $this->currentLevel--;
        return $result;
    }

    /**
     * The getters of an anonymous class by the name of their property, with the casing of the property when it
     * exists ("getId" is "id" for a property "id")
     *
     * @return array<string, array{0: string, 1: string|null}> The getter and the property, null when it has none
     */
    private function anonymousGetters(object $object, ReflectionClass $reflectionClass): array
    {
        $propertyMap = [];
        foreach ($reflectionClass->getProperties() as $property) {
            $propertyMap[strtolower($property->getName())] = $property->getName();
        }

        $prefix = $this->getMethodGetPrefix();
        $getters = [];
        foreach (get_class_methods($object) as $method) {
            if (!str_starts_with($method, $prefix) || strlen($method) <= strlen($prefix)) {
                continue;
            }
            /** @var non-empty-string $propertyKey longer than the prefix */
            $propertyKey = lcfirst(substr($method, strlen($prefix)));
            $keyName = $propertyMap[strtolower($propertyKey)] ?? null;
            $getters[$keyName ?? $propertyKey] = [$method, $keyName];
        }
        return $getters;
    }

    /**
     * The attribute of a property by its class name, as cacheGetAttributes() finds it for a class
     */
    private static function propertyAttribute(ReflectionClass $reflectionClass, string $name, ?string $attributeClass): ?object
    {
        if ($attributeClass === null || !$reflectionClass->hasProperty($name)) {
            return null;
        }
        foreach ($reflectionClass->getProperty($name)->getAttributes() as $attribute) {
            if ($attribute->getName() === $attributeClass) {
                return $attribute->newInstance();
            }
        }
        return null;
    }

    /**
     * @return non-empty-string
     */
    public function getMethodSearchPattern(): string
    {
        return $this->methodPattern[self::METHOD_PATTERN_SEARCH_KEY];
    }

    public function getMethodReplacePattern():string
    {
        return $this->methodPattern[self::METHOD_PATTERN_REPLACE_KEY];
    }

    /**
     * @param string $search
     * @param string $replace
     * @return $this
     */
    public function withMethodPattern(string $search, string $replace): static
    {
        if (strlen($search) < 2) {
            throw new \InvalidArgumentException("Search pattern cannot be empty or less than 2 characters");
        }

        if ($search[0] !== $search[strlen($search) - 1]) {
            throw new \InvalidArgumentException("Search pattern must be enclosed in matching delimiters");
        }

        $this->methodPattern = [
            self::METHOD_PATTERN_SEARCH_KEY => $search,
            self::METHOD_PATTERN_REPLACE_KEY => $replace
        ];
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

    public function withOnlyProperties(array $properties): static
    {
        $this->onlyProperties = $properties;
        $this->onlyPropertiesMap = array_flip($properties);
        return $this;
    }

    public function withoutOnlyProperties(): static
    {
        $this->onlyProperties = [];
        $this->onlyPropertiesMap = [];
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
