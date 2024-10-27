<?php

namespace Tests\Sample;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SampleAttribute
{
    private ?string $elementName;

    public function __construct(?string $elementName = null)
    {
        $this->elementName = $elementName;
    }

    public function getElementName(): ?string
    {
        return $this->elementName;
    }
}