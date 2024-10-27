<?php

namespace ByJG\Serializer;


use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;
use Closure;

interface ObjectCopyInterface
{
    public function copyFrom(array|object $source, PropertyPatternInterface|\Closure|null $propertyPattern = null): void;

    public function copyTo(array|object $target, PropertyPatternInterface|Closure|null $propertyPattern = null): void;
}