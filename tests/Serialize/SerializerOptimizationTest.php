<?php

namespace Tests\Serialize;

use ByJG\Serializer\Serialize;
use PHPUnit\Framework\TestCase;
use stdClass;
use Tests\Sample\ModelGetter;

class SerializerOptimizationTest extends TestCase
{
    public function testIgnorePropertiesWithMap()
    {
        // Create an object with many properties to test optimized property lookup
        $model = new stdClass();
        for ($i = 0; $i < 100; $i++) {
            $model->{"property$i"} = "value$i";
        }

        // Add a few specific properties we want to ignore
        $model->password = "secret";
        $model->apiKey = "api-key-12345";
        $model->token = "security-token";
        
        // Create serializer with ignored properties
        $serializer = Serialize::from($model);
        $serializer->withIgnoreProperties(['password', 'apiKey', 'token']);
        
        // Get the array representation
        $result = $serializer->toArray();
        
        // Check that properties were ignored
        $this->assertArrayNotHasKey('password', $result);
        $this->assertArrayNotHasKey('apiKey', $result);
        $this->assertArrayNotHasKey('token', $result);
        
        // Check that other properties are still there
        $this->assertArrayHasKey('property0', $result);
        $this->assertArrayHasKey('property99', $result);
        
        // Test performance of property filtering
        $start = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            $result = $serializer->toArray();
        }
        
        // We're just checking that multiple serializations complete successfully
        $this->assertNotEmpty($result);
    }

    public function testFastTypeDetection()
    {
        // Create different types of data to test optimized type detection
        $data = [
            'array' => [1, 2, 3],
            'object' => new ModelGetter(10, 'Test'),
            'stdClass' => new stdClass(),
        ];
        
        // Serialize each type and check results
        $serialized = Serialize::from($data['array'])->toArray();
        $this->assertEquals($data['array'], $serialized);
        
        $serialized = Serialize::from($data['object'])->toArray();
        $this->assertEquals(['Id' => 10, 'Name' => 'Test'], $serialized);
        
        $serialized = Serialize::from($data['stdClass'])->toArray();
        $this->assertCount(0, $serialized);
        
        // Test scalar types indirectly through arrays
        $scalars = [
            'string' => 'this is a string',
            'integer' => 42,
            'float' => 3.14,
            'boolean' => true,
            'null' => null
        ];
        
        $serialized = Serialize::from($scalars)->toArray();
        $this->assertEquals($scalars, $serialized);
    }

    public function testStringConversion()
    {
        // Test the onlyString feature with different types
        $model = new stdClass();
        $model->string = "already a string";
        $model->int = 42;
        $model->float = 3.14;
        $model->bool = true;
        $model->falseVal = false;
        $model->null = null;
        
        // Normal serialization
        $serializer = Serialize::from($model);
        $result = $serializer->toArray();
        
        // Check original types
        $this->assertSame("already a string", $result['string']);
        $this->assertSame(42, $result['int']);
        $this->assertSame(3.14, $result['float']);
        $this->assertSame(true, $result['bool']);
        $this->assertSame(false, $result['falseVal']);
        $this->assertSame(null, $result['null']);
        
        // With string conversion
        $resultStrings = $serializer->withOnlyString()->toArray();
        
        // Everything should be a string now
        $this->assertSame("already a string", $resultStrings['string']);
        $this->assertSame("42", $resultStrings['int']);
        $this->assertSame("3.14", $resultStrings['float']);
        $this->assertSame("1", $resultStrings['bool']);
        $this->assertSame("", $resultStrings['falseVal']);
        $this->assertSame("", $resultStrings['null']);
        
        // Get back to normal with withOnlyString(false)
        $resultBack = $serializer->withOnlyString(false)->toArray();
        
        // Check types are back to normal
        $this->assertSame("already a string", $resultBack['string']);
        $this->assertSame(42, $resultBack['int']);
        $this->assertSame(3.14, $resultBack['float']);
        $this->assertSame(true, $resultBack['bool']);
        $this->assertSame(false, $resultBack['falseVal']);
        $this->assertSame(null, $resultBack['null']);
    }
    
    public function testLargeObjectGraph()
    {
        // Create a large object graph to test serialization performance
        $root = new stdClass();
        
        // Create a deep object hierarchy
        $current = $root;
        for ($i = 0; $i < 20; $i++) {
            $current->child = new stdClass();
            $current->value = "Level $i";
            $current->id = $i;
            
            // Add some siblings
            $current->siblings = [];
            for ($j = 0; $j < 5; $j++) {
                $sibling = new stdClass();
                $sibling->id = "$i-$j";
                $sibling->name = "Sibling $j of $i";
                $current->siblings[] = $sibling;
            }
            
            $current = $current->child;
        }
        
        // Measure serialization time
        $start = microtime(true);
        $result = Serialize::from($root)->toArray();
        $time = microtime(true) - $start;
        
        // For very large object graphs, we should check that serialization completes
        // within a reasonable time frame, but we can't make absolute assertions
        // as it depends on the environment
        
        // Just assert that it completed and returned an array with expected keys
        $this->assertArrayHasKey('child', $result);
        $this->assertArrayHasKey('value', $result);
        
        // Test the stopAtFirstLevel optimization
        $start = microtime(true);
        $resultFirstLevel = Serialize::from($root)->withStopAtFirstLevel()->toArray();
        $timeFirstLevel = microtime(true) - $start;
        
        // Stopping at first level should be much faster since it skips deep serialization
        $this->assertArrayHasKey('child', $resultFirstLevel);
        $this->assertArrayHasKey('value', $resultFirstLevel);
        $this->assertIsObject($resultFirstLevel['child']); // Should be the original object
    }
} 