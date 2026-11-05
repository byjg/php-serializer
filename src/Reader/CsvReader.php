<?php

namespace ByJG\Serializer\Reader;

use ByJG\Serializer\Exception\InvalidArgumentException;
use Generator;

/**
 * Reads CSV one record at a time (RFC 4180): a quoted value may contain line breaks, and the lines may end
 * in LF or CRLF. An unclosed quote is not detected: fgetcsv() reads it to the end of the content, which
 * becomes part of that value.
 *
 * Iterating it reads the records of a CSV file, keyed by the header row. The file is opened when the
 * iteration starts and closed when it ends, so each iteration reads it again.
 */
class CsvReader implements FileBlocks
{
    public function __construct(private string $filename)
    {
    }

    /**
     * @return Generator<int, array>
     * @throws InvalidArgumentException when the file cannot be opened, or a record does not have as many
     *         fields as the header
     */
    #[\Override]
    public function getIterator(): Generator
    {
        $handle = @fopen($this->filename, 'r');
        if ($handle === false) {
            throw new InvalidArgumentException("Cannot open the file '$this->filename'");
        }
        try {
            yield from self::records($handle);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     * @param bool $hasHeader The first record is the header: the records are keyed by it
     * @param string $separator The character between the fields (one byte)
     * @param string $enclosure The character that quotes a field (one byte); doubled inside it, it is the
     *                          character itself
     * @return Generator<int, array> each record, keyed by the header, or a list without a header
     * @throws InvalidArgumentException when a record does not have as many fields as the header
     */
    public static function records(
        $handle,
        bool $hasHeader = true,
        string $separator = ',',
        string $enclosure = '"'
    ): Generator {
        $headers = null;
        $record = 0;

        // fgetcsv() reads a record, not a line: the line breaks inside quotes stay in the value
        while (is_array($row = fgetcsv($handle, null, $separator, $enclosure, "\\"))) {
            if (count($row) === 1 && trim((string)$row[0]) === '') {
                continue;
            }

            if ($hasHeader && $headers === null) {
                $headers = $row;
                continue;
            }

            $record++;
            if ($headers === null) {
                yield $row;
                continue;
            }

            // RFC 4180: every record has the same number of fields; a value would be lost or invented
            if (count($row) !== count($headers)) {
                throw new InvalidArgumentException(sprintf(
                    "Invalid CSV: record %d has %d fields, the header has %d",
                    $record,
                    count($row),
                    count($headers)
                ));
            }
            yield array_combine($headers, $row);
        }
    }
}
