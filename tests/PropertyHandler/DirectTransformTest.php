<?php

namespace Tests\PropertyHandler;

use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\PropertyHandler\DirectTransform;
use PHPUnit\Framework\TestCase;

class DirectTransformTest extends TestCase
{
    public function testMapName()
    {
        $directTransform = new DirectTransform();
        
        // Test that property names are returned unchanged
        $this->assertEquals('propertyName', $directTransform->mapName('propertyName'));
        $this->assertEquals('another_property', $directTransform->mapName('another_property'));
        $this->assertEquals('123PropertyName', $directTransform->mapName('123PropertyName'));
    }

    public function testTransformValueWithoutHandler()
    {
        $directTransform = new DirectTransform();
        
        // Test that values are returned unchanged without a handler
        $this->assertSame('test value', $directTransform->transformValue('prop', 'prop', 'test value'));
        $this->assertSame(['a' => 1], $directTransform->transformValue('prop', 'prop', ['a' => 1]));
        $this->assertSame(123, $directTransform->transformValue('prop', 'prop', 123));
        $this->assertSame(null, $directTransform->transformValue('prop', 'prop', null));
    }
    
    public function testTransformValueWithHandler()
    {
        // Test with a custom value handler
        $directTransform = new DirectTransform(
            function ($propertyName, $targetName, $value, $instance = null) {
                if (is_string($value)) {
                    return strtoupper($value);
                }
                return $value;
            }
        );
        
        // Test string value is transformed
        $this->assertSame('TEST VALUE', $directTransform->transformValue('prop', 'prop', 'test value'));
        
        // Test non-string values remain unchanged
        $this->assertSame(123, $directTransform->transformValue('prop', 'prop', 123));
        $this->assertSame(['a' => 1], $directTransform->transformValue('prop', 'prop', ['a' => 1]));
    }
    
    public function testTransformValueWithInstanceParameter()
    {
        // Create a test object
        $testObject = new \stdClass();
        $testObject->name = 'John';
        $testObject->role = 'Admin';
        
        // Test handler that uses the instance parameter
        $directTransform = new DirectTransform(
            function ($propertyName, $targetName, $value, $instance = null) {
                if ($propertyName === 'role' && isset($instance->name)) {
                    return $value . ' (' . $instance->name . ')';
                }
                return $value;
            }
        );

        $resultObject = new \stdClass();
        ObjectCopy::copy($testObject, $resultObject, $directTransform);
        
        // Test value transformation with instance access
        $this->assertSame('Admin (John)', $resultObject->role);
        
        // Test normal value transformation
        $this->assertSame('John', $resultObject->name);
    }
} 