<?php

namespace Tests\Sample;

use ByJG\Serialize\BaseModel;

class ModelPublic extends BaseModel
{

    public $Id = "";
    public $Name = "";

    /**
     * ModelPublic constructor.
     * @param int $Id
     * @param string $Name
     */
    public function __construct($Id, $Name)
    {
        $this->Id = $Id;
        $this->Name = $Name;
    }
}
