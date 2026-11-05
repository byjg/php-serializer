<?php

namespace Tests\Sample;

use AllowDynamicProperties;

/**
 * A dynamic property exists only on the instance that has it
 */
#[AllowDynamicProperties]
class ModelDynamicTarget
{
    public $id;
}
