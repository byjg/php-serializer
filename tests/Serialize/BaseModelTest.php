<?php

namespace ByJG\Serializer;

use PHPUnit\Framework\TestCase;
use Tests\Sample\ModelGetter;
use Tests\Sample\SampleModel;

class BaseModelTest extends TestCase
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


    public function testBindFromObject()
    {
        $model = new ModelGetter(10, 'Testing');

        $object = new SampleModel($model);

        $this->assertEquals(10, $object->Id);
        $this->assertEquals("Testing", $object->getName());
    }

    public function testBindFromStdClass()
    {
        // Matching exact property names
        $model = new \stdClass();
        $model->Id = 10;
        $model->Name = "Testing";

        $object = new SampleModel($model);

        $this->assertEquals(10, $object->Id);
        $this->assertEquals("Testing", $object->getName());

        // Matching with different case letters
        $model2 = new \stdClass();
        $model2->id = 10;
        $model2->name = "Testing";

        $object = new SampleModel($model2);

        $this->assertEquals(10, $object->Id);
        $this->assertEquals("Testing", $object->getName());
    }

    public function testBindFromArray()
    {
        $array = array(
            "Id" => 10,
            "Name" => "Testing"
        );

        $object = new SampleModel($array);

        $this->assertEquals(10, $object->Id);
        $this->assertEquals("Testing", $object->getName());
    }

   public function testPropertyPatternBind()
   {
       $obj = new \stdClass();
       $obj->Id_Model = 10;
       $obj->Client_Name = 'Testing';

       // Testing Without Property Bind
       $object = new \Tests\Sample\ModelPropertyPattern();
       $object->bind($obj);

       $this->assertEquals('', $object->getIdModel());
       $this->assertEquals('', $object->getClientName());

       // Testing with Bind
       $object = new \Tests\Sample\ModelPropertyPattern();
       $object->bind($obj, '/_//');

       $this->assertEquals(10, $object->getIdModel());
       $this->assertEquals("Testing", $object->getClientName());

       // Testing Constructor
       $object = new \Tests\Sample\ModelPropertyPattern($obj, '/_//');

       $this->assertEquals(10, $object->getIdModel());
       $this->assertEquals("Testing", $object->getClientName());
   }

   public function testPropertyPatternBind_2()
   {
       // Other Testing
       $obj = new \stdClass();
       $obj->IdModel = 10;
       $obj->ClientName = 'Testing';

       $object = new \Tests\Sample\ModelPropertyPattern($obj);

       $this->assertEquals(10, $object->getIdModel());
       $this->assertEquals("Testing", $object->getClientName());
   }

   /**
    * The current property pattern try do remove the underscore.
    */
   public function testPropertyPatternBind_3()
   {
       // Other Testing
       $obj = [
           "clientname" => "Joao",
           "birthdate" => "1974-01-26"
       ];

       $object = new \Tests\Sample\ModelPropertyPattern();
       $object->bind($obj);

       $this->assertEquals("1974-01-26", $object->getBirthdate());
       $this->assertEquals("Joao", $object->getClientName());
   }

   /**
    * The current property pattern try do remove the underscore.
    * The setPropertyPattern is done on constructor
    */
   public function testPropertyPatternBind_4()
   {
       // Other Testing
       $obj = [
           "birth_date" => "1974-01-26"
       ];

       $object = new \Tests\Sample\ModelPropertyPatternConstruct();
       $object->bind($obj);

       $this->assertEquals("1974-01-26", $object->getBirth_date());
   }

   /**
    * The current property pattern try do remove the underscore.
    */
   public function testPropertyPatternBind_5()
   {
       // Other Testing
       $obj = [
           "birth_date" => "1974-01-26"
       ];

       $object = new \Tests\Sample\ModelPropertyPatternAnnotation($obj);

       $this->assertEquals("1974-01-26", $object->getBirth_date());
   }

}
