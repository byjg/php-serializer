---
sidebar_position: 4
---

# ObjectCopy Interface 

`ObjectCopyInterface` is an interface that exposes the methods `copyFrom` and `copyTo`, which allow you to set property contents from or to another object.

You can implement this interface directly, use the `ObjectCopyTrait`, or extend the `BaseModel` abstract class.

## Methods

The interface defines two methods:

```php
public function copyFrom(array|object $source, ?PropertyHandlerInterface $propertyHandler = null): void;

public function copyTo(array|object $target, ?PropertyHandlerInterface $propertyHandler = null): void;
```

## Example Usage

### Using BaseModel

The easiest way to use this interface is by extending the `BaseModel` abstract class:

```php
<?php
use ByJG\Serializer\BaseModel;

class MyClass extends BaseModel
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

// You can also use property handlers with copyFrom and copyTo
$myclass->copyFrom($data, new SnakeToCamelCase());
$myclass->copyTo($otherObject, new CamelToSnakeCase());
```

### Using ObjectCopyTrait

If your class already extends another class, use the `ObjectCopyTrait`:

```php
<?php
use ByJG\Serializer\ObjectCopyInterface;
use ByJG\Serializer\ObjectCopyTrait;

class MyClass implements ObjectCopyInterface
{
    use ObjectCopyTrait;

    public $id;
    public $name;
    public $age;
}
```

### Custom Implementation

If you prefer to implement the interface directly:

```php
<?php
class MyCustomClass implements ObjectCopyInterface
{
    public $id;
    public $name;
    
    public function copyFrom(array|object $source, ?PropertyHandlerInterface $propertyHandler = null): void
    {
        // Custom implementation
        ObjectCopy::copy($source, $this, $propertyHandler);
    }
    
    public function copyTo(array|object $target, ?PropertyHandlerInterface $propertyHandler = null): void
    {
        // Custom implementation
        ObjectCopy::copy($this, $target, $propertyHandler);
    }
}
```

## Using PropertyHandlerInterface

Both `copyFrom()` and `copyTo()` methods accept an optional `PropertyHandlerInterface` parameter for property name mapping and value transformation. The interface defines two methods: `mapName()` for property name transformation and `transformValue()` for value transformation.

For detailed documentation on property handlers including all built-in handlers, custom handler creation, and transformation patterns, see the **[Property Handlers Guide](propertyhandlers.md)**.

## Related Components

- [ObjectCopyTrait](objectcopytrait.md) - Trait implementing this interface
- [BaseModel](basemodel.md) - Abstract base class implementing this interface
- [ObjectCopy](objectcopy.md) - Utility class for property copying
