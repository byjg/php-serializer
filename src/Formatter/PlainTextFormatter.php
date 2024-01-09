<?php

namespace ByJG\Serializer\Formatter;

use ByJG\Serializer\Serialize;

class PlainTextFormatter implements FormatterInterface
{

    protected string $breakLine = "\n";
    protected string $startOfLine = "";
    protected bool $ignorePropertyName = true;

    /**
     * @param array|object $serializable
     * @return mixed
     */
    public function process(array|object $serializable): string
    {
        if (!is_array($serializable)) {
            return $this->processInternal(Serialize::from($serializable)->toArray());
        }

        return $this->processInternal($serializable);
        
    }

    protected function processInternal(array|object $serializable): string
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
