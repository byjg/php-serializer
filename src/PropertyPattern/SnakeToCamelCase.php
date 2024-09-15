<?php

namespace ByJG\Serializer\PropertyPattern;

class SnakeToCamelCase implements PropertyPatternInterface
{
    public function map(string $sourcePropertyName): string
    {
        return preg_replace_callback(
            '/_([a-z])/i',
            function ($matches) {
                return strtoupper($matches[1]);
            },
            strtolower($sourcePropertyName)
        );
    }
}