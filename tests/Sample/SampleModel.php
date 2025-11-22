<?php

namespace Tests\Sample;

use ByJG\Serializer\BaseModel;

class SampleModel extends BaseModel
{

    public string $Id = "";
    protected $_Name = "";

    public function __construct($object = null)
    {
        parent::__construct($object);
    }

    public function getName()
    {
        return $this->_Name;
    }

    public function setName($Name): void
    {
        $this->_Name = $Name;
    }
}
