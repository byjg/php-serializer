<?php

namespace ByJG\Serializer\PropertyPattern;

class DifferentTargetProperty implements PropertyPatternInterface
{
    protected array $mapFields;

    public function __construct(array $mapFields)
    {
        $this->mapFields = $mapFields;
    }

    public function map(string $sourcePropertyName): string|null
    {
        return $this->mapFields[$sourcePropertyName] ?? $sourcePropertyName;
    }
}