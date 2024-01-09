<?php

namespace ByJG\Serializer;

use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;

abstract class BaseModel extends BindableObject
{

    /**
     * Construct a model and optionally can set (bind) your properties base and the attribute matching from SingleRow,
     * IteratorInterface
     *
     * @param null $object
     * @param PropertyPatternInterface|null $propertyPattern
     */
    public function __construct($object = null, ?PropertyPatternInterface $propertyPattern = null)
    {
        if (!is_null($object)) {
            $this->bindFrom($object, $propertyPattern);
        }
    }

    public function toArray(): array
    {
        return SerializerObject::instance($this)->serialize();
    }

}
