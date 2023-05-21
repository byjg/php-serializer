<?php

namespace ByJG\Serializer\Formatter;

use ByJG\Serializer\SerializerObject;
use Symfony\Component\Yaml\Yaml;

class YamlFormatter implements FormatterInterface
{

    /**
     * @param array|object $serializable
     * @return string
     */
    public function process($serializable)
    {
        if (is_array($serializable)) {
            return Yaml::dump($serializable, 2, 2);
        }

        return Yaml::dump(SerializerObject::instance($serializable)->serialize(), 2, 2);
    }
}
