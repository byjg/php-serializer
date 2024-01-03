<?php

namespace ByJG\Serializer\PropertyPattern;

use Closure;

interface PropertyPatternInterface
{
    public function getRegEx(): string;

    public function getCallback(): ?Closure;

    public function getReplacement(): ?string;
}