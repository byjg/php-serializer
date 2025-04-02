---
sidebar_position: 3
---

# ObjectCopy Interface 

`ObjectCopyInterface` is an interface that exposes the methods `copyFrom` and `copyTo`, which allow you to set property contents from or to another object.

You can either implement this interface or extend the abstract class `ObjectCopy`.

## Methods

The interface defines two methods:

```php
public function copyFrom(array|object $source, ?PropertyHandlerInterface $propertyHandler = null): void;

public function copyTo(array|object $target, ?PropertyHandlerInterface $propertyHandler = null): void;
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

// You can also use property handlers with copyFrom and copyTo
$myclass->copyFrom($data, new SnakeToCamelCase());
$myclass->copyTo($otherObject, new CamelToSnakeCase());

// With value transformation
$valueHandler = function ($propName, $targetName, $value) {
    if ($targetName === 'age') {
        return $value + 1; // Add one year to age
    }
    return $value;
};
$myclass->copyFrom($data, new SnakeToCamelCase($valueHandler));
```

## Custom Implementation

If you prefer to implement the interface directly instead of extending the `ObjectCopy` class:

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

The `PropertyHandlerInterface` defines two key methods that property handlers must implement:

1. `mapName(string $property): string` - Maps a source property name to a target property name
2. `transformValue(string $propertyName, string $targetName, mixed $value, mixed $instance = null): mixed` - Transforms the value during copying

Various implementations of this interface are available for common transformation scenarios. For detailed examples and available property handlers, see:

- [Property Handlers in ObjectCopy documentation](objectcopy.md#available-property-handler-classes)
- [Creating Custom Property Handlers](objectcopy.md#creating-custom-property-handler-classes)

## Related Components

- [ObjectCopyTrait](objectcopytrait.md) - Trait implementing this interface
- [BaseModel](basemodel.md) - Abstract base class implementing this interface
- [ObjectCopy](objectcopy.md) - Utility class for property copying
