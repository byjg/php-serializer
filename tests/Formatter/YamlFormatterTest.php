<?php

namespace Tests\Formatter;

use ByJG\Serializer\Serialize;
use PHPUnit\Framework\TestCase;
use Tests\Sample\ModelGetter;
use Tests\Sample\SampleModel;
use PHPUnit\Util\Json;
use ByJG\Serializer\Formatter\YamlFormatter;
use Tests\Sample\ModelList3;

class YamlFormatterTest extends TestCase
{
    public function testArrayFormatter()
    {
        $array = [
            "key1" => "value",
            "key2" => "value2"
        ];

        $formatter = new YamlFormatter();
        $this->assertEquals(
            file_get_contents(__DIR__ . "/yaml1.yml"),
            $formatter->process($array)
        );
    }

    public function testObjectFormatter()
    {
        $object = new SampleModel();
        $object->Id = "10";
        $object->setName("Joao");

        $formatter = new YamlFormatter();
        $this->assertEquals(
            file_get_contents(__DIR__ . "/yaml2.yml"),
            $formatter->process($object->toArray())
        );

        $this->assertEquals(
            file_get_contents(__DIR__ . "/yaml2.yml"),
            $formatter->process($object)
        );
    }

    public function testObjectList()
    {
        $object = new ModelList3();
        $object->addItem(new ModelGetter(10, "John"));
        $object->addItem(new ModelGetter(20, "Doe"));

        $formatter = new YamlFormatter();
        $this->assertEquals(
            file_get_contents(__DIR__ . "/yaml3.yml"),
            $formatter->process($object)
        );


        $this->assertEquals(
            file_get_contents(__DIR__ . "/yaml4.yml"),
            $formatter->process(Serialize::from($object->getCollection())->toArray())
        );
    }

}