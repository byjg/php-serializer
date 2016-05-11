<?php
/**
 * Created by PhpStorm.
 * User: jg
 * Date: 11/05/16
 * Time: 01:40
 */

namespace ByJG\Serialize\Formatter;


interface FormatterInterface
{
    public function process($serializable);
}