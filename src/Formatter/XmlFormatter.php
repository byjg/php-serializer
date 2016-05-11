<?php
/**
 * Created by PhpStorm.
 * User: jg
 * Date: 11/05/16
 * Time: 01:42
 */

namespace ByJG\Serialize\Formatter;


class XmlFormatter implements FormatterInterface
{

    public function process($serializable)
    {
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\"?><root></root>");
        $this->arrayToXml($serializable, $xml);

        return $xml->asXML();
    }

    protected function arrayToXml($array, &$xml)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    $subnode = $xml->addChild("$key");
                    $this->arrayToXml($value, $subnode);
                } else {
                    $subnode = $xml->addChild("item$key");
                    $this->arrayToXml($value, $subnode);
                }
            } else {
                $xml->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }
}