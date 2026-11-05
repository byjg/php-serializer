<?php

namespace Tests\Serialize;

use ByJG\Serializer\Exception\InvalidArgumentException;
use ByJG\Serializer\Serialize;
use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class SerializeXmlTest extends TestCase
{
    public function testListOfScalarsIsValidXml(): void
    {
        $this->assertSame(
            "<?xml version=\"1.0\"?>\n<root><tags><item>a</item><item>b</item></tags></root>\n",
            Serialize::from(['tags' => ['a', 'b']])->toXml()
        );
    }

    /**
     * A list with one item is written as a list, like a list with more items; only a single scalar is
     * written as its text
     */
    public function testListWithASingleItem(): void
    {
        $this->assertSame(
            "<?xml version=\"1.0\"?>\n<root><item><name>John</name></item></root>\n",
            Serialize::from([['name' => 'John']])->toXml()
        );
        $this->assertSame(
            "<?xml version=\"1.0\"?>\n<users><user0><id>1</id></user0></users>\n",
            Serialize::from([['id' => 1]])->toXml(rootElement: 'users', listElement: 'user', listElementSuffix: true)
        );
        $this->assertSame('text', Serialize::from(['text'])->toXml());
    }

    public function testToXmlOptions(): void
    {
        $this->assertSame(
            "<?xml version=\"1.0\"?>\n<users><user0><id>1</id></user0><user1><id>2</id></user1></users>\n",
            Serialize::from([['id' => 1], ['id' => 2]])->toXml(rootElement: 'users', listElement: 'user', listElementSuffix: true)
        );
    }

    public function testToXmlWritesAttributesAndText(): void
    {
        $data = [
            '@attributes' => ['version' => '2'],
            'user' => ['@attributes' => ['id' => '5', 'note' => 'a & "b"'], '@value' => 'John & Jane'],
            'empty' => ['@attributes' => ['flag' => 'on']],
        ];

        $this->assertSame(
            "<?xml version=\"1.0\"?>\n<root version=\"2\"><user id=\"5\" note=\"a &amp; &quot;b&quot;\">John &amp; Jane</user><empty flag=\"on\"/></root>\n",
            Serialize::from($data)->toXml()
        );
    }

    public static function roundTripProvider(): array
    {
        return [
            'flat' => [['id' => '10', 'name' => 'Joao']],
            'nested' => [['user' => ['id' => '1', 'address' => ['city' => 'Rio']]]],
            'list of scalars' => [['tags' => ['a', 'b', 'c']]],
            'list with one entry' => [['tags' => ['a']]],
            'list of objects' => [['users' => [['id' => '1'], ['id' => '2']]]],
            'attributes and text' => [['user' => ['@attributes' => ['id' => '5'], '@value' => 'John']]],
            'attributes and children' => [['@attributes' => ['v' => '1'], 'user' => ['@attributes' => ['id' => '5'], 'name' => 'John']]],
            'escaped text' => [['text' => '<b> & "c"']],
            'empty element' => [['name' => '']],
        ];
    }

    #[DataProvider('roundTripProvider')]
    public function testFromXmlReadsBackWhatToXmlWrites(array $data): void
    {
        $this->assertSame($data, Serialize::fromXml(Serialize::from($data)->toXml())->toArray());
    }

    public function testRoundTripWithNumberedListEntries(): void
    {
        $data = ['users' => [['id' => '1'], ['id' => '2']]];
        $xml = Serialize::from($data)->toXml(listElement: 'user', listElementSuffix: true);

        $this->assertSame($data, Serialize::fromXml($xml, listElement: 'user', listElementSuffix: true)->toArray());
    }

    public function testRepeatedSiblingsBecomeAList(): void
    {
        $xml = '<library><book>A</book><book>B</book><shelf>1</shelf></library>';

        $this->assertSame(['book' => ['A', 'B'], 'shelf' => '1'], Serialize::fromXml($xml)->toArray());
    }

    public function testRepeatedSiblingsWhoseContentIsAList(): void
    {
        $xml = '<root><row><item>a</item></row><row><item>b</item></row></root>';

        $this->assertSame(['row' => [['a'], ['b']]], Serialize::fromXml($xml)->toArray());
    }

    public function testWithoutListElementOnlyRepeatedSiblingsAreAList(): void
    {
        $xml = '<root><item>a</item><other><item>b</item><item>c</item></other></root>';

        $this->assertSame(
            ['item' => 'a', 'other' => ['item' => ['b', 'c']]],
            Serialize::fromXml($xml, listElement: null)->toArray()
        );
    }

    public function testCdataIsText(): void
    {
        $this->assertSame(['body' => '<p>Hi</p>'], Serialize::fromXml('<root><body><![CDATA[<p>Hi</p>]]></body></root>')->toArray());
    }

    public function testRootWithOnlyText(): void
    {
        $this->assertSame(['@value' => 'hello'], Serialize::fromXml('<root>hello</root>')->toArray());
        $this->assertSame([], Serialize::fromXml('<root/>')->toArray());
    }

    public function testAcceptsSimpleXmlElementAndDomNode(): void
    {
        $xml = '<root><id>1</id></root>';
        $dom = new DOMDocument();
        $dom->loadXML($xml);

        $this->assertSame(['id' => '1'], Serialize::fromXml(new SimpleXMLElement($xml))->toArray());
        $this->assertSame(['id' => '1'], Serialize::fromXml($dom)->toArray());
        $this->assertSame(['id' => '1'], Serialize::fromXml($dom->documentElement)->toArray());
    }

    public function testFromXmlConvertsToOtherFormats(): void
    {
        $this->assertSame('{"user":{"id":"1","name":"Joao"}}', Serialize::fromXml('<root><user><id>1</id><name>Joao</name></user></root>')->toJson());
    }

    public function testInvalidXmlThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid XML');

        Serialize::fromXml('<root><unclosed></root>');
    }

    public function testExternalEntitiesAreNotExpanded(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'xxe');
        file_put_contents($file, 'SECRET-CONTENT');
        try {
            $xml = '<?xml version="1.0"?><!DOCTYPE root [<!ENTITY xxe SYSTEM "file://' . $file . '">]><root><data>&xxe;</data></root>';

            $this->assertStringNotContainsString('SECRET-CONTENT', (string)json_encode(Serialize::fromXml($xml)->toArray()));
        } finally {
            unlink($file);
        }
    }

    public static function namespaceProvider(): array
    {
        return [
            'prefixed element' => [
                '<book xmlns:x="urn:x"><title>T</title><x:isbn>111</x:isbn></book>',
                ['@attributes' => ['xmlns:x' => 'urn:x'], 'title' => 'T', 'x:isbn' => '111'],
                '<root xmlns:x="urn:x"><title>T</title><x:isbn>111</x:isbn></root>',
            ],
            'default namespace' => [
                '<book xmlns="urn:b"><title>T</title></book>',
                ['@attributes' => ['xmlns' => 'urn:b'], 'title' => 'T'],
                '<root xmlns="urn:b"><title>T</title></root>',
            ],
            'namespaced attributes' => [
                '<book xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="novel" id="1"><title xml:lang="en">T</title></book>',
                [
                    '@attributes' => ['xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance', 'xsi:type' => 'novel', 'id' => '1'],
                    'title' => ['@attributes' => ['xml:lang' => 'en'], '@value' => 'T'],
                ],
                '<root xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:type="novel" id="1"><title xml:lang="en">T</title></root>',
            ],
            'same name in two namespaces' => [
                '<book xmlns:x="urn:x" xmlns:y="urn:y"><x:id>1</x:id><y:id>2</y:id></book>',
                ['@attributes' => ['xmlns:x' => 'urn:x', 'xmlns:y' => 'urn:y'], 'x:id' => '1', 'y:id' => '2'],
                '<root xmlns:x="urn:x" xmlns:y="urn:y"><x:id>1</x:id><y:id>2</y:id></root>',
            ],
            'declared on a child' => [
                '<book><title>T</title><meta xmlns:m="urn:m"><m:k>v</m:k></meta></book>',
                ['title' => 'T', 'meta' => ['@attributes' => ['xmlns:m' => 'urn:m'], 'm:k' => 'v']],
                '<root><title>T</title><meta xmlns:m="urn:m"><m:k>v</m:k></meta></root>',
            ],
        ];
    }

    /**
     * An element or attribute in a namespace keeps its prefix, the declaration is an attribute as in XML,
     * and toXml() writes the namespaces back
     */
    #[DataProvider('namespaceProvider')]
    public function testNamespacesAreReadAndWrittenBack(string $xml, array $expected, string $written): void
    {
        $serialize = Serialize::fromXml($xml);

        $this->assertSame($expected, $serialize->toArray());
        $this->assertSame("<?xml version=\"1.0\"?>\n$written\n", $serialize->toXml());
        $this->assertSame($expected, Serialize::fromXml((string)$serialize->toXml())->toArray());
    }

    public function testElementOfADocumentHasTheNamespacesDeclaredOnItsParents(): void
    {
        $document = new DOMDocument();
        $document->loadXML('<bookstore xmlns:x="urn:x"><book><x:isbn>1</x:isbn></book></bookstore>');

        $this->assertSame(
            ['@attributes' => ['xmlns:x' => 'urn:x'], 'x:isbn' => '1'],
            Serialize::fromXml($document->documentElement->firstChild)->toArray()
        );
    }

    public function testPrefixWithoutADeclarationThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The prefix 'dc' of 'dc:title' has no namespace declaration (xmlns:dc)");
        Serialize::from(['dc:title' => 'T'])->toXml();
    }
}
