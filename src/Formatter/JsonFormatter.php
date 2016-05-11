<?php
/**
 * Created by PhpStorm.
 * User: jg
 * Date: 11/05/16
 * Time: 01:41
 */

namespace ByJG\Serializer\Formatter;


class JsonFormatter implements FormatterInterface
{

    public function process($serializable)
    {
        return json_encode($serializable);
    }
}
