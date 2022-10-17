<?php

namespace ByJG\Serializer\Formatter;

use ByJG\Serializer\SerializerObject;

class PlainTextFormatter implements FormatterInterface
{

    protected $breakLine = "\n";
    protected $startOfLine = "";
    protected $ignorePropertyName = true;

    /**
     * @param array $serializable
     * @return mixed
     */
    public function process($serializable)
    {
        if (!is_array($serializable)) {
            return $this->processInternal(SerializerObject::instance($serializable)->serialize());
        }

        return $this->processInternal($serializable);
        
    }

    protected function processInternal($serializable)
    {
        $result = "";

        foreach ($serializable as $key => $value) {
            $result .= $this->startOfLine;
            if (is_array($value)) {
                $result .= $this->processInternal($value);
            } else {
                $result .= (!$this->ignorePropertyName ? "$key=" : "") . $value;
            }
            $result .= $this->breakLine;
        }
        return $result;
    }

    /**
     * @param mixed $breakLine
     * @return PlainTextFormatter
     */
    public function withBreakLine($breakLine)
    {
        $this->breakLine = $breakLine;
        return $this;
    }

    /**
     * @param mixed $startOfLine
     * @return PlainTextFormatter
     */
    public function withStartOfLine($startOfLine)
    {
        $this->startOfLine = $startOfLine;
        return $this;
    }

    /**
     * @param mixed $ignorePropertyName
     * @return PlainTextFormatter
     */
    public function withIgnorePropertyName($ignorePropertyName)
    {
        $this->ignorePropertyName = $ignorePropertyName;
        return $this;
    }
}
