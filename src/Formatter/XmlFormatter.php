<?php

namespace ByJG\Serializer\Formatter;

use ByJG\Serializer\Exception\InvalidArgumentException;
use ByJG\Serializer\Serialize;
use DOMElement;
use DOMNode;
use SimpleXMLElement;


/**
 * Converts between arrays and XML, in both directions, with the same conventions:
 *
 * - `@attributes` holds the attributes of an element, `@value` its text when it also has
 *   attributes or children;
 * - a list is written as repeated list elements (`<item>` by default) and read back as a
 *   list, so `toXml()` then `fromXml()` returns the same structure;
 * - the root element wraps the content and is not part of the array;
 * - an element or attribute in a namespace keeps its prefix (`x:isbn`, `xsi:type`), and a namespace
 *   declaration is an attribute, as in XML (`xmlns:x`, `xmlns` for the default namespace), so toXml()
 *   writes the namespaces back.
 */
class XmlFormatter implements FormatterInterface
{
    public const string ATTRIBUTES = '@attributes';

    public const string VALUE = '@value';

    private const string XMLNS = 'http://www.w3.org/2000/xmlns/';

    private const string XML = 'http://www.w3.org/XML/1998/namespace';

    protected string $rootElement = "root";

    protected ?string $listElement = "item";

    protected bool $listElementSuffix = false;


    /**
     * @param object|array $serializable
     * @return string|bool
     */
    #[\Override]
    public function process(object|array $serializable): string|bool
    {
        $array = $serializable;
        if (is_array($array)) {
            $key = array_key_first($array);
            // A single scalar is its text; a single array or object is a list item, as in a longer list
            $value = $key === null ? null : $array[$key];
            if (is_numeric($key) && count($array) === 1 && (is_scalar($value) || $value === null)) {
                return is_bool($value) ? $value : (string)$value;
            } elseif (empty($array)) {
                return '';
            }
        } else {
            $array = Serialize::from($serializable)->toArray();
        }

        $text = isset($array[self::VALUE]) ? htmlspecialchars((string)$array[self::VALUE]) : '';
        $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><$this->rootElement>$text</$this->rootElement>");

        return $this->arrayToXml($array, $xml)->asXML();
    }

    /**
     * Read XML into an array. Every value is a string: XML has no types.
     *
     * Entities are never expanded and nothing is fetched from the network, so an external
     * entity (XXE) stays an unresolved reference.
     *
     * @param string|SimpleXMLElement|DOMNode $content
     * @param bool $withRoot Keep the root element as the single key of the result
     * @return array
     * @throws InvalidArgumentException
     */
    public function parse(string|SimpleXMLElement|DOMNode $content, bool $withRoot = false): array
    {
        $xml = match (true) {
            $content instanceof SimpleXMLElement => $content,
            $content instanceof DOMNode => simplexml_import_dom($content),
            default => $this->loadString($content),
        };
        if ($xml === null) {
            throw new InvalidArgumentException('The DOM node cannot be converted to XML');
        }

        $data = $this->xmlToArray($xml, true);
        if ($withRoot) {
            return [$xml->getName() => $data];
        }
        if (is_string($data)) {
            return $data === '' ? [] : [self::VALUE => $data];
        }

        return $data;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function loadString(string $content): SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string($content, SimpleXMLElement::class, LIBXML_NOCDATA | LIBXML_NONET);
            $error = libxml_get_last_error();
            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors($previous);
        }

        if ($xml === false) {
            throw new InvalidArgumentException('Invalid XML: ' . ($error === false ? 'empty content' : trim($error->message)));
        }

        return $xml;
    }

    /**
     * @param SimpleXMLElement $node
     * @param bool $top The element read: it also gets the namespaces declared on its parents, so the
     *                  result is complete on its own (an element of a bigger document)
     * @return array|string The text of a leaf element, an array otherwise
     * @throws InvalidArgumentException
     */
    protected function xmlToArray(SimpleXMLElement $node, bool $top = false): array|string
    {
        $output = [];
        foreach (self::namespaceDeclarations($node, $top) as $name => $uri) {
            $output[self::ATTRIBUTES][$name] = $uri;
        }

        // DOM, not SimpleXML: SimpleXML lists only the attributes and children without a namespace
        $element = dom_import_simplexml($node);
        foreach ($element->attributes ?? [] as $attribute) {
            $output[self::ATTRIBUTES][$attribute->nodeName] = $attribute->value;
        }

        $repeated = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $this->readChild($output, $repeated, $child);
            }
        }

        $text = trim((string)$node);
        if ($output === []) {
            return $text;
        }
        if ($text !== '') {
            $output[self::VALUE] = $text;
        }

        return $output;
    }

    /**
     * The namespaces declared on an element, as the attributes that declare them (`xmlns`, `xmlns:x`)
     *
     * @param bool $top Also the namespaces used in the element but declared on a parent, not inside it
     * @return array<string, string>
     */
    private static function namespaceDeclarations(SimpleXMLElement $node, bool $top): array
    {
        $declared = $node->getDocNamespaces(false, false);
        $declared = $declared === false ? [] : $declared;
        if ($top) {
            $inside = $node->getDocNamespaces(true, false);
            $declared += array_diff_key($node->getNamespaces(true), $inside === false ? [] : $inside);
        }

        $attributes = [];
        foreach ($declared as $prefix => $uri) {
            if ($prefix !== 'xml') {
                $attributes[$prefix === '' ? 'xmlns' : "xmlns:$prefix"] = $uri;
            }
        }
        return $attributes;
    }

    /**
     * Adds a child element to the output: as a list entry, by its name, or into a list when the name repeats
     *
     * @param array<string, true> $repeated The names already turned into a list
     * @throws InvalidArgumentException
     */
    private function readChild(array &$output, array &$repeated, DOMElement $child): void
    {
        $name = $child->nodeName;
        $data = $this->xmlToArray(simplexml_import_dom($child)
            ?? throw new InvalidArgumentException("The element <$name> cannot be read"));

        if ($this->isListElement($name)) {
            $output[] = $data;
            return;
        }
        if (!array_key_exists($name, $output)) {
            $output[$name] = $data;
            return;
        }
        if (!isset($repeated[$name])) {
            $output[$name] = [$output[$name]];
            $repeated[$name] = true;
        }
        $output[$name][] = $data;
    }

    protected function isListElement(string $name): bool
    {
        if ($this->listElement === null) {
            return false;
        }
        if ($name === $this->listElement) {
            return true;
        }

        return $this->listElementSuffix
            && preg_match('/^' . preg_quote($this->listElement, '/') . '\d+$/', $name) === 1;
    }

    /**
     * @param array $array
     * @param SimpleXMLElement $xml
     * @param array<string, string> $namespaces The namespaces declared on the parents, by prefix ('' is the default)
     * @return SimpleXMLElement
     * @throws InvalidArgumentException when a prefix has no namespace declaration
     */
    protected function arrayToXml(array $array, SimpleXMLElement $xml, array $namespaces = []): SimpleXMLElement
    {
        $attributes = is_array($array[self::ATTRIBUTES] ?? null) ? $array[self::ATTRIBUTES] : [];
        // The declarations first: the attributes and children of this element may use them
        $namespaces = $this->declareNamespaces($attributes, $xml, $namespaces);
        $this->writeAttributes($attributes, $xml, $namespaces);

        foreach ($array as $key => $value) {
            // @value is already the text of $xml, written when it was created
            if ($key === self::VALUE || ($key === self::ATTRIBUTES && is_array($value))) {
                continue;
            }
            $this->writeChild($key, $value, $xml, $namespaces);
        }

        return $xml;
    }

    private static function isNamespaceDeclaration(string $name): bool
    {
        return $name === 'xmlns' || str_starts_with($name, 'xmlns:');
    }

    /**
     * Writes the namespace declarations among the attributes on the element
     *
     * @param array<string, string> $namespaces The namespaces declared on the parents
     * @return array<string, string> The namespaces with the ones declared on the element
     */
    private function declareNamespaces(array $attributes, SimpleXMLElement $xml, array $namespaces): array
    {
        foreach ($attributes as $name => $uri) {
            $name = (string)$name;
            if (self::isNamespaceDeclaration($name)) {
                dom_import_simplexml($xml)->setAttributeNS(self::XMLNS, $name, (string)$uri);
                $namespaces[$name === 'xmlns' ? '' : substr($name, 6)] = (string)$uri;
            }
        }
        return $namespaces;
    }

    /**
     * @param array<string, string> $namespaces
     * @throws InvalidArgumentException when a prefix has no namespace declaration
     */
    private function writeAttributes(array $attributes, SimpleXMLElement $xml, array $namespaces): void
    {
        foreach ($attributes as $name => $attribute) {
            $name = (string)$name;
            if (self::isNamespaceDeclaration($name)) {
                continue;
            }
            $uri = str_contains($name, ':') ? $this->prefixNamespace($name, $namespaces) : null;
            $xml->addAttribute($name, (string)$attribute, $uri);
        }
    }

    /**
     * @param array<string, string> $namespaces
     * @throws InvalidArgumentException when a prefix has no namespace declaration
     */
    private function writeChild(int|string $key, mixed $value, SimpleXMLElement $xml, array $namespaces): void
    {
        $name = is_int($key)
            ? ($this->listElement ?? 'item') . ($this->listElementSuffix ? $key : "")
            : $key;
        $uri = str_contains($name, ':') ? $this->prefixNamespace($name, $namespaces) : ($namespaces[''] ?? null);

        if (!is_array($value)) {
            $xml->addChild($name, htmlspecialchars("$value"), $uri);
            return;
        }
        $text = isset($value[self::VALUE]) ? htmlspecialchars((string)$value[self::VALUE]) : null;
        $child = $xml->addChild($name, $text, $uri)
            ?? throw new InvalidArgumentException("Cannot add the element <$name>");
        $this->arrayToXml($value, $child, $namespaces);
    }

    /**
     * The namespace of a prefixed name (`x:isbn`), declared on this element or a parent
     *
     * @param array<string, string> $namespaces
     * @throws InvalidArgumentException when the prefix has no declaration: the XML would not be valid
     */
    private function prefixNamespace(string $name, array $namespaces): string
    {
        $prefix = (string)strstr($name, ':', true);
        if ($prefix === 'xml') {
            return self::XML;
        }
        return $namespaces[$prefix]
            ?? throw new InvalidArgumentException("The prefix '$prefix' of '$name' has no namespace declaration (xmlns:$prefix)");
    }

    /**
     * @param string $rootElement
     * @return XmlFormatter
     */
    public function withRootElement(string $rootElement): self
    {
        $this->rootElement = $rootElement;
        return $this;
    }

    /**
     * @param string|null $listElement The element of a list entry. With null, parse() reads
     *                                 only repeated siblings as a list.
     * @return XmlFormatter
     */
    public function withListElement(?string $listElement): self
    {
        $this->listElement = $listElement;
        return $this;
    }

    /**
     * @return XmlFormatter
     */
    public function withListElementSuffix(bool $suffix = true): self
    {
        $this->listElementSuffix = $suffix;
        return $this;
    }
}
