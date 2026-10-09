<?php

namespace ByJG\Serializer\PropertyHandler;

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
            static fn ($matches) => strtoupper($matches[1]),
            strtolower($property)
        );

        return $result ?? $property;
    }
}
