<?php

namespace Tests\Formatter;

use ByJG\Serializer\Serialize;
use PHPUnit\Framework\TestCase;
use Tests\Sample\ModelGetter;
use Tests\Sample\SampleModel;
use PHPUnit\Util\Json;
use ByJG\Serializer\Formatter\JsonFormatter;
use Tests\Sample\ModelList3;

class JsonFormatterTest extends TestCase
{
    public function testArrayFormatter(): void
    {
        $array = [
            "key1" => "value",
            "key2" => "value2"
        ];

        $formatter = new JsonFormatter();
        $this->assertEquals('{"key1":"value","key2":"value2"}', $formatter->process($array));
    }

    public function testObjectFormatter(): void
    {
        $object = new SampleModel();
        $object->Id = "10";
        $object->setName("Joao");

        $formatter = new JsonFormatter();
        $this->assertEquals('{"Id":"10","Name":"Joao"}', $formatter->process($object->toArray()));
        $this->assertEquals('{"Id":"10","Name":"Joao"}', $formatter->process($object));
    }

    public function testObjectList(): void
    {
        $object = new ModelList3();
        $object->addItem(new ModelGetter(10, "John"));
        $object->addItem(new ModelGetter(20, "Doe"));

        $formatter = new JsonFormatter();
        $this->assertEquals('{"collection":[{"Id":10,"Name":"John"},{"Id":20,"Name":"Doe"}]}', $formatter->process($object));
        $this->assertEquals('[{"Id":10,"Name":"John"},{"Id":20,"Name":"Doe"}]', $formatter->process(Serialize::from($object->getCollection())->toArray()));
    }

}