<?php

namespace Tests\PropertyHandler;

use ByJG\Serializer\PropertyHandler\SnakeToCamelCase;
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use PHPUnit\Framework\Attributes\DataProvider;

class SnakeToCamelCaseTest extends PropertyHandlerTestBase
{

    protected function createHandler(?callable $valueHandler = null): PropertyHandlerInterface
    {
        return new SnakeToCamelCase($valueHandler);
    }

    protected function getDefaultHandlerTestData(): array
    {
        return ['prop_name', 'propName', 'testValue'];
    }

    /**
     * @return string[][]
     *
     * @psalm-return list{list{'test', 'test'}, list{'my_test', 'myTest'}, list{'my_test_multiple', 'myTestMultiple'}, list{'my_test_multiple1', 'myTestMultiple1'}, list{'my_test_multiple12', 'myTestMultiple12'}, list{'my_test_multiple123', 'myTestMultiple123'}, list{'my_test_multiple1234', 'myTestMultiple1234'}, list{'xml_http_request', 'xmlHttpRequest'}}
     */
    public static function mapProvider(): array
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
    public function testMapName($value, $expected): void
    {
        $snakeToCamelCase = new SnakeToCamelCase();
        $this->assertEquals($expected, $snakeToCamelCase->mapName($value));
    }
} 