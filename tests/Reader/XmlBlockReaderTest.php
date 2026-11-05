<?php

namespace Tests\Reader;

use ByJG\Serializer\Reader\XmlBlockReader;
use DOMElement;
use Override;
use PHPUnit\Framework\TestCase;

class XmlBlockReaderTest extends TestCase
{
    private const ATOM = 'http://www.w3.org/2005/Atom';

    private string $filename;

    #[Override]
    protected function setUp(): void
    {
        $this->filename = sys_get_temp_dir() . '/xml-block-reader-' . uniqid() . '.xml';
    }

    #[Override]
    protected function tearDown(): void
    {
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    /**
     * The text of the <id> of each block read
     */
    private function ids(string $xml, ?string $blockElement, array $namespaces = []): array
    {
        file_put_contents($this->filename, $xml);
        $ids = [];
        /** @var DOMElement $element */
        foreach (new XmlBlockReader($this->filename, $blockElement, $namespaces) as $element) {
            $ids[] = $element->getElementsByTagNameNS('*', 'id')->item(0)?->textContent;
        }
        return $ids;
    }

    public function testPrefixOfTheNamespacesMatchesADefaultNamespace(): void
    {
        $xml = '<feed xmlns="' . self::ATOM . '"><entry><id>1</id></entry><entry><id>2</id></entry></feed>';

        $this->assertSame(['1', '2'], $this->ids($xml, 'a:entry', ['a' => self::ATOM]));
    }

    public function testPrefixOfTheNamespacesMatchesAnotherPrefixInTheFile(): void
    {
        $xml = '<atom:feed xmlns:atom="' . self::ATOM . '"><atom:entry><atom:id>1</atom:id></atom:entry></atom:feed>';

        $this->assertSame(['1'], $this->ids($xml, 'a:entry', ['a' => self::ATOM]));
    }

    public function testOnlyTheElementsOfThatNamespace(): void
    {
        $xml = '<feed xmlns="' . self::ATOM . '" xmlns:x="urn:other">'
            . '<entry><id>1</id></entry><x:entry><x:id>other</x:id></x:entry><entry><id>2</id></entry></feed>';

        $this->assertSame(['1', '2'], $this->ids($xml, 'a:entry', ['a' => self::ATOM]));
        $this->assertSame(['other'], $this->ids($xml, 'o:entry', ['o' => 'urn:other']));
    }

    public function testWithoutNamespacesTheNameIsAsWrittenInTheFile(): void
    {
        $xml = '<feed xmlns="' . self::ATOM . '"><entry><id>1</id></entry></feed>';

        $this->assertSame(['1'], $this->ids($xml, 'entry'));
        $this->assertSame([], $this->ids($xml, 'a:entry'));
    }

    public function testPrefixNotInTheNamespacesIsAsWrittenInTheFile(): void
    {
        $xml = '<atom:feed xmlns:atom="' . self::ATOM . '"><atom:entry><atom:id>1</atom:id></atom:entry></atom:feed>';

        $this->assertSame(['1'], $this->ids($xml, 'atom:entry', ['a' => self::ATOM]));
    }
}
