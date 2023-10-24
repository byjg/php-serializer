<?php

namespace ByJG\Serializer;

use ByJG\Serializer\Exception\InvalidArgumentException;
use ByJG\Serializer\PropertyPattern\CamelToSnakeCase;
use ByJG\Serializer\PropertyPattern\SnakeToCamelCase;
use PHPUnit\Framework\TestCase;
use Tests\Sample\ModelPropertyPattern;
use Tests\Sample\ModelPublic;
use Tests\Sample\SampleModel;

class BinderObjectTest extends TestCase
{
    public function testBind_Constructor()
    {
        $object1 = new SampleModel( ['Id' => 10, 'Name' => 'Joao']);
        $this->assertEquals(10, $object1->Id);
        $this->assertEquals('Joao', $object1->getName());
    }

    public function testBind_Array()
    {
        $object1 = new SampleModel();
        $object1->bindFrom( ['Id' => 10, 'Name' => 'Joao'] );
        $this->assertEquals(10, $object1->Id);
        $this->assertEquals('Joao', $object1->getName());
    }

    public function testBind_StdClass()
    {
        $stdClass = new \stdClass();
        $stdClass->Id = 10;
        $stdClass->Name = 'Joao';

        $object1 = new SampleModel();
        $object1->bindFrom( $stdClass );
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

    public function testBindToArray()
    {
        $this->expectException(InvalidArgumentException::class);
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $array = [];

        BinderObject::bind($object1, $array);
    }

    public function testToArrayFrom()
    {
        $object1 = new SampleModel();
        $object1->Id = 10;
        $object1->setName('Joao');

        $result = SerializerObject::instance($object1)->serialize();

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

        $result = SerializerObject::instance($object1)->serialize();

        $this->assertEquals(
            [
                'IdModel' => 1,
                'ClientName' => "Joao",
                'birthdate' => "1974-01-26"
            ],
            $result
        );
    }

    public function testPropertyPatterSnakeToCamel()
    {
        $source = new \stdClass();
        $source->id_model = 1;
        $source->client_name = 'Joao';
        $source->age = 49;

        $target = new \stdClass();

        BinderObject::bind($source, $target, new SnakeToCamelCase());

        $this->assertEquals(1, $target->idModel);
        $this->assertEquals('Joao', $target->clientName);
        $this->assertEquals(49, $target->age);
    }

    public function testPropertyPatterCamelToSnake()
    {
        $source = new \stdClass();
        $source->idModel = 1;
        $source->clientName = 'Joao';
        $source->age = 49;

        $target = new \stdClass();

        BinderObject::bind($source, $target, new CamelToSnakeCase());

        $this->assertEquals(1, $target->id_model);
        $this->assertEquals('Joao', $target->client_name);
        $this->assertEquals(49, $target->age);
    }
}
