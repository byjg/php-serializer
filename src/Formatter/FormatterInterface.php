<?php

namespace ByJG\Serializer\Formatter;

interface FormatterInterface
{
    /**
     * @param array|object $serializable
     * @return string
     */
    public function process($serializable);
}
