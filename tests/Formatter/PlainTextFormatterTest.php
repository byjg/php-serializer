<?php

namespace Tests\Formatter;

use ByJG\Serializer\Serialize;
use PHPUnit\Framework\TestCase;
use Tests\Sample\ModelGetter;
use Tests\Sample\SampleModel;
use PHPUnit\Util\Json;
use ByJG\Serializer\Formatter\PlainTextFormatter;
use Tests\Sample\ModelList3;

class PlainTextFormatterTest extends TestCase
{
    public function testArrayFormatter(): void
    {
        $array = [
            "key1" => "value",
            "key2" => "value2"
        ];

        $formatter = new PlainTextFormatter();
        $this->assertEquals("value\nvalue2\n", $formatter->process($array));
    }

    public function testObjectFormatter(): void
    {
        $object = new SampleModel();
        $object->Id = "10";
        $object->setName("Joao");

        $formatter = new PlainTextFormatter();
        $this->assertEquals("10\nJoao\n", $formatter->process($object->toArray()));
        $this->assertEquals("10\nJoao\n", $formatter->process($object));
    }

    public function testObjectList(): void
    {
        $object = new ModelList3();
        $object->addItem(new ModelGetter(10, "John"));
        $object->addItem(new ModelGetter(20, "Doe"));

        $formatter = new PlainTextFormatter();
        $this->assertEquals("10\nJohn\n\n20\nDoe\n\n\n", $formatter->process($object));
        $this->assertEquals("10\nJohn\n\n20\nDoe\n\n", $formatter->process(Serialize::from($object->getCollection())->toArray()));
    }

}

