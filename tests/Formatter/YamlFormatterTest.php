<?php

namespace ByJG\Serializer;

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
        $this->assertEquals(<<<END
            key1: value
            key2: value2

            END,
            $formatter->process($array)
        );
    }

    public function testObjectFormatter()
    {
        $object = new SampleModel();
        $object->Id = "10";
        $object->setName("Joao");

        $formatter = new YamlFormatter();
        $this->assertEquals(<<<END
            Id: '10'
            Name: Joao

            END,
            $formatter->process($object->toArray())
        );

        $this->assertEquals(<<<END
            Id: '10'
            Name: Joao
            
            END,
            $formatter->process($object)
        );
    }

    public function testObjectList()
    {
        $object = new ModelList3();
        $object->addItem(new ModelGetter(10, "John"));
        $object->addItem(new ModelGetter(20, "Doe"));

        $formatter = new YamlFormatter();
        $this->assertEquals(<<<END
            collection:
              - { Id: 10, Name: John }
              - { Id: 20, Name: Doe }
            
            END,
            $formatter->process($object)
        );


        $this->assertEquals(<<<END
            -
              Id: 10
              Name: John
            -
              Id: 20
              Name: Doe
            
            END,
            $formatter->process(SerializerObject::instance($object->getCollection())->serialize())
        );
    }

}