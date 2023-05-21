<?php

namespace ByJG\Serializer;


interface BindableInterface
{
    public function bindFrom($source, $propertyPattern = null);

    public function bindTo($target, $propertyPattern = null);
}