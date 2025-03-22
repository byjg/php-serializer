<?php

namespace ByJG\Serializer\PropertyHandler;

use Closure;

class SnakeToCamelCase implements PropertyHandlerInterface
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
        $result = preg_replace_callback(
            '/_([a-z])/i',
            function ($matches) {
                return strtoupper($matches[1]);
            },
            strtolower($property)
        );
        
        return $result !== null ? $result : $property;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function changeValue(string $propertyName, string $targetName, mixed $value): mixed
    {
        if ($this->valueHandler !== null) {
            return ($this->valueHandler)($propertyName, $targetName, $value);
        }
        
        return $value;
    }
} 