<?php

namespace Tests\PropertyHandler;

use ByJG\Serializer\PropertyHandler\SnakeToCamelCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SnakeToCamelCaseTest extends TestCase
{

    public function testChangeValue()
    {
        // Test with default handler (no transformation)
        $snakeToCamelCase = new SnakeToCamelCase();
        $this->assertSame('testValue', $snakeToCamelCase->transformValue('prop_name', 'propName', 'testValue'));
        
        // Test with custom value handler
        $customHandler = new SnakeToCamelCase(function ($propName, $targetName, $value) {
            return strtoupper($value);
        });
        $this->assertSame('TESTVALUE', $customHandler->transformValue('prop_name', 'propName', 'testValue'));
    }

    public static function mapProvider()
    {
        return [
            ['test', 'test'],
            ['my_test', 'myTest'],
            ['my_test_multiple', 'myTestMultiple'],
            ['my_test_multiple1', 'myTestMultiple1'],
            ['my_test_multiple12', 'myTestMultiple12'],
            ['my_test_multiple123', 'myTestMultiple123'],
            ['my_test_multiple1234', 'myTestMultiple1234'],
            ['xml_http_request', 'xmlHttpRequest']
        ];
    }

    #[DataProvider('mapProvider')]
    public function testMapName($value, $expected)
    {
        $snakeToCamelCase = new SnakeToCamelCase();
        $this->assertEquals($expected, $snakeToCamelCase->mapName($value));
    }
} 