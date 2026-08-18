<?php

namespace ByJG\Serializer\PropertyHandler;

class CamelToSnakeCase extends DirectTransform
{
    /**
     * @inheritDoc
     */
    #[\Override]
    public function mapName(string $property): string
    {
        // First, handle acronyms (like XMLHttpRequest)
        // Then handle camelCase
        $result = preg_replace_callback(
            '/(^|[a-z])([A-Z]+)([A-Z][a-z])/U',
            static fn ($matches) => $matches[1] . $matches[2] . '_' . strtolower($matches[3]),
            $property
        );

        $result = preg_replace_callback(
            '/([a-z])([A-Z])/',
            static fn ($matches) => $matches[1] . '_' . strtolower($matches[2]),
            $result ?? ''
        );

        return strtolower($result ?? '');
    }
}
