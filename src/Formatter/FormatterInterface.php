<?php

namespace ByJG\Serializer\Formatter;

interface FormatterInterface
{
    /**
     * @param array|object $serializable
     * @return string|bool
     */
    public function process(array|object $serializable): string|bool;
}
