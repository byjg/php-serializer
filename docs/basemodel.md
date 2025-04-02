---
sidebar_position: 5
---

# BaseModel

The `BaseModel` class is an abstract base class that implements the `ObjectCopyInterface` and provides basic functionality for model objects.

## Overview

This class:

- Implements `ObjectCopyInterface` using the `ObjectCopyTrait`
- Provides a constructor that can initialize the object from a source
- Includes a `toArray()` method for easy array conversion

## Usage

To create a model class:

```php
use ByJG\Serializer\BaseModel;

class User extends BaseModel
{
    public $id;
    public $name;
    public $email;
    
    // Inherits copyFrom(), copyTo(), and toArray() methods
}
```

## Constructor

The BaseModel constructor can initialize the object from a source:

```php
/**
 * Create a BaseModel that implements ObjectCopyInterface and toArray() method
 *
 * @param array|object|null $object The source object to copy properties from
 * @param PropertyHandlerInterface|null $propertyHandler Property handling interface
 */
public function __construct(array|object|null $object = null, ?PropertyHandlerInterface $propertyHandler = null)
```

## Example

```php
// Creating a model with initial data
$user = new User([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Converting to array
$array = $user->toArray();
```

The BaseModel provides all the functionality of ObjectCopyTrait, plus additional convenience features.

## Benefits of using BaseModel

1. **Ready-to-use base class**: Includes common functionality needed for model objects
2. **Object copying**: Built-in support for copying properties from/to other objects
3. **Array conversion**: Easy conversion to arrays with the `toArray()` method
4. **Initialization from source**: Constructor can initialize the object from various sources

## When to use BaseModel vs. ObjectCopyTrait

- Use `BaseModel` when you need a complete base model class with additional utility methods
- Use `ObjectCopyTrait` directly when you need to add copy functionality to a class that already extends another class

## Related Components

- [ObjectCopyInterface](objectcopyinterface.md) - Interface implemented by this class
- [ObjectCopyTrait](objectcopytrait.md) - Trait used by this class
- [ObjectCopy](objectcopy.md) - Utility class for property copying 