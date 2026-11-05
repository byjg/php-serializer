<?php

namespace Tests\Serialize;

use ByJG\Serializer\Exception\InvalidArgumentException;
use ByJG\Serializer\Serialize;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Sample\ModelGetter;

class SerializeFromFileTest extends TestCase
{
    private string $dir;

    #[\Override]
    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/serialize-from-file-' . uniqid();
        mkdir($this->dir);
    }

    #[\Override]
    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->dir);
    }

    private function file(string $name, string $content): string
    {
        $filename = "$this->dir/$name";
        file_put_contents($filename, $content);
        return $filename;
    }

    private function blocks(Serialize $serialize): array
    {
        $result = [];
        foreach ($serialize->toIterator() as $index => $block) {
            $this->assertInstanceOf(Serialize::class, $block);
            $result[$index] = $block->toArray();
        }
        return $result;
    }

    public function testCsvBlocksAreTheRecords(): void
    {
        $filename = $this->file('people.csv', "name,note\r\nJohn,\"line1\nline2\"\r\nJane,\"say \"\"hi\"\"\"\r\n");
        $expected = [
            ['name' => 'John', 'note' => "line1\nline2"],
            ['name' => 'Jane', 'note' => 'say "hi"'],
        ];

        $this->assertEquals($expected, $this->blocks(Serialize::fromFile($filename)));
        $this->assertEquals($expected, Serialize::fromFile($filename)->toArray());
    }

    public function testCsvToMethodsWorkOverAllTheBlocks(): void
    {
        $filename = $this->file('people.csv', "name,age\nJohn,30\nJane,25\n");
        $serialize = Serialize::fromFile($filename);

        $this->assertEquals('[{"name":"John","age":"30"},{"name":"Jane","age":"25"}]', $serialize->toJson());
        $this->assertEquals("name,age\nJohn,30\nJane,25\n", $serialize->toCsv());
        $this->assertEquals(
            Serialize::from([['name' => 'John', 'age' => '30'], ['name' => 'Jane', 'age' => '25']])->toYaml(),
            $serialize->toYaml()
        );
        $this->assertEquals(
            Serialize::from([['name' => 'John', 'age' => '30'], ['name' => 'Jane', 'age' => '25']])->toXml(),
            $serialize->toXml()
        );
        $this->assertEquals(
            [['name' => 'John', 'age' => '30'], ['name' => 'Jane', 'age' => '25']],
            unserialize($serialize->toPhpSerialize())
        );
    }

    public function testTheOptionsApplyToEachBlock(): void
    {
        $filename = $this->file('people.csv', "name,age,city\nJohn,30,Rio\nJane,25,Lisbon\n");
        $serialize = Serialize::fromFile($filename)->withOnlyProperties(['name']);

        $this->assertEquals([['name' => 'John'], ['name' => 'Jane']], $this->blocks($serialize));
        $this->assertEquals([['name' => 'John'], ['name' => 'Jane']], $serialize->toArray());
    }

    public function testTheFileIsReadWhenUsedAndOnEachUse(): void
    {
        $filename = $this->file('people.csv', "name\nJohn\n");
        $serialize = Serialize::fromFile($filename);
        $this->assertEquals([['name' => 'John']], $serialize->toArray());

        file_put_contents($filename, "name\nJohn\nJane\n");
        $this->assertEquals([['name' => 'John'], ['name' => 'Jane']], $serialize->toArray());
    }

    public function testCsvRecordWithADifferentNumberOfFieldsThrowsWhenItIsRead(): void
    {
        $filename = $this->file('people.csv', "name,age\nJohn,30\nJane\n");
        $read = [];

        try {
            foreach (Serialize::fromFile($filename)->toIterator() as $block) {
                $read[] = $block->toArray();
            }
            $this->fail('The uneven record was not refused');
        } catch (InvalidArgumentException $exception) {
            $this->assertEquals('Invalid CSV: record 2 has 1 fields, the header has 2', $exception->getMessage());
        }
        $this->assertEquals([['name' => 'John', 'age' => '30']], $read);
    }

    public function testXmlBlocksAreTheChildrenOfTheRoot(): void
    {
        $filename = $this->file('people.xml', <<<XML
            <?xml version="1.0"?>
            <people>
              <person id="1"><name>John</name><tags><item>a</item><item>b</item></tags></person>
              <person><name>Jane</name></person>
            </people>
            XML);
        $expected = [
            ['@attributes' => ['id' => '1'], 'name' => 'John', 'tags' => ['a', 'b']],
            ['name' => 'Jane'],
        ];

        $this->assertEquals($expected, $this->blocks(Serialize::fromFile($filename)));
        $this->assertEquals($expected, Serialize::fromFile($filename)->toArray());
    }

    public function testXmlBlockIsReadAsFromXmlReadsADocument(): void
    {
        $person = '<person id="1"><name>John</name><tags><item>a</item><item>b</item></tags></person>';
        $filename = $this->file('people.xml', "<people>$person</people>");

        $this->assertEquals([Serialize::fromXml($person)->toArray()], Serialize::fromFile($filename)->toArray());
    }

    public function testXmlBlockElementAtAnyDepth(): void
    {
        $filename = $this->file('export.xml', <<<XML
            <export>
              <header><name>not a block</name></header>
              <data>
                <row><name>John</name></row>
                <row><name>Jane</name></row>
              </data>
            </export>
            XML);

        $this->assertEquals(
            [['name' => 'John'], ['name' => 'Jane']],
            $this->blocks(Serialize::fromFile($filename, blockElement: 'row'))
        );
    }

    public function testXmlBlockElementInsideABlockIsPartOfIt(): void
    {
        $filename = $this->file('tree.xml', '<tree><node><name>a</name><node><name>b</name></node></node></tree>');

        $this->assertEquals(
            [['name' => 'a', 'node' => ['name' => 'b']]],
            Serialize::fromFile($filename, blockElement: 'node')->toArray()
        );
    }

    private const string BOOK = '<book xmlns:x="urn:x" category="COOKING"><title lang="en">Everyday Italian</title>'
        . '<author>A</author><note>n1</note><author>B</author><x:isbn>111</x:isbn><desc>Very <b>good</b> book</desc></book>';

    private function books(): string
    {
        return $this->file('books.xml', '<?xml version="1.0"?><bookstore xmlns:x="urn:x"><data>'
            . str_replace(' xmlns:x="urn:x"', '', self::BOOK) . '</data></bookstore>');
    }

    public function testXmlBlockValueIsTheOriginalElement(): void
    {
        $blocks = iterator_to_array(Serialize::fromFile($this->books(), blockElement: 'book')->toIterator());

        $this->assertCount(1, $blocks);
        $value = $blocks[0]->getValue();
        $this->assertInstanceOf(\DOMElement::class, $value);
        $this->assertEquals('book', $value->nodeName);
        $this->assertEquals('COOKING', $value->getAttribute('category'));
        $this->assertEquals('111', $value->getElementsByTagNameNS('urn:x', 'isbn')->item(0)?->textContent);
    }

    /**
     * Without an option or a parameter, an XML block is written as its original element: the order of the
     * siblings, the namespaced element and the mixed text stay, which a conversion to an array loses
     */
    public function testXmlBlockToXmlIsTheOriginalElement(): void
    {
        $blocks = iterator_to_array(Serialize::fromFile($this->books(), blockElement: 'book')->toIterator());

        $this->assertEquals("<?xml version=\"1.0\"?>\n" . self::BOOK . "\n", $blocks[0]->toXml());
    }

    public function testXmlFileToXmlIsTheOriginalBlocksInARoot(): void
    {
        $this->assertEquals(
            "<?xml version=\"1.0\"?>\n<root>" . self::BOOK . "</root>\n",
            Serialize::fromFile($this->books(), blockElement: 'book')->toXml()
        );
    }

    public function testXmlToXmlWithAnOptionIsConverted(): void
    {
        $serialize = Serialize::fromFile($this->books(), blockElement: 'book')->withIgnoreProperties(['author']);
        $expected = Serialize::fromXml(self::BOOK)->withIgnoreProperties(['author']);

        $blocks = iterator_to_array($serialize->toIterator());
        $this->assertEquals($expected->toXml(), $blocks[0]->toXml());
        $this->assertStringNotContainsString('<author>', $blocks[0]->toXml());
    }

    public function testXmlBlockConvertedKeepsTheNamespaces(): void
    {
        $blocks = iterator_to_array(Serialize::fromFile($this->books(), blockElement: 'book')->toIterator());
        $block = $blocks[0]->withIgnoreProperties(['author']);

        $this->assertSame('urn:x', $block->toArray()['@attributes']['xmlns:x']);
        $this->assertSame('111', $block->toArray()['x:isbn']);
        $this->assertStringContainsString('<root xmlns:x="urn:x" category="COOKING">', (string)$block->toXml());
        $this->assertStringContainsString('<x:isbn>111</x:isbn>', (string)$block->toXml());
    }

    public function testXmlToXmlWithAParameterIsConverted(): void
    {
        $blocks = iterator_to_array(Serialize::fromFile($this->books(), blockElement: 'book')->toIterator());

        $this->assertEquals(Serialize::fromXml(self::BOOK)->toXml(rootElement: 'book'), $blocks[0]->toXml(rootElement: 'book'));
    }

    public function testXmlBlockOtherFormatsConvertTheElement(): void
    {
        $blocks = iterator_to_array(Serialize::fromFile($this->books(), blockElement: 'book')->toIterator());
        $expected = Serialize::fromXml(self::BOOK);

        $this->assertEquals($expected->toArray(), $blocks[0]->toArray());
        $this->assertEquals($expected->toJson(), $blocks[0]->toJson());
        $this->assertEquals($expected->toYaml(), $blocks[0]->toYaml());
        $this->assertEquals($expected->toArray(), unserialize($blocks[0]->toPhpSerialize()));
    }

    public function testCsvBlockValueIsTheRecord(): void
    {
        $filename = $this->file('people.csv', "name,age\nJohn,30\nJane,25\n");

        $values = array_map(fn(Serialize $block) => $block->getValue(), iterator_to_array(Serialize::fromFile($filename)->toIterator()));
        $this->assertSame([['name' => 'John', 'age' => '30'], ['name' => 'Jane', 'age' => '25']], $values);
    }

    public function testFileValueIsTheListOfTheBlockValues(): void
    {
        $filename = $this->file('people.csv', "name,age\nJohn,30\nJane,25\n");

        $this->assertSame([['name' => 'John', 'age' => '30'], ['name' => 'Jane', 'age' => '25']], Serialize::fromFile($filename)->getValue());
    }

    public function testValueOfFromIsTheModelGiven(): void
    {
        $model = new ModelGetter(10, 'John');

        $this->assertSame($model, Serialize::from($model)->getValue());
    }

    public function testInvalidXmlThrows(): void
    {
        $filename = $this->file('people.xml', '<people><person><name>John</nme></person></people>');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid XML: Opening and ending tag mismatch');
        Serialize::fromFile($filename)->toArray();
    }

    public function testJsonListBlocksAreTheItems(): void
    {
        $content = '[{"name":"John","age":30},{"name":"Jane","age":25}]';
        $filename = $this->file('people.json', $content);

        $this->assertEquals([['name' => 'John', 'age' => 30], ['name' => 'Jane', 'age' => 25]], $this->blocks(Serialize::fromFile($filename)));
        $this->assertEquals(Serialize::fromJson($content)->toArray(), Serialize::fromFile($filename)->toArray());
    }

    /**
     * The same file gives the same records with an option, read all at once or block by block
     */
    public function testOptionsOnAJsonListAreTheSameForAllTheBlocksAndEachBlock(): void
    {
        $filename = $this->file('people.json', '[{"name":"John","age":30},{"name":"Jane","age":25}]');
        $serialize = Serialize::fromFile($filename)->withOnlyProperties(['name']);

        $this->assertSame([['name' => 'John'], ['name' => 'Jane']], $serialize->toArray());
        $this->assertSame($serialize->toArray(), $this->blocks($serialize));
    }

    public function testJsonObjectIsASingleBlock(): void
    {
        $filename = $this->file('person.json', '{"name":"John","tags":["a","b"]}');

        $this->assertEquals([['name' => 'John', 'tags' => ['a', 'b']]], $this->blocks(Serialize::fromFile($filename)));
        $this->assertEquals(['name' => 'John', 'tags' => ['a', 'b']], Serialize::fromFile($filename)->toArray());
    }

    public function testInvalidJsonThrowsWhenCreated(): void
    {
        $filename = $this->file('people.json', '{"name":');

        $this->expectException(JsonException::class);
        Serialize::fromFile($filename);
    }

    #[DataProvider('yamlExtensionProvider')]
    public function testYamlListBlocksAreTheItems(string $extension): void
    {
        $filename = $this->file("people.$extension", "- name: John\n  age: 30\n- name: Jane\n  age: 25\n");

        $this->assertEquals([['name' => 'John', 'age' => 30], ['name' => 'Jane', 'age' => 25]], $this->blocks(Serialize::fromFile($filename)));
    }

    public static function yamlExtensionProvider(): array
    {
        return [['yaml'], ['yml'], ['YAML']];
    }

    public function testInMemoryListOfObjectsBlocksAreTheItems(): void
    {
        $serialize = Serialize::from([new ModelGetter(10, 'John'), new ModelGetter(20, 'Jane')]);

        $this->assertEquals([['Id' => 10, 'Name' => 'John'], ['Id' => 20, 'Name' => 'Jane']], $this->blocks($serialize));
    }

    public static function singleBlockProvider(): array
    {
        return [
            'an object' => [new ModelGetter(10, 'John'), ['Id' => 10, 'Name' => 'John']],
            'an associative array' => [['name' => 'John'], ['name' => 'John']],
            'a list of scalars' => [['a', 'b'], ['a', 'b']],
            'a mixed list' => [[['name' => 'John'], 'b'], [['name' => 'John'], 'b']],
        ];
    }

    #[DataProvider('singleBlockProvider')]
    public function testInMemoryModelThatIsNotAListOfArraysOrObjectsIsASingleBlock(object|array $model, array $expected): void
    {
        $this->assertEquals([$expected], $this->blocks(Serialize::from($model)));
    }

    public static function invalidFileProvider(): array
    {
        return [
            'not found' => ['missing.csv', null, null, "Cannot read the file"],
            'unknown extension' => ['people.txt', "name\nJohn\n", null, "Cannot detect the format"],
            'no extension' => ['people', "name\nJohn\n", null, "Cannot detect the format"],
            'block element for csv' => ['people.csv', "name\nJohn\n", 'row', "The block element is for an XML file"],
        ];
    }

    #[DataProvider('invalidFileProvider')]
    public function testInvalidFileThrowsWhenCreated(string $name, ?string $content, ?string $blockElement, string $message): void
    {
        $filename = $content === null ? "$this->dir/$name" : $this->file($name, $content);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        Serialize::fromFile($filename, $blockElement);
    }

    /**
     * Only one block is in memory at a time: reading 100,000 records keeps the peak memory flat, where loading
     * them all takes tens of megabytes
     */
    #[DataProvider('bigFileProvider')]
    public function testBigFileIsReadOneBlockAtATime(string $name, string $header, string $record, string $footer, ?string $blockElement): void
    {
        $filename = "$this->dir/$name";
        $handle = fopen($filename, 'w');
        fwrite($handle, $header);
        for ($i = 0; $i < 100000; $i++) {
            fwrite($handle, sprintf($record, $i));
        }
        fwrite($handle, $footer);
        fclose($handle);

        memory_reset_peak_usage();
        $start = memory_get_usage();
        $count = 0;
        $last = null;
        foreach (Serialize::fromFile($filename, $blockElement)->toIterator() as $block) {
            $last = $block->toArray();
            $count++;
        }
        $peak = memory_get_peak_usage() - $start;

        $this->assertEquals(100000, $count);
        $this->assertEquals(['id' => '99999', 'name' => 'Name 99999', 'city' => 'City'], $last);
        $this->assertLessThan(2 * 1024 * 1024, $peak, "Peak memory grew by $peak bytes");
    }

    public static function bigFileProvider(): array
    {
        return [
            'csv' => ['big.csv', "id,name,city\n", "%1\$d,Name %1\$d,City\n", '', null],
            'xml' => ['big.xml', '<export><rows>', '<row><id>%1$d</id><name>Name %1$d</name><city>City</city></row>', '</rows></export>', 'row'],
        ];
    }
}
