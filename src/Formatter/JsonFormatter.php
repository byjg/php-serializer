<?php

namespace ByJG\Serializer\Formatter;

use ByJG\Serializer\Serialize;

class JsonFormatter implements FormatterInterface
{

    /**
     * @param object|array $serializable
     * @return string|bool
     */
    public function process(object|array $serializable): string|bool
    {
        if (is_array($serializable)) {
            return json_encode($serializable);
        }

        return json_encode(Serialize::from($serializable)->toArray());
    }
}
