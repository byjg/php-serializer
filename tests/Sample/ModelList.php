<?php

namespace Tests\Sample;

use Exception;

class ModelList
{

    protected $_collection = [];

    /**
     * Add VistoriaAuditor to a List
     *
     * @param mixed $obj
     *
     * @throws Exception
     *
     * @return void
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
     * Dont create this node, only the nodes inside the array.
     * @Xmlnuke:DontCreateNode
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
