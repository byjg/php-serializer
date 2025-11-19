<?php

namespace ByJG\Serializer;

use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;

/**
 * Trait that implements methods for copying properties to and from objects
 */
trait ObjectCopyTrait
{
    /**
     * Copies properties from the source to this object
     * 
     * @param array|object $source The source object to copy from
     * @param PropertyHandlerInterface|null $propertyHandler The property handler
     */
    #[\Override]
    public function copyFrom(array|object $source, ?PropertyHandlerInterface $propertyHandler = null): void
    {
        ObjectCopy::copy($source, $this, $propertyHandler);
    }

    /**
     * Copies properties from this object to the target
     * 
     * @param array|object $target The target object to copy to
     * @param PropertyHandlerInterface|null $propertyHandler The property handler
     */
    #[\Override]
    public function copyTo(array|object $target, ?PropertyHandlerInterface $propertyHandler = null): void
    {
        ObjectCopy::copy($this, $target, $propertyHandler);
    }
} 