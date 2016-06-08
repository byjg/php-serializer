<?php
/**
 * Created by PhpStorm.
 * User: jg
 * Date: 11/05/16
 * Time: 01:40
 */

namespace ByJG\Serializer\Formatter;


interface FormatterInterface
{
    /**
     * @param array $serializable
     * @return string
     */
    public function process($serializable);
}
