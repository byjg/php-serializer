<?php

namespace ByJG\Serializer;

use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;

abstract class BindableObject implements BindableInterface
{
    /**
     * @param array|object $source
     * @param PropertyPatternInterface|null $propertyPattern
     */
    public function bindFrom(array|object $source, ?PropertyPatternInterface $propertyPattern = null): void
    {
        BinderObject::bind($source, $this, $propertyPattern);
    }

    /**
     * @param array|object $target
     * @param PropertyPatternInterface|null $propertyPattern
     */
    public function bindTo(array|object $target, ?PropertyPatternInterface $propertyPattern = null): void
    {
        BinderObject::bind($this, $target, $propertyPattern);
    }
}
