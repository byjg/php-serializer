<?php

namespace ByJG\Serializer\Formatter;

use ByJG\Serializer\SerializerObject;

class JsonFormatter implements FormatterInterface
{

    /**
     * @param object|array $serializable
     * @return string
     */
    public function process(object|array $serializable): string
    {
        if (is_array($serializable)) {
            return json_encode($serializable);
        }

        return json_encode(SerializerObject::instance($serializable)->toArray());
    }
}
