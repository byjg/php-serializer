<?php

namespace Tests\Sample;

use ByJG\Serializer\BaseModel;

class ModelGetter extends BaseModel
{

    protected $_Id = "";
    protected $_Name = "";

    /**
     * ModelGetter constructor.
     * @param int $Id
     * @param string $Name
     */
    public function __construct($Id, $Name)
    {
        $this->_Id = $Id;
        $this->_Name = $Name;
    }

    public function getId()
    {
        return $this->_Id;
    }

    public function getName()
    {
        return $this->_Name;
    }

    public function setId($Id)
    {
        $this->_Id = $Id;
    }

    public function setName($Name)
    {
        $this->_Name = $Name;
    }
}
