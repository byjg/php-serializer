<?php

namespace Tests\Sample;

use Exception;

class ModelList2
{

    protected $_collection = array();

    /**
     * Add VistoriaAuditor to a List
     * @param mixed $obj
     * @throws Exception
     */
    public function addItem($obj)
    {
        if (!($obj instanceof ModelGetter)) {
            throw new Exception('Invalid type');
        } else {
            $this->_collection[] = $obj;
        }
    }

    /**
     * Retrieve an array of ModelGetter instance
     * The property name have to "collection"
     */
    public function getCollection()
    {
        if (count($this->_collection) > 0) {
            return $this->_collection;
        } else {
            return null;
        }
    }
}
