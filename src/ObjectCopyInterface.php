<?php

namespace ByJG\Serializer;


use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;

interface ObjectCopyInterface
{
    public function copyFrom(array|object $source, ?PropertyPatternInterface $propertyPattern = null): void;

    public function copyTo(array|object $target, ?PropertyPatternInterface $propertyPattern = null): void;
}