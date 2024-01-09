<?php

namespace ByJG\Serializer\Formatter;

use ByJG\Serializer\SerializerObject;
use Symfony\Component\Yaml\Yaml;

class YamlFormatter implements FormatterInterface
{

    /**
     * @param object|array $serializable
     * @return string
     */
    public function process(object|array $serializable): string
    {
        if (is_array($serializable)) {
            return Yaml::dump($serializable, 2, 2);
        }

        return Yaml::dump(SerializerObject::instance($serializable)->serialize(), 2, 2);
    }
}
