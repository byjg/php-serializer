<?php

namespace ByJG\Serializer\PropertyHandler;

use Closure;

class SnakeToCamelCase extends DirectTransform
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public function mapName(string $property): string
    {
        $result = preg_replace_callback(
            '/_([a-z])/i',
            function ($matches) {
                return strtoupper($matches[1]);
            },
            strtolower($property)
        );
        
        return $result !== null ? $result : $property;
    }
}
