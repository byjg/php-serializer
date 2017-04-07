<?php

namespace Tests\Sample;

use ByJG\Serializer\BaseModel;

class ModelPropertyPatternConstruct extends BaseModel
{

    protected $_birth_date = "";

    function __construct($object = null)
    {
        parent::__construct($object);
    }


    public function getBirth_date()
    {
        return $this->_birth_date;
    }

    public function setBirth_date($birth_date)
    {
        $this->_birth_date = $birth_date;
    }


}
