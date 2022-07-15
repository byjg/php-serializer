<?php
/**
 * Created by PhpStorm.
 * User: jg
 * Date: 11/05/16
 * Time: 01:41
 */

namespace ByJG\Serializer\Formatter;
use Serializable;
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
