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
     * @return string|bool
     */
    public function process(array|object $serializable): string|bool
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
     * @param string $breakLine
     * @return $this
     */
    public function withBreakLine(string $breakLine): static
    {
        $this->breakLine = $breakLine;
        return $this;
    }

    /**
     * @param string $startOfLine
     * @return $this
     */
    public function withStartOfLine(string $startOfLine): static
    {
        $this->startOfLine = $startOfLine;
        return $this;
    }

    /**
     * @param bool $ignorePropertyName
     * @return $this
     */
    public function withIgnorePropertyName(bool $ignorePropertyName): static
    {
        $this->ignorePropertyName = $ignorePropertyName;
        return $this;
    }
}
