---
sidebar_position: 6
---

# DirectTransform class

The `DirectTransform` class is a basic implementation of the `PropertyHandlerInterface` that passes property names and values through unchanged, with an optional value transformation function.

This class serves as:
1. A simple property handler that doesn't modify property names
2. A base class for more complex property handlers that need custom value transformations
3. The parent class for other property handlers like `CamelToSnakeCase`, `SnakeToCamelCase`, and `PropertyNameMapper`

## Basic Usage

The `DirectTransform` class performs an identity mapping of property names:

```php
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\PropertyHandler\DirectTransform;

$source = ["name" => "John", "age" => 30];
$target = new stdClass();

// Property names will remain the same
ObjectCopy::copy($source, $target, new DirectTransform());

// Result:
// $target->name = "John";
// $target->age = 30;
```

## Value Transformation

While property names remain unchanged, you can transform values by providing a closure to the constructor:

```php
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\PropertyHandler\DirectTransform;

$source = ["name" => "John", "age" => 30];
$target = new stdClass();

// Value transformation function
$valueHandler = function ($propertyName, $targetName, $value, $instance = null) {
    if ($propertyName === 'name') {
        return strtoupper($value);
    }
    if ($propertyName === 'age') {
        return $value + 1;
    }
    return $value;
};

ObjectCopy::copy($source, $target, new DirectTransform($valueHandler));

// Result:
// $target->name = "JOHN";
// $target->age = 31;
```

The `$valueHandler` closure receives these parameters:
- `$propertyName`: The original property name from the source object
- `$targetName`: The mapped property name for the target (in this case, it's the same as $propertyName)
- `$value`: The value from the source object
- `$instance`: The complete source object instance (optional)

## Accessing the Source Object

The value handler can access the complete source object through the `$instance` parameter:

```php
$source = new stdClass();
$source->firstName = "John";
$source->lastName = "Doe";
$source->age = 30;

$target = new stdClass();

// Value handler that combines properties
$valueHandler = function ($propertyName, $targetName, $value, $instance = null) {
    if ($propertyName === 'firstName' && $instance !== null) {
        // Create a full name using both firstName and lastName properties
        return $value . ' ' . ($instance->lastName ?? '');
    }
    return $value;
};

ObjectCopy::copy($source, $target, new DirectTransform($valueHandler));

// Result:
// $target->firstName = "John Doe";
// $target->lastName = "Doe";
// $target->age = 30;
```

## Custom Property Handlers

The `DirectTransform` class serves as the base class for more specialized property handlers:

- `CamelToSnakeCase`: Converts camelCase properties to snake_case
- `SnakeToCamelCase`: Converts snake_case properties to camelCase
- `PropertyNameMapper`: Maps specific property names to different target names

For detailed examples of these specialized handlers, see the [ObjectCopy documentation](objectcopy.md#available-property-handler-classes).

## Related Components

- [ObjectCopy](objectcopy.md) - Main utility class for property copying
- [PropertyHandlerInterface](objectcopy.md#creating-custom-property-handler-classes) - Interface implemented by this class 