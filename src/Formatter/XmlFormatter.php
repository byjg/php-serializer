<?php
/**
 * Created by PhpStorm.
 * User: jg
 * Date: 11/05/16
 * Time: 01:42
 */

namespace ByJG\Serializer\Formatter;
use ByJG\Serializer\SerializerObject;


class XmlFormatter implements FormatterInterface
{

    protected $rootElement = "root";

    protected $listElement = "item";

    protected $listElementSuffix = false;

    
    /**
     * @param array|object $serializable
     * @return mixed
     */
    public function process($serializable)
    {
        $array = $serializable;
        if (!is_array($serializable)) {
            $array = SerializerObject::instance($serializable)->serialize();
        }
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\"?><{$this->rootElement}></{$this->rootElement}>");
        $this->arrayToXml($array, $xml);

        return $xml->asXML();
    }

    /**
     * @param array $array
     * @param \SimpleXMLElement $xml
     */
    protected function arrayToXml($array, \SimpleXMLElement &$xml)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnode = $xml->addChild("$key");
                    $this->arrayToXml($value, $subnode);
                } else {
                    $subnode = $xml->addChild($this->listElement . ($this->listElementSuffix ? $key : ""));
                    $this->arrayToXml($value, $subnode);
                }
            } else {
                $xml->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }
	/**
	 * @param mixed $rootElement 
	 * @return XmlFormatter
	 */
	function withRootElement($rootElement) {
		$this->rootElement = $rootElement;
		return $this;
	}
	/**
	 * @param mixed $listElement 
	 * @return XmlFormatter
	 */
	function withListElement($listElement) {
		$this->listElement = $listElement;
		return $this;
	}
	/**
	 * @param mixed $listElementSuffix 
	 * @return XmlFormatter
	 */
	function withListElementSuffix() {
		$this->listElementSuffix = true;
		return $this;
	}
}
