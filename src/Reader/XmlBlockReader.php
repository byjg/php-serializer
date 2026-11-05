<?php

namespace ByJG\Serializer\Reader;

use ByJG\Serializer\Exception\InvalidArgumentException;
use Closure;
use DOMDocument;
use DOMElement;
use Generator;
use XMLReader;

/**
 * Reads an XML file one element at a time with XMLReader: only the element being read is in memory, not the
 * whole document. Each element is the original one, a DOMElement, not converted: XPath, saveXML() or
 * Serialize work on it.
 *
 * Entities are never expanded and nothing is fetched from the network. Each iteration reads the file again.
 */
class XmlBlockReader implements FileBlocks
{
    /**
     * @param string $filename
     * @param string|null $blockElement The name of the elements to read, at any depth; null reads each child
     *                                  of the root element
     */
    public function __construct(private string $filename, private ?string $blockElement = null)
    {
    }

    /**
     * @return Generator<int, DOMElement>
     * @throws InvalidArgumentException when the file cannot be opened or is not valid XML
     */
    #[\Override]
    public function getIterator(): Generator
    {
        $blockElement = $this->blockElement;
        $reader = new XMLReader();
        if (!self::call(fn() => $reader->open($this->filename, null, LIBXML_NONET))) {
            throw new InvalidArgumentException("Cannot open the file '$this->filename'");
        }

        try {
            $moved = self::call(fn() => $reader->read());
            while ($moved) {
                $isBlock = $reader->nodeType === XMLReader::ELEMENT
                    && ($blockElement === null ? $reader->depth === 1 : $reader->name === $blockElement);
                if (!$isBlock) {
                    $moved = self::call(fn() => $reader->read());
                    continue;
                }

                $node = self::call(fn() => $reader->expand(new DOMDocument()));
                if (!$node instanceof DOMElement) {
                    throw new InvalidArgumentException("Invalid XML: cannot read the element '$reader->name'");
                }
                yield $node;

                // Past the element, not into it: an element with the same name inside a block is not a block
                $moved = self::call(fn() => $reader->next());
            }
        } finally {
            $reader->close();
        }
    }

    /**
     * Runs an XMLReader call with the libxml errors collected, not raised as warnings
     *
     * @template T
     * @param Closure(): T $action
     * @return T
     * @throws InvalidArgumentException when libxml reports an error
     */
    private static function call(Closure $action): mixed
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $result = $action();
            $error = libxml_get_last_error();
            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors($previous);
        }

        if ($error !== false && $error->level >= LIBXML_ERR_ERROR) {
            throw new InvalidArgumentException('Invalid XML: ' . trim($error->message));
        }
        return $result;
    }
}
