---
sidebar_position: 3
---

# ObjectCopyTrait

The `ObjectCopyTrait` provides an implementation of the `ObjectCopyInterface` methods that can be used in your classes.

## Overview

This trait implements:

- `copyFrom()` - Copies properties from a source object to the current object
- `copyTo()` - Copies properties from the current object to a target object

Both methods use the static `ObjectCopy::copy()` method internally.

## Usage

To use the trait in your class:

```php
use ByJG\Serializer\ObjectCopyInterface;
use ByJG\Serializer\ObjectCopyTrait;

class User implements ObjectCopyInterface
{
    use ObjectCopyTrait;
    
    public $id;
    public $name;
    public $email;
    
    // The class now has copyFrom() and copyTo() methods
}
```

## Example

```php
// Creating an object and copying properties from an array
$user = new User();
$user->copyFrom([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Copying properties to another object
$userData = new stdClass();
$user->copyTo($userData);
```

Property handlers can be used with these methods for property transformation. For detailed examples and available property handlers, see the [ObjectCopy documentation](objectcopy.md#available-property-handler-classes).

## Benefits of using the trait

1. **Implementing the interface**: Your class automatically fulfills the `ObjectCopyInterface` contract
2. **Code reuse**: You don't need to implement the copy logic in each class
3. **Consistency**: All classes using the trait will handle copying in the same way
4. **Flexibility**: You can still use property handlers for transformations

## When to use ObjectCopyTrait vs. BaseModel

- Use `ObjectCopyTrait` when you want to add copy functionality to a class that already extends another class
- Use `BaseModel` when you want a ready-to-use base class that includes copying functionality plus other utility methods

## Related Components

- [ObjectCopyInterface](objectcopyinterface.md) - Interface defining the copy methods
- [ObjectCopy](objectcopy.md) - Utility class for copying properties between objects
- [BaseModel](basemodel.md) - Abstract base class using this trait 