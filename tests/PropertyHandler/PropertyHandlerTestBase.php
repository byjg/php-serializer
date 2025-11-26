<?php

namespace Tests\PropertyHandler;

use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use PHPUnit\Framework\TestCase;

abstract class PropertyHandlerTestBase extends TestCase
{
    /**
     * Create an instance of the PropertyHandler being tested
     *
     * @param callable|null $valueHandler
     * @return PropertyHandlerInterface
     */
    abstract protected function createHandler(?callable $valueHandler = null): PropertyHandlerInterface;

    /**
     * Get the test data for the default handler test
     *
     * @return array{0: string, 1: string, 2: string} [sourceName, targetName, value]
     */
    abstract protected function getDefaultHandlerTestData(): array;

    public function testChangeValue(): void
    {
        // Test with default handler (no transformation)
        $handler = $this->createHandler();
        [$sourceName, $targetName, $value] = $this->getDefaultHandlerTestData();
        $this->assertSame($value, $handler->transformValue($sourceName, $targetName, $value));

        // Test with custom value handler
        $customHandler = $this->createHandler(function ($propName, $targetName, $value) {
            return strtoupper($value);
        });
        $this->assertSame(strtoupper($value), $customHandler->transformValue($sourceName, $targetName, $value));
    }
}