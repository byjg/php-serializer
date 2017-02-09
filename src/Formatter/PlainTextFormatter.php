<?php
/**
 * Created by PhpStorm.
 * User: jg
 * Date: 11/05/16
 * Time: 01:42
 */

namespace ByJG\Serializer\Formatter;


class PlainTextFormatter implements FormatterInterface
{

    protected $breakLine = "\n";
    protected $startOfLine = "";

    /**
     * PlainTextFormatter constructor.
     *
     * @param string $breakLine
     * @param string $startOfLine
     */
    public function __construct($breakLine = "\n", $startOfLine = "")
    {
        $this->breakLine = $breakLine;
        $this->startOfLine = $startOfLine;
    }


    /**
     * @param array $serializable
     * @return mixed
     */
    public function process($serializable)
    {
        $result = "";
        foreach ($serializable as $value) {
            $result .= $this->startOfLine;
            if (is_array($value)) {
                $result .= $this->process($value);
            } else {
                $result .= $value;
            }
            $result .= $this->breakLine;
        }
        return $result;
    }

}
