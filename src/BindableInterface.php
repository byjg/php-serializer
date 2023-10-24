<?php

namespace ByJG\Serializer;


use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;

interface BindableInterface
{
    public function bindFrom($source, ?PropertyPatternInterface $propertyPattern = null);

    public function bindTo($target, ?PropertyPatternInterface $propertyPattern = null);
}