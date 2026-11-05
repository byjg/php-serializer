<?php

namespace Tests\Reader;

use ByJG\Serializer\Reader\CsvReader;
use PHPUnit\Framework\TestCase;

class CsvReaderTest extends TestCase
{
    /**
     * @return resource
     */
    private static function stream(string $content)
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);
        return $stream;
    }

    public function testDefaultIsCommaAndDoubleQuote(): void
    {
        $records = CsvReader::records(self::stream("id,name\n1,\"Doe, \"\"JJ\"\"\"\n"));

        $this->assertSame([['id' => '1', 'name' => 'Doe, "JJ"']], iterator_to_array($records, false));
    }

    public function testSeparator(): void
    {
        $records = CsvReader::records(self::stream("id;name\n1;\"Doe; John\"\n2;Jane,Mary\n"), separator: ';');

        $this->assertSame(
            [['id' => '1', 'name' => 'Doe; John'], ['id' => '2', 'name' => 'Jane,Mary']],
            iterator_to_array($records, false)
        );
    }

    public function testEnclosure(): void
    {
        $records = CsvReader::records(
            self::stream("id,name\n1,'Doe, ''JJ'''\n2,\"Jane\"\n"),
            enclosure: "'"
        );

        // With ' as the enclosure, " is a plain character
        $this->assertSame(
            [['id' => '1', 'name' => "Doe, 'JJ'"], ['id' => '2', 'name' => '"Jane"']],
            iterator_to_array($records, false)
        );
    }

    public function testSeparatorAndEnclosureWithoutHeader(): void
    {
        $records = CsvReader::records(self::stream("1|'a|b'\n2|'line\nbreak'\n"), false, '|', "'");

        $this->assertSame([['1', 'a|b'], ['2', "line\nbreak"]], iterator_to_array($records, false));
    }
}
