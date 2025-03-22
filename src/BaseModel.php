<?php

namespace ByJG\Serializer;

use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;

/**
 * Base model that implements the ObjectCopy functionality
 */
abstract class BaseModel extends ObjectCopy
{
    /**
     * Create a BaseModel that has inherited ObjectCopy and toArray() method
     * IteratorInterface
     *
     * @param array|object|null $object The source object to copy properties from
     * @param PropertyHandlerInterface|null $propertyHandler Property handling interface
     */
    public function __construct(array|object|null $object = null, ?PropertyHandlerInterface $propertyHandler = null)
    {
        if (!is_null($object)) {
            $this->copyFrom($object, $propertyHandler);
        }
    }

    /**
     * Convert the object to an array
     *
     * @return array The object as an array
     */
    public function toArray(): array
    {
        return Serialize::from($this)->toArray();
    }
}
