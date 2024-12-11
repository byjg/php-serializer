<?php

namespace ByJG\Serializer\PropertyPattern;

interface PropertyPatternInterface
{
    public function map(string $sourcePropertyName): string|null;
}