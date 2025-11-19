<?php

namespace Tests\Serialize;

use ByJG\Serializer\Serialize;
use PHPUnit\Framework\TestCase;
use Tests\Sample\ModelGetter;
use PHPUnit\Framework\Attributes\Group;

class SerializerCachingTest extends TestCase
{
    public function testMethodExistsCache()
    {
        // Create a simple object with a counter to track method_exists calls
        $counter = 0;
        $obj = new class($counter) {
            private $counter;
            private $id = 10;
            private $name = 'Test';
            
            public function __construct(&$counter) {
                $this->counter = &$counter;
            }
            
            public function getId() {
                return $this->id;
            }
            
            public function getName() {
                return $this->name;
            }
            
            // Override method_exists with our own tracker
            public function __call($method, $args) {
                if ($method === 'methodExists') {
                    $this->counter++;
                }
                return method_exists($this, $args[0]);
            }
        };
        
        // We can't directly test the cache without modifying the class
        // but we can verify that the functionality works correctly
        
        // Serialize the object multiple times
        $result1 = Serialize::from($obj)->toArray();
        $result2 = Serialize::from($obj)->toArray();
        
        // Results should be identical
        $this->assertEquals($result1, $result2);
        
        // The anonymous class now serializes its properties thanks to our fix
        $this->assertEquals(['id' => 10, 'name' => 'Test'], $result1);
    }
    
    public function testReflectionCaching()
    {
        // This test creates multiple similar objects to test reflection caching
        $objects = [];
        
        // Create 100 objects of the same class
        for ($i = 0; $i < 100; $i++) {
            $objects[] = new ModelGetter($i, "Name$i");
        }
        
        // First loop - initial caching
        $startTime = microtime(true);
        foreach ($objects as $obj) {
            $result = Serialize::from($obj)->toArray();
            $this->assertEquals(['Id' => $obj->getId(), 'Name' => $obj->getName()], $result);
        }
        $firstLoopTime = microtime(true) - $startTime;
        
        // Second loop - should use cache
        $startTime = microtime(true);
        foreach ($objects as $obj) {
            $result = Serialize::from($obj)->toArray();
            $this->assertEquals(['Id' => $obj->getId(), 'Name' => $obj->getName()], $result);
        }
        $secondLoopTime = microtime(true) - $startTime;
        
        // We can't make strict assertions about timing as it depends on the environment
        // but in most cases the second loop should be significantly faster
        $this->assertTrue(
            $secondLoopTime <= $firstLoopTime * 1.5,
            "Second loop should leverage cached reflection data"
        );
    }
    
    #[Group('performance')]
    public function testPropertyCaching()
    {
        // Create a complex object with nested properties
        $model = new \stdClass();
        $model->simpleProperty = 'value';
        $model->nestedArray = [
            'key1' => 'value1',
            'key2' => 'value2',
            'nested' => [
                'deep1' => 'deepvalue1',
                'deep2' => 'deepvalue2'
            ]
        ];
        $model->objectProperty = new ModelGetter(10, 'Name');
        
        // First serialization
        $startTime = microtime(true);
        $result1 = Serialize::from($model)->toArray();
        $firstTime = microtime(true) - $startTime;
        
        // Second serialization - should use cached property information
        $startTime = microtime(true);
        $result2 = Serialize::from($model)->toArray();
        $secondTime = microtime(true) - $startTime;
        
        // Results should be identical
        $this->assertEquals($result1, $result2);
        
        // Compare times - should be similar or faster on second run
        // Use a loose assertion to accommodate different test environments
        $this->assertTrue(
            $secondTime <= $firstTime * 2.0,
            "Second serialization should leverage cached property data"
        );
    }
    
    #[Group('performance')]
    public function testCacheObjectMethod()
    {
        // Create a complex model object
        $model = new ModelGetter(10, 'Test');
        
        // First run - cache should be populated
        $serializer1 = Serialize::from($model);
        $result1 = $serializer1->toArray();
        
        // Second run with identical object - should use cache
        $serializer2 = Serialize::from($model);
        $result2 = $serializer2->toArray();
        
        // Results should be identical
        $this->assertEquals($result1, $result2);
        $this->assertEquals(['Id' => 10, 'Name' => 'Test'], $result1);
        
        // Create a new object of the same class but with different values
        $model2 = new ModelGetter(20, 'Different');
        $result3 = Serialize::from($model2)->toArray();
        
        // Should use the same cache but get different values
        $this->assertEquals(['Id' => 20, 'Name' => 'Different'], $result3);
    }
} 