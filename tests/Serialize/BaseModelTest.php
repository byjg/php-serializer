<?php

namespace Tests\Serialize;

use ByJG\Serializer\PropertyHandler\SnakeToCamelCase;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Sample\ModelGetter;
use Tests\Sample\ModelPropertyPattern;
use Tests\Sample\ModelPropertyPatternAnnotation;
use Tests\Sample\ModelPropertyPatternConstruct;
use Tests\Sample\SampleModel;

class BaseModelTest extends TestCase
{
    public function testCopyFromObject(): void
    {
        $model = new ModelGetter(10, 'Testing');

        $object = new SampleModel($model);

        $this->assertEquals(10, $object->Id);
        $this->assertEquals("Testing", $object->getName());
    }

    public function testCopyFromStdClass(): void
    {
        // Matching exact property names
        $model = new stdClass();
        $model->Id = 10;
        $model->Name = "Testing";

        $object = new SampleModel($model);

        $this->assertEquals(10, $object->Id);
        $this->assertEquals("Testing", $object->getName());

        // Matching with different case letters
        $model2 = new stdClass();
        $model2->id = 10;
        $model2->name = "Testing";

        $object = new SampleModel($model2);

        $this->assertEquals(10, $object->Id);
        $this->assertEquals("Testing", $object->getName());
    }

    public function testCopyFromArray(): void
    {
        $array = array(
            "Id" => 10,
            "Name" => "Testing"
        );

        $object = new SampleModel($array);

        $this->assertEquals(10, $object->Id);
        $this->assertEquals("Testing", $object->getName());
    }

   public function testPropertyPatternCopy(): void
   {
       $obj = new stdClass();
       $obj->Id_Model = 10;
       $obj->Client_Name = 'Testing';

       // Testing Without Property Copy
       $object = new ModelPropertyPattern();
       $object->copyFrom($obj);

       $this->assertEquals('', $object->getIdModel());
       $this->assertEquals('', $object->getClientName());

       // Testing with Property Handler
       $object = new ModelPropertyPattern();
       $object->copyFrom($obj, new SnakeToCamelCase());

       $this->assertEquals(10, $object->getIdModel());
       $this->assertEquals("Testing", $object->getClientName());

       // Testing Constructor
       $object = new ModelPropertyPattern($obj, new SnakeToCamelCase());

       $this->assertEquals(10, $object->getIdModel());
       $this->assertEquals("Testing", $object->getClientName());
   }

   public function testPropertyPatternCopy_2(): void
   {
       // Other Testing
       $obj = new stdClass();
       $obj->IdModel = 10;
       $obj->ClientName = 'Testing';

       $object = new ModelPropertyPattern($obj);

       $this->assertEquals(10, $object->getIdModel());
       $this->assertEquals("Testing", $object->getClientName());
   }

   /**
    * The current property pattern try do remove the underscore.
    */
   public function testPropertyPatternCopy_3(): void
   {
       // Other Testing
       $obj = [
           "clientname" => "Joao",
           "birthdate" => "1974-01-26"
       ];

       $object = new ModelPropertyPattern();
       $object->copyFrom($obj);

       $this->assertEquals("1974-01-26", $object->getBirthdate());
       $this->assertEquals("Joao", $object->getClientName());
   }

   /**
    * The current property pattern try do remove the underscore.
    * The setPropertyPattern is done on constructor
    */
   public function testPropertyPatternCopy_4(): void
   {
       // Other Testing
       $obj = [
           "birth_date" => "1974-01-26"
       ];

       $object = new ModelPropertyPatternConstruct();
       $object->copyFrom($obj);

       $this->assertEquals("1974-01-26", $object->getBirth_date());
   }

   /**
    * The current property pattern try do remove the underscore.
    */
   public function testPropertyPatternCopy_5(): void
   {
       // Other Testing
       $obj = [
           "birth_date" => "1974-01-26"
       ];

       $object = new ModelPropertyPatternAnnotation($obj);

       $this->assertEquals("1974-01-26", $object->getBirth_date());
   }

}
