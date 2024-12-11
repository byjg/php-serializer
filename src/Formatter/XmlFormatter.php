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
    public function process(object|array $serializable): string|bool
    {
        $array = $serializable;
        if (!is_array($serializable)) {
            $array = Serialize::from($serializable)->toArray();
        }
        $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><$this->rootElement></$this->rootElement>");
        $this->arrayToXml($array, $xml);

        return $xml->asXML();
    }

    /**
     * @param array $array
     * @param SimpleXMLElement $xml
     */
    protected function arrayToXml(array $array, SimpleXMLElement $xml): void
    {
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
