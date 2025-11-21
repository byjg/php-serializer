<?php

namespace Tests\Sample;

use ByJG\Serializer\BaseModel;
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;

class ModelPropertyPattern extends BaseModel
{

    protected $_Id_Model = "";
    protected $_Client_Name = "";
    protected $birthdate = "";

    /**
     * Constructor
     * 
     * @param array|object|null $object Data to initialize with
     * @param PropertyHandlerInterface|null $propertyHandler Property handler
     */
    public function __construct(mixed $object = null, ?PropertyHandlerInterface $propertyHandler = null)
    {
        parent::__construct($object, $propertyHandler);
    }

    public function getIdModel()
    {
        return $this->_Id_Model;
    }

    public function getClientName()
    {
        return $this->_Client_Name;
    }

    public function setIdModel($Id): void
    {
        $this->_Id_Model = $Id;
    }

    public function setClientName($Name): void
    {
        $this->_Client_Name = $Name;
    }

    public function getBirthdate()
    {
        return $this->birthdate;
    }

    public function setBirthdate($birth_date): void
    {
        $this->birthdate = $birth_date;
    }
}
