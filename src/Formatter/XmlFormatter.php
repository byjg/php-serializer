<?php

namespace ByJG\Serializer\Formatter;

use ByJG\Serializer\Exception\InvalidArgumentException;
use ByJG\Serializer\Serialize;
use DOMNode;
use SimpleXMLElement;


/**
 * Converts between arrays and XML, in both directions, with the same conventions:
 *
 * - `@attributes` holds the attributes of an element, `@value` its text when it also has
 *   attributes or children;
 * - a list is written as repeated list elements (`<item>` by default) and read back as a
 *   list, so `toXml()` then `fromXml()` returns the same structure;
 * - the root element wraps the content and is not part of the array.
 */
class XmlFormatter implements FormatterInterface
{
    public const ATTRIBUTES = '@attributes';

    public const VALUE = '@value';

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
            if (is_numeric($key) && count($array) === 1) {
                return $array[$key] ?? '';
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

        $data = $this->xmlToArray($xml);
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
     * @return array|string The text of a leaf element, an array otherwise
     */
    protected function xmlToArray(SimpleXMLElement $node): array|string
    {
        $output = [];
        foreach ($node->attributes() ?? [] as $name => $value) {
            $output[self::ATTRIBUTES][(string)$name] = (string)$value;
        }

        $repeated = [];
        foreach ($node->children() ?? [] as $child) {
            $name = $child->getName();
            $data = $this->xmlToArray($child);

            if ($this->isListElement($name)) {
                $output[] = $data;
            } elseif (!array_key_exists($name, $output)) {
                $output[$name] = $data;
            } else {
                if (!isset($repeated[$name])) {
                    $output[$name] = [$output[$name]];
                    $repeated[$name] = true;
                }
                $output[$name][] = $data;
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
     * @return SimpleXMLElement
     */
    protected function arrayToXml(array $array, SimpleXMLElement $xml): SimpleXMLElement
    {
        foreach ($array as $key => $value) {
            if ($key === self::VALUE) {
                continue;   // already the text of $xml, written when it was created
            }
            if ($key === self::ATTRIBUTES && is_array($value)) {
                foreach ($value as $name => $attribute) {
                    $xml->addAttribute((string)$name, (string)$attribute);
                }
                continue;
            }

            $name = is_int($key)
                ? ($this->listElement ?? 'item') . ($this->listElementSuffix ? $key : "")
                : $key;

            if (is_array($value)) {
                $text = isset($value[self::VALUE]) ? htmlspecialchars((string)$value[self::VALUE]) : null;
                $child = $xml->addChild($name, $text)
                    ?? throw new InvalidArgumentException("Cannot add the element <$name>");
                $this->arrayToXml($value, $child);
            } else {
                $xml->addChild($name, htmlspecialchars("$value"));
            }
        }

        return $xml;
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
