<?php

namespace ByJG\Serializer;


use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;

interface BindableInterface
{
    public function bindFrom(array|object $source, ?PropertyPatternInterface $propertyPattern = null): void;

    public function bindTo(array|object $target, ?PropertyPatternInterface $propertyPattern = null): void;
}