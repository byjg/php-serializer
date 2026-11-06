<?php

namespace Tests\Sample;

/**
 * Targets for how ObjectCopy writes a value: setter, public property (any case), protected property, enum
 */
class ModelWriteTargets
{
    public $Exact = 'default';
    public $lower;
    public $both;
    public ?SampleStatus $status = null;
    protected $hidden = 'hidden';
    protected $viaSetter;
    protected ?SampleLevel $level = null;

    public function setBoth($value): void
    {
        $this->both = "setter:$value";
    }

    public function setViaSetter($value): void
    {
        $this->viaSetter = $value;
    }

    public function getViaSetter()
    {
        return $this->viaSetter;
    }

    public function getHidden()
    {
        return $this->hidden;
    }

    public function setLevel(?SampleLevel $level): void
    {
        $this->level = $level;
    }

    public function getLevel(): ?SampleLevel
    {
        return $this->level;
    }
}
