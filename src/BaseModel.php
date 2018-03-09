<?php

namespace ByJG\Serializer;

abstract class BaseModel extends BinderObject
{

    /**
     * Construct a model and optionally can set (bind) your properties base and the attribute matching from SingleRow,
     * IteratorInterface
     *
     * @param Object $object
     * @param null $propertyPattern
     * @throws \Exception
     */
    public function __construct($object = null, $propertyPattern = null)
    {
        if (!is_null($object)) {
            $this->bind($object, $propertyPattern);
        }
    }

}
