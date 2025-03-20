---
sidebar_position: 3
---

# ObjectCopy Interface 

`ObjectCopyInterface` is an interface that exposes the methods `copyFrom` and `copyTo`, which allow you to set property contents from or to another object.

You can either implement this interface or extend the abstract class `ObjectCopy`.

## Methods

The interface defines two methods:

```php
public function copyFrom(array|object $source, PropertyPatternInterface|\Closure|null $propertyPattern = null): void;

public function copyTo(array|object $target, PropertyPatternInterface|Closure|null $propertyPattern = null): void;
```

## Example Usage

```php
<?php
// Create a class that extends ObjectCopy
class MyClass extends ObjectCopy
{
    public $id;
    public $name;
    public $age;
}

// Create an instance
$myclass = new MyClass();

// Copy the properties from $data into the properties that match on $myclass
$data = ['id' => 1, 'name' => 'John', 'age' => 30];
$myclass->copyFrom($data);

// Create another object
$otherObject = new stdClass();

// Copy the properties from $myclass into the properties that match on $otherObject
$myclass->copyTo($otherObject);

// You can also use property patterns with copyFrom and copyTo
$myclass->copyFrom($data, new SnakeToCamelCase());
$myclass->copyTo($otherObject, new CamelToSnakeCase());
```

## Custom Implementation

If you prefer to implement the interface directly instead of extending the `ObjectCopy` class:

```php
<?php
class MyCustomClass implements ObjectCopyInterface
{
    public $id;
    public $name;
    
    public function copyFrom(array|object $source, PropertyPatternInterface|\Closure|null $propertyPattern = null): void
    {
        // Custom implementation
        ObjectCopy::copy($source, $this, $propertyPattern);
    }
    
    public function copyTo(array|object $target, PropertyPatternInterface|Closure|null $propertyPattern = null): void
    {
        // Custom implementation
        ObjectCopy::copy($this, $target, $propertyPattern);
    }
}
```
