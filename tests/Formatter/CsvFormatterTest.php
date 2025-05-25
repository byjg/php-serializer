<?php

namespace Tests\Formatter;

use ByJG\Serializer\Serialize;
use PHPUnit\Framework\TestCase;
use Tests\Sample\ModelGetter;
use Tests\Sample\SampleModel;
use ByJG\Serializer\Formatter\CsvFormatter;
use Tests\Sample\ModelList3;

class CsvFormatterTest extends TestCase
{
    public function testArrayFormatter()
    {
        $array = [
            "key1" => "value",
            "key2" => "value2"
        ];

        $formatter = new CsvFormatter();
        $expected = "key1,key2\nvalue,value2\n";
        $this->assertEquals($expected, $formatter->process($array));
    }

    public function testArrayFormatterMultipleLines()
    {
        $array = [
            [
                "key1" => "value",
                "key2" => "value2"
            ],
            [
                "key1" => "value3",
                "key2" => "value4"
            ],
        ]
        ;

        $formatter = new CsvFormatter();
        $expected = "key1,key2\nvalue,value2\nvalue3,value4\n";
        $this->assertEquals($expected, $formatter->process($array));
    }

    public function testObjectFormatter()
    {
        $object = new SampleModel();
        $object->Id = "10";
        $object->setName("Joao");

        $formatter = new CsvFormatter();
        $expected = "Id,Name\n10,Joao\n";
        $this->assertEquals($expected, $formatter->process($object->toArray()));
        $this->assertEquals($expected, $formatter->process($object));
    }

    public function testObjectListCollection()
    {
        $object = new ModelList3();
        $object->addItem(new ModelGetter(10, "John"));
        $object->addItem(new ModelGetter(20, "Doe"));

        $formatter = new CsvFormatter();
        $expected = "Id,Name\n10,John\n20,Doe\n";
        $this->assertEquals($expected, $formatter->process(Serialize::from($object->getCollection())->toArray()));
    }

    public function testFromCsv()
    {
        $csvContent = "name,age,city\nJohn,30,New York\nJane,25,Los Angeles\n";
        $expected = [
            ["name" => "John", "age" => "30", "city" => "New York"],
            ["name" => "Jane", "age" => "25", "city" => "Los Angeles"]
        ];

        $result = Serialize::fromCsv($csvContent)->toArray();
        $this->assertEquals($expected, $result);
    }

    public function testFromCsvNoHeaders()
    {
        $csvContent = "John,30,New York\nJane,25,Los Angeles\n";
        $expected = [
            ["0" => "John", "1" => "30", "2" => "New York"],
            ["0" => "Jane", "1" => "25", "2" => "Los Angeles"]
        ];

        $result = Serialize::fromCsv($csvContent, false)->toArray();
        $this->assertEquals($expected, $result);
    }

    public function testRoundTrip()
    {
        $data = [
            ["name" => "John", "age" => "30", "city" => "New York"],
            ["name" => "Jane", "age" => "25", "city" => "Los Angeles"]
        ];

        $csv = Serialize::from($data)->toCsv();
        $result = Serialize::fromCsv(is_bool($csv) ? "" : $csv)->toArray();

        $this->assertEquals($data, $result);
    }
}
