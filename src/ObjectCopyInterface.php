<?php

namespace ByJG\Serializer;

use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;

/**
 * Interface for objects that can copy properties to and from other objects
 */
interface ObjectCopyInterface
{
    /**
     * Copy properties from a source object to this object
     *
     * @param array|object $source The source object to copy properties from
     * @param PropertyHandlerInterface|null $propertyHandler Property handling interface for mapping names and values
     * @return void
     */
    public function copyFrom(array|object $source, ?PropertyHandlerInterface $propertyHandler = null): void;

    /**
     * Copy properties from this object to a target object
     *
     * @param array|object $target The target object to copy properties to
     * @param PropertyHandlerInterface|null $propertyHandler Property handling interface for mapping names and values
     * @return void
     */
    public function copyTo(array|object $target, ?PropertyHandlerInterface $propertyHandler = null): void;
}