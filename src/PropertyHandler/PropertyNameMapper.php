<?php

namespace ByJG\Serializer\PropertyHandler;

use Closure;

class PropertyNameMapper extends DirectTransform
{
    /**
     * @param array<string, string> $mapFields Map of source property names to target property names
     * @param Closure|null $valueHandler Optional closure to handle value transformation
     */
    public function __construct(protected array $mapFields, ?Closure $valueHandler = null)
    {
        parent::__construct($valueHandler);
    }

    /**
     * @inheritDoc
     */
    #[\Override]
    public function mapName(string $property): string
    {
        return $this->mapFields[$property] ?? $property;
    }
}