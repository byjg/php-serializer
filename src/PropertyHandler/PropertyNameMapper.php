<?php

namespace ByJG\Serializer\PropertyHandler;

use Closure;

class PropertyNameMapper implements PropertyHandlerInterface
{
    protected array $mapFields;
    protected ?Closure $valueHandler;

    /**
     * @param array $mapFields Map of source property names to target property names
     * @param Closure|null $valueHandler Optional closure to handle value transformation
     */
    public function __construct(array $mapFields, ?Closure $valueHandler = null)
    {
        $this->mapFields = $mapFields;
        $this->valueHandler = $valueHandler;
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function mapName(string $property): string
    {
        return $this->mapFields[$property] ?? $property;
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