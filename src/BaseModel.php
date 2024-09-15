<?php

namespace ByJG\Serializer;

use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;

abstract class BaseModel extends ObjectCopy
{

    /**
     * Create a BaseModel that has inherited ObjectCopy and toArray() method
     * IteratorInterface
     *
     * @param array|object|null $object
     * @param PropertyPatternInterface|null $propertyPattern
     */
    public function __construct(array|object|null $object = null, ?PropertyPatternInterface $propertyPattern = null)
    {
        if (!is_null($object)) {
            $this->copyFrom($object, $propertyPattern);
        }
    }

    public function toArray(): array
    {
        return Serialize::from($this)->toArray();
    }

}
