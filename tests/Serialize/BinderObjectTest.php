<?php

namespace ByJG\Serializer;

use Tests\Sample\ModelPublic;
use Tests\Sample\SampleModel;

// backward compatibility
if (!class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit_Framework_TestCase', '\PHPUnit\Framework\TestCase');
}

class BinderObjectTest extends \PHPUnit\Framework\TestCase
{

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {

    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {

    }

    public function testBind_Constructor()
    {
        $object1 = new SampleModel( ['Id' => 10, 'Name' => 'Joao']);
        $this->assertEquals(10, $object1->Id);
        $this->assertEquals('Joao', $object1->getName());
    }

    public function testBind_Array()
    {
        $object1 = new SampleModel();
        $object1->bind( ['Id' => 10, 'Name' => 'Joao'] );
        $this->assertEquals(10, $object1->Id);
        $this->assertEquals('Joao', $object1->getName());
    }

    public function testBind_StdClass()
    {
        $stdClass = new \stdClass();
        $stdClass->Id = 10;
        $stdClass->Name = 'Joao';

        $object1 = new SampleModel();
        $object1->bind( $stdClass );
        $this->assertEquals(10, $object1->Id);
        $this->assertEquals('Joao', $object1->getName());
    }

    public function testBindTo_Object()
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $object2 = new SampleModel();
        $object1->bindTo($object2);

        $this->assertEquals(10, $object2->Id);
        $this->assertEquals('Joao', $object2->getName());
    }

    public function testBindTo_stdClass()
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $object2 = new \stdClass();
        $object1->bindTo($object2);

        $this->assertEquals(10, $object2->Id);
        $this->assertEquals('Joao', $object2->Name);
    }

    public function testToArray()
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $object2 = $object1->toArray();

        $this->assertEquals(10, $object2['Id']);
        $this->assertEquals('Joao', $object2['Name']);
    }

    public function testComplexBind()
    {
        $model = new ModelPublic(20, 'JG');

        $data = new \stdClass();
        $data->Id = 10;
        $data->Name = $model;

        $object = new SampleModel($data);

        $this->assertEquals(10, $object->Id);
        $this->assertEquals($model, $object->getName());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBindToArray()
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $array = [];

        BinderObject::bindObject($object1, $array);
    }

    public function testToArrayFrom()
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $result = BinderObject::toArrayFrom($object1);

        $this->assertEquals(
            [
                'Id' => 10,
                'Name' => 'Joao'
            ],
            $result
        );
    }

    // public function testToArrayFrom2()
    // {
    //     $object1 = new ModelPropertyPattern();
    //     $object1->setBirth_date('1974-01-26');
    //     $object1->setClientName('Joao');
    //     $object1->setIdModel(1);
    //
    //     $result = BinderObject::toArrayFrom($object1);
    //
    //     $this->assertEquals(
    //         [
    //             'Id_Model' => 1,
    //             'Client_Name' => "Joao",
    //             'birth_date' => "1974-01-26"
    //         ],
    //         $result
    //     );
    // }

}
