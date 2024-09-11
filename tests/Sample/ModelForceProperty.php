<?php
/**
 * User: jg
 * Date: 11/04/17
 * Time: 17:05
 */

namespace Tests\Sample;


class ModelForceProperty
{
    protected $fakeProp = "10";

    protected $nonExistante = "30";

    /**
     * @return int
     */
    public function getFakeProp()
    {
        return 20;
    }

}