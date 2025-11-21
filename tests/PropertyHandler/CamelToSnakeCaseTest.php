<?php

namespace Tests\PropertyHandler;

use ByJG\Serializer\PropertyHandler\CamelToSnakeCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CamelToSnakeCaseTest extends TestCase
{

    public function testChangeValue(): void
    {
        // Test with default handler (no transformation)
        $camelToSnakeCase = new CamelToSnakeCase();
        $this->assertSame('test_value', $camelToSnakeCase->transformValue('propName', 'prop_name', 'test_value'));
        
        // Test with custom value handler
        $customHandler = new CamelToSnakeCase(function ($propName, $targetName, $value) {
            return strtoupper($value);
        });
        $this->assertSame('TEST_VALUE', $customHandler->transformValue('propName', 'prop_name', 'test_value'));
    }

    /**
     * @return string[][]
     *
     * @psalm-return list{list{'test', 'test'}, list{'myTest', 'my_test'}, list{'MyTest', 'my_test'}, list{'MyTestMultiple', 'my_test_multiple'}, list{'myTestMultiple', 'my_test_multiple'}, list{'myTestMultiple1', 'my_test_multiple1'}, list{'myTestMultiple12', 'my_test_multiple12'}, list{'myTestMultiple123', 'my_test_multiple123'}, list{'myTestMultiple1234', 'my_test_multiple1234'}, list{'XMLHttpRequest', 'xml_http_request'}}
     */
    public static function mapProvider(): array
    {
        return [
            ['test', 'test'],
            ['myTest', 'my_test'],
            ['MyTest', 'my_test'],
            ['MyTestMultiple', 'my_test_multiple'],
            ['myTestMultiple', 'my_test_multiple'],
            ['myTestMultiple1', 'my_test_multiple1'],
            ['myTestMultiple12', 'my_test_multiple12'],
            ['myTestMultiple123', 'my_test_multiple123'],
            ['myTestMultiple1234', 'my_test_multiple1234'],
            ['XMLHttpRequest', 'xml_http_request']
        ];
    }

    #[DataProvider('mapProvider')]
    public function testMapName($value, $expected): void
    {
        $camelToSnakeCase = new CamelToSnakeCase();
        $this->assertEquals($expected, $camelToSnakeCase->mapName($value));
    }
}
