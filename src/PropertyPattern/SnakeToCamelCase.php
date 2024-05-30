<?php

namespace ByJG\Serializer\PropertyPattern;

use Closure;

class SnakeToCamelCase implements PropertyPatternInterface
{

    public function getRegEx(): string
    {
        return '/_([a-z])/i';
    }

    public function getCallback(): ?Closure
    {
        return function ($matches) {
            return strtoupper($matches[1]);
        };
    }

    public function getReplacement(): ?string
    {
        return null;
    }

    public function prepare($value)
    {
        return strtolower($value);
    }
}