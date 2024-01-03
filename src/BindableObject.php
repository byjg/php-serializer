<?php

namespace ByJG\Serializer;

use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;

abstract class BindableObject implements BindableInterface
{
    public function bindFrom($source, ?PropertyPatternInterface $propertyPattern = null)
    {
        BinderObject::bind($source, $this, $propertyPattern);
    }

    public function bindTo($target, ?PropertyPatternInterface $propertyPattern = null)
    {
        BinderObject::bind($this, $target, $propertyPattern);
    }
}
