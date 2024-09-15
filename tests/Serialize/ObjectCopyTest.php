<?php

namespace Tests\Serialize;

use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\PropertyPattern\CamelToSnakeCase;
use ByJG\Serializer\PropertyPattern\DifferentTargetProperty;
use ByJG\Serializer\PropertyPattern\SnakeToCamelCase;
use ByJG\Serializer\Serialize;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Sample\ModelPropertyPattern;
use Tests\Sample\ModelPublic;
use Tests\Sample\SampleModel;
use TypeError;

class ObjectCopyTest extends TestCase
{
    public function testCopy_Constructor()
    {
        $object1 = new SampleModel( ['Id' => 10, 'Name' => 'Joao']);
        $this->assertEquals(10, $object1->Id);
        $this->assertEquals('Joao', $object1->getName());
    }

    public function testCopy_Array()
    {
        $object1 = new SampleModel();
        $object1->copyFrom(['Id' => 10, 'Name' => 'Joao']);
        $this->assertEquals(10, $object1->Id);
        $this->assertEquals('Joao', $object1->getName());
    }

    public function testCopy_StdClass()
    {
        $stdClass = new stdClass();
        $stdClass->Id = 10;
        $stdClass->Name = 'Joao';

        $object1 = new SampleModel();
        $object1->copyFrom($stdClass);
        $this->assertEquals(10, $object1->Id);
        $this->assertEquals('Joao', $object1->getName());
    }

    public function testCopyTo_Object()
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $object2 = new SampleModel();
        $object1->copyTo($object2);

        $this->assertEquals(10, $object2->Id);
        $this->assertEquals('Joao', $object2->getName());
    }

    public function testCopyTo_stdClass()
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $object2 = new stdClass();
        $object1->copyTo($object2);

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

    public function testComplexCopy()
    {
        $model = new ModelPublic(20, 'JG');

        $data = new stdClass();
        $data->Id = 10;
        $data->Name = $model;

        $object = new SampleModel($data);

        $this->assertEquals(10, $object->Id);
        $this->assertEquals($model, $object->getName());
    }

    public function testCopyToArray()
    {
        $this->expectException(TypeError::class);
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $array = [];

        ObjectCopy::copy($object1, $array);
    }

    public function testToArrayFrom()
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $result = Serialize::from($object1)->toArray();

        $this->assertEquals(
            [
                'Id' => 10,
                'Name' => 'Joao'
            ],
            $result
        );
    }

    public function testToArrayFrom2()
    {
        $object1 = new ModelPropertyPattern();
        $object1->setBirthdate('1974-01-26');
        $object1->setClientName('Joao');
        $object1->setIdModel(1);

        $result = Serialize::from($object1)->toArray();

        $this->assertEquals(
            [
                'IdModel' => 1,
                'ClientName' => "Joao",
                'birthdate' => "1974-01-26"
            ],
            $result
        );
    }

    public function testObjectCopy()
    {
        $source = [
            "Id" => 1,
            "Name" => "Joao",
            "Ignored" => "Ignored"
        ];

        $target = new ModelPublic(5, "Test");
        ObjectCopy::copy($source, $target, new SnakeToCamelCase());

        $this->assertEquals(1, $target->Id);
        $this->assertEquals('Joao', $target->Name);
    }

    public function testPropertyPatterSnakeToCamel()
    {
        $source = new stdClass();
        $source->id_model = 1;
        $source->client_name = 'Joao';
        $source->age = 49;

        $target = new stdClass();
        ObjectCopy::copy($source, $target, new SnakeToCamelCase());

        $this->assertEquals(1, $target->idModel);
        $this->assertEquals('Joao', $target->clientName);
        $this->assertEquals(49, $target->age);
    }


    public function testPropertyPatterCamelToSnake()
    {
        $source = new stdClass();
        $source->idModel = 1;
        $source->clientName = 'Joao';
        $source->age = 49;

        $target = new stdClass();

        ObjectCopy::copy($source, $target, new CamelToSnakeCase());

        $this->assertEquals(1, $target->id_model);
        $this->assertEquals('Joao', $target->client_name);
        $this->assertEquals(49, $target->age);
    }

    public function testPropertyDifferentName()
    {
        $source = new stdClass();
        $source->idModel = 1;
        $source->clientName = 'Joao';
        $source->age = 49;

        $target = new stdClass();

        ObjectCopy::copy($source, $target, new DifferentTargetProperty(['idModel' => 'x', 'clientName' => 'y']));

        $this->assertEquals(1, $target->x);
        $this->assertEquals('Joao', $target->y);
        $this->assertEquals(49, $target->age);

    }

    public function testPropertyDifferentNameAndChangeValue()
    {
        $source = new stdClass();
        $source->idModel = 1;
        $source->clientName = 'Joao';
        $source->age = 49;

        $target = new stdClass();

        ObjectCopy::copy($source, $target, new DifferentTargetProperty(['idModel' => 'x', 'clientName' => 'y']), function ($propName, $targetName, $value) {
            return "$propName-$targetName-$value";
        });

        $this->assertEquals("idModel-x-1", $target->x);
        $this->assertEquals('clientName-y-Joao', $target->y);
        $this->assertEquals("age-age-49", $target->age);

    }
}
