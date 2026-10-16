<?php

namespace Tests\Sample;

class ModelEnum
{
    public ?SampleStatus $status = null;
    public SampleLevel $level = SampleLevel::Low;
    public string|SampleStatus|null $loose = null;
    public ?SampleSuit $suit = null;
    protected ?SampleStatus $previous = null;

    public function getPrevious(): ?SampleStatus
    {
        return $this->previous;
    }

    public function setPrevious(?SampleStatus $previous): void
    {
        $this->previous = $previous;
    }
}
