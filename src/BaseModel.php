<?php

namespace ByJG\Serializer;
use phpDocumentor\Reflection\DocBlock\Serializer;

abstract class BaseModel extends BindableObject
{

    /**
     * Construct a model and optionally can set (bind) your properties base and the attribute matching from SingleRow,
     * IteratorInterface
     *
     * @param Object $object
     * @param null $propertyPattern
     * @throws \ByJG\Serializer\Exception\InvalidArgumentException
     */
    public function __construct($object = null, $propertyPattern = null)
    {
        if (!is_null($object)) {
            $this->bindFrom($object, $propertyPattern);
        }
    }

    public function toArray()
    {
        return SerializerObject::instance($this)->serialize();
    }

}
