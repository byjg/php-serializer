<?php

namespace ByJG\Serializer\Formatter;

use ByJG\Serializer\Serialize;
use SimpleXMLElement;


class XmlFormatter implements FormatterInterface
{

    protected string $rootElement = "root";

    protected string $listElement = "item";

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
        $xml = $this->arrayToXml($array);

        return $xml->asXML();
    }

    /**
     * @param array $array
     * @param SimpleXMLElement|null $xml
     * @return SimpleXMLElement
     */
    protected function arrayToXml(array $array, ?SimpleXMLElement $xml = null): SimpleXMLElement
    {
        if ($xml === null) {
            $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><$this->rootElement></$this->rootElement>");
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subNode = $xml->addChild("$key");
                } else {
                    $subNode = $xml->addChild($this->listElement . ($this->listElementSuffix ? $key : ""));
                }
                $this->arrayToXml($value, $subNode);
            } else {
                $xml->addChild("$key", htmlspecialchars("$value"));
            }
        }

        return $xml;
    }
    /**
     * @param mixed $rootElement
     * @return XmlFormatter
     */
    public function withRootElement(string $rootElement): self
    {
        $this->rootElement = $rootElement;
        return $this;
    }

    /**
     * @param mixed $listElement
     * @return XmlFormatter
     */
    public function withListElement(string $listElement): self
    {
        $this->listElement = $listElement;
        return $this;
    }

    /**
     * @return XmlFormatter
     */
    public function withListElementSuffix(): self
    {
        $this->listElementSuffix = true;
        return $this;
    }
}
