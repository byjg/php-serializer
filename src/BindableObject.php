<?php

namespace ByJG\Serializer;

use ByJG\Serializer\Exception\InvalidArgumentException;
use stdClass;
use PhpParser\Node\Expr\BinaryOp;

abstract class BindableObject implements BindableInterface
{
    public function bindFrom($source, $propertyPattern = null)
    {
        BinderObject::bind($source, $this, $propertyPattern);
    }

    public function bindTo($target, $propertyPattern = null)
    {
        BinderObject::bind($this, $target, $propertyPattern);
    }
}
