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
        $result = preg_replace_callback(
            '/([A-Z])/',
            function ($matches) {
                return '_' . strtolower($matches[1]);
            },
            $property
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