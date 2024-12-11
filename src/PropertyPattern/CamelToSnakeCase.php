<?php

namespace ByJG\Serializer\PropertyPattern;

class CamelToSnakeCase implements PropertyPatternInterface
{
    public function map(string $sourcePropertyName): string|null
    {
        return preg_replace_callback(
            '/([A-Z])/',
            function ($matches) {
                return '_' . strtolower($matches[1]);
            },
            $sourcePropertyName
        );
    }
}