<?php

namespace ByJG\Serializer\PropertyHandler;

use Closure;

class CamelToSnakeCase implements PropertyHandlerInterface
{
    private ?Closure $valueHandler;

    /**
     * @param Closure|null $valueHandler Optional closure to handle value transformation
     */
    public function __construct(?Closure $valueHandler = null)
    {
        $this->valueHandler = $valueHandler;
    }

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
            function ($matches) {
                return $matches[1] . $matches[2] . '_' . strtolower($matches[3]);
            }, 
            $property
        );
        
        $result = preg_replace_callback(
            '/([a-z])([A-Z])/',
            function ($matches) {
                return $matches[1] . '_' . strtolower($matches[2]);
            },
            $result
        );
        
        return strtolower($result);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function transformValue(string $propertyName, string $targetName, mixed $value): mixed
    {
        if ($this->valueHandler !== null) {
            return ($this->valueHandler)($propertyName, $targetName, $value);
        }
        
        return $value;
    }
} 