<?php

namespace Tests\Sample;

use ByJG\Serializer\BaseModel;

class ModelPropertyPattern extends BaseModel
{

    protected $_Id_Model = "";
    protected $_Client_Name = "";
    protected $birthdate = "";

    function __construct($object = null, $propertyPattern = null)
    {
        parent::__construct($object, $propertyPattern);
    }

    public function getIdModel()
    {
        return $this->_Id_Model;
    }

    public function getClientName()
    {
        return $this->_Client_Name;
    }

    public function setIdModel($Id)
    {
        $this->_Id_Model = $Id;
    }

    public function setClientName($Name)
    {
        $this->_Client_Name = $Name;
    }

    public function getBirthdate()
    {
        return $this->birthdate;
    }

    public function setBirthdate($birth_date)
    {
        $this->birthdate = $birth_date;
    }
}
