<?php

namespace ByJG\Serializer\Formatter;

use ByJG\Serializer\Serialize;
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

        return Yaml::dump(Serialize::from($serializable)->toArray(), 2, 2);
    }
}
