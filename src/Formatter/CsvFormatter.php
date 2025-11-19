<?php

namespace ByJG\Serializer\Formatter;

use ByJG\Serializer\Serialize;

class CsvFormatter implements FormatterInterface
{
    /**
     * @param object|array $serializable
     * @return string|bool
     */
    #[\Override]
    public function process(object|array $serializable): string|bool
    {
        // Convert to array if it's an object
        if (is_object($serializable)) {
            $serializable = Serialize::from($serializable)->toArray();
        }

        // If it's an empty array, return an empty string
        if (empty($serializable)) {
            return '';
        }

        // Check if it's a simple array or an array of arrays
        $isMultidimensional = $this->isMultidimensionalArray($serializable);

        // We don't want to handle multidimensional arrays
        if (!$isMultidimensional) {
            $serializable = [$serializable];
        }

        // Get the headers (keys of the first row)
        $headers = array_keys(reset($serializable));

        // Start with the headers
        $output = $this->arrayToCsvLine($headers);

        // Add each row
        foreach ($serializable as $row) {
            $output .= $this->arrayToCsvLine(array_values($row));
        }

        return $output;
    }

    /**
     * Convert an array to a CSV line
     * 
     * @param array $fields
     * @return string
     */
    private function arrayToCsvLine(array $fields): string
    {
        $f = fopen('php://memory', 'r+');
        fputcsv($f, $fields, escape: "\\");
        rewind($f);
        $line = stream_get_contents($f);
        fclose($f);

        return $line === false ? "" : $line;
    }

    /**
     * Check if an array is multidimensional
     *
     * @param array $array
     * @return bool
     */
    private function isMultidimensionalArray(array $array): bool
    {
        foreach ($array as $value) {
            if (is_array($value)) {
                return true;
            }
        }

        return false;
    }
}
