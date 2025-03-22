<?php

namespace Tests\PropertyHandler;

use ByJG\Serializer\PropertyHandler\PropertyNameMapper;
use PHPUnit\Framework\TestCase;

class PropertyNameMapperTest extends TestCase
{
    private array $mapFields = [
        'first_name' => 'firstName',
        'last_name' => 'lastName',
        'email_address' => 'emailAddress',
        'user_id' => 'userId'
    ];

    public function testMapName()
    {
        $propertyMapper = new PropertyNameMapper($this->mapFields);
        
        // Test mapped fields
        $this->assertEquals('firstName', $propertyMapper->mapName('first_name'));
        $this->assertEquals('lastName', $propertyMapper->mapName('last_name'));
        $this->assertEquals('emailAddress', $propertyMapper->mapName('email_address'));
        
        // Test unmapped field (should return original)
        $this->assertEquals('originalField', $propertyMapper->mapName('originalField'));
    }

    public function testTransformValueWithoutHandler()
    {
        $propertyMapper = new PropertyNameMapper($this->mapFields);
        
        // Test value is unchanged without handler
        $this->assertSame('test value', $propertyMapper->transformValue('first_name', 'firstName', 'test value'));
        $this->assertSame(['a' => 1], $propertyMapper->transformValue('last_name', 'lastName', ['a' => 1]));
    }
    
    public function testTransformValueWithHandler()
    {
        // Test with custom value handler
        $propertyMapper = new PropertyNameMapper(
            $this->mapFields,
            function ($propertyName, $targetName, $value) {
                if (is_string($value)) {
                    return strtoupper($value);
                }
                return $value;
            }
        );
        
        // Test string value is transformed
        $this->assertSame('TEST VALUE', $propertyMapper->transformValue('first_name', 'firstName', 'test value'));
        
        // Test non-string value remains unchanged
        $this->assertSame(1, $propertyMapper->transformValue('last_name', 'lastName', 1));
    }
} 