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
    public function testArrayFormatter(): void
    {
        $array = [
            "key1" => "value",
            "key2" => "value2"
        ];

        $formatter = new CsvFormatter();
        $expected = "key1,key2\nvalue,value2\n";
        $this->assertEquals($expected, $formatter->process($array));
    }

    public function testArrayFormatterMultipleLines(): void
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

    public function testObjectFormatter(): void
    {
        $object = new SampleModel();
        $object->Id = "10";
        $object->setName("Joao");

        $formatter = new CsvFormatter();
        $expected = "Id,Name\n10,Joao\n";
        $this->assertEquals($expected, $formatter->process($object->toArray()));
        $this->assertEquals($expected, $formatter->process($object));
    }

    public function testObjectListCollection(): void
    {
        $object = new ModelList3();
        $object->addItem(new ModelGetter(10, "John"));
        $object->addItem(new ModelGetter(20, "Doe"));

        $formatter = new CsvFormatter();
        $expected = "Id,Name\n10,John\n20,Doe\n";
        $this->assertEquals($expected, $formatter->process(Serialize::from($object->getCollection())->toArray()));
    }

    public function testFromCsv(): void
    {
        $csvContent = "name,age,city\nJohn,30,New York\nJane,25,Los Angeles\n";
        $expected = [
            ["name" => "John", "age" => "30", "city" => "New York"],
            ["name" => "Jane", "age" => "25", "city" => "Los Angeles"]
        ];

        $result = Serialize::fromCsv($csvContent)->toArray();
        $this->assertEquals($expected, $result);
    }

    public function testFromCsvNoHeaders(): void
    {
        $csvContent = "John,30,New York\nJane,25,Los Angeles\n";
        $expected = [
            ["0" => "John", "1" => "30", "2" => "New York"],
            ["0" => "Jane", "1" => "25", "2" => "Los Angeles"]
        ];

        $result = Serialize::fromCsv($csvContent, false)->toArray();
        $this->assertEquals($expected, $result);
    }

    /**
     * RFC 4180: a quoted value may contain line breaks and quotes, and the lines may end in CRLF
     */
    public function testFromCsvQuotedLineBreaksAndCrlf(): void
    {
        $csvContent = "name,note\r\nJohn,\"line1\nline2\"\r\nJane,\"say \"\"hi\"\"\"\r\n";
        $expected = [
            ["name" => "John", "note" => "line1\nline2"],
            ["name" => "Jane", "note" => 'say "hi"'],
        ];

        $result = Serialize::fromCsv($csvContent)->toArray();
        $this->assertEquals($expected, $result);
    }

    public static function unevenRecordProvider(): array
    {
        return [
            'fewer fields' => ["name,age\nJohn,30\nJane\n", "Invalid CSV: record 2 has 1 fields, the header has 2"],
            'more fields' => ["name,age\nJohn,30,extra\n", "Invalid CSV: record 1 has 3 fields, the header has 2"],
        ];
    }

    /**
     * RFC 4180: every record has as many fields as the header; a missing or extra value is an error, not dropped
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('unevenRecordProvider')]
    public function testFromCsvRecordWithADifferentNumberOfFields(string $csvContent, string $message): void
    {
        $this->expectException(\ByJG\Serializer\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        Serialize::fromCsv($csvContent);
    }

    public function testRoundTrip(): void
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
