<?php

namespace ByJG\Serializer;

use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;

/**
 * Base model that implements the ObjectCopyInterface functionality
 */
abstract class BaseModel implements ObjectCopyInterface
{
    use ObjectCopyTrait;
    
    /**
     * Create a BaseModel that implements ObjectCopyInterface and toArray() method
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
