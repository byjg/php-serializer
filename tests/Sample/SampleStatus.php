<?php

namespace Tests\Sample;

enum SampleStatus: string
{
    case Paid = 'PAID';
    case Failed = 'FAILED';
}
