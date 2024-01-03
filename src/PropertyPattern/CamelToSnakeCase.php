<?php

namespace ByJG\Serializer\PropertyPattern;

use Closure;

class CamelToSnakeCase implements PropertyPatternInterface
{

    public function getRegEx(): string
    {
        return '/([A-Z])/';
    }

    public function getCallback(): ?Closure
    {
        return function ($matches) {
            return '_' . strtolower($matches[1]);
        };
    }

    public function getReplacement(): ?string
    {
        return null;
    }
}