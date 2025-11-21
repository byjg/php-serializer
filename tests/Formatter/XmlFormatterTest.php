<?php

namespace Tests\Formatter;

use ByJG\Serializer\Serialize;
use PHPUnit\Framework\TestCase;
use Tests\Sample\ModelGetter;
use Tests\Sample\SampleModel;
use ByJG\Serializer\Formatter\XmlFormatter;
use Tests\Sample\ModelList3;

class XmlFormatterTest extends TestCase
{
    public function testArrayFormatter(): void
    {
        $array = [
            "key1" => "value",
            "key2" => "value2"
        ];

        $formatter = new XmlFormatter();
        $this->assertEquals("<?xml version=\"1.0\"?>\n<root><key1>value</key1><key2>value2</key2></root>\n", $formatter->process($array));
    }

    public function testObjectFormatter(): void
    {
        $object = new SampleModel();
        $object->Id = "10";
        $object->setName("Joao");

        $formatter = new XmlFormatter();
        $this->assertEquals("<?xml version=\"1.0\"?>\n<root><Id>10</Id><Name>Joao</Name></root>\n", $formatter->process(Serialize::from($object)->toArray()));
        $this->assertEquals("<?xml version=\"1.0\"?>\n<test><Id>10</Id><Name>Joao</Name></test>\n", $formatter->withRootElement("test")->process($object));
    }

    public function testObjectList(): void
    {
        $object = new ModelList3();
        $object->addItem(new ModelGetter(10, "John"));
        $object->addItem(new ModelGetter(20, "Doe"));

        $formatter = new XmlFormatter();
        $this->assertEquals("<?xml version=\"1.0\"?>\n<root><collection><model0><Id>10</Id><Name>John</Name></model0><model1><Id>20</Id><Name>Doe</Name></model1></collection></root>\n", $formatter->withListElement("model")->withListElementSuffix()->process($object));

        $formatter = new XmlFormatter();
        $this->assertEquals("<?xml version=\"1.0\"?>\n<root><item><Id>10</Id><Name>John</Name></item><item><Id>20</Id><Name>Doe</Name></item></root>\n", $formatter->process(Serialize::from($object->getCollection())->toArray()));
    }

}