<?php

namespace ByJG\Serializer\Formatter;

use ByJG\Serializer\SerializerObject;

class JsonFormatter implements FormatterInterface
{

    /**
     * @param array|object $serializable
     * @return string
     */
    public function process($serializable)
    {
        if (is_array($serializable)) {
            return json_encode($serializable);
        }

        return json_encode(SerializerObject::instance($serializable)->serialize());
    }
}
