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

The BaseModel constructor can initialize the object from a source and apply property transformations during construction:

```php
/**
 * Create a BaseModel that implements ObjectCopyInterface and toArray() method
 *
 * @param array|object|null $object The source object to copy properties from
 * @param PropertyHandlerInterface|null $propertyHandler Property handling interface
 */
public function __construct(array|object|null $object = null, ?PropertyHandlerInterface $propertyHandler = null)
```

### Parameters

**$object** (array|object|null)
- The source data to initialize the model with
- Can be an array, object, or null
- If null, creates an empty model
- Properties are copied using ObjectCopy

**$propertyHandler** (PropertyHandlerInterface|null)
- Optional property handler to transform property names and values during initialization
- Useful for converting between naming conventions (e.g., snake_case to camelCase)
- See [Property Handlers documentation](propertyhandlers.md) for details

### Basic Usage

**Creating an empty model:**

```php
class User extends BaseModel {
    public $id;
    public $name;
    public $email;
}

// Empty model
$user = new User();
```

**Initializing from an array:**

```php
// Initialize with data
$user = new User([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Properties are automatically set:
// $user->id = 1
// $user->name = 'John Doe'
// $user->email = 'john@example.com'
```

**Initializing from another object:**

```php
$stdClass = new stdClass();
$stdClass->id = 1;
$stdClass->name = 'John Doe';
$stdClass->email = 'john@example.com';

$user = new User($stdClass);
```

### Using Property Handlers in Constructor

**Converting snake_case to camelCase:**

```php
use ByJG\Serializer\PropertyHandler\SnakeToCamelCase;

class User extends BaseModel {
    public $userId;
    public $firstName;
    public $lastName;
}

// Database row with snake_case
$dbRow = [
    'user_id' => 1,
    'first_name' => 'John',
    'last_name' => 'Doe'
];

// Convert during construction
$user = new User($dbRow, new SnakeToCamelCase());

// Result:
// $user->userId = 1
// $user->firstName = 'John'
// $user->lastName = 'Doe'
```

**Applying value transformations:**

```php
use ByJG\Serializer\PropertyHandler\DirectTransform;

class User extends BaseModel {
    public $name;
    public $email;
    public DateTime $createdAt;
}

// Value handler to transform data during construction
$valueHandler = function ($prop, $target, $value, $instance) {
    if ($target === 'createdAt' && is_string($value)) {
        return new DateTime($value);
    }
    if ($target === 'email' && is_string($value)) {
        return strtolower(trim($value));
    }
    return $value;
};

$data = [
    'name' => 'John Doe',
    'email' => ' JOHN@EXAMPLE.COM ',
    'createdAt' => '2024-01-01 12:00:00'
];

$user = new User($data, new DirectTransform($valueHandler));
```

For detailed information about value handler parameters and advanced transformation patterns, see the **[Property Handlers Guide - Value Handler Parameters](propertyhandlers.md#value-handler-parameter-details)**.

**Mapping custom field names:**

```php
use ByJG\Serializer\PropertyHandler\PropertyNameMapper;

class User extends BaseModel {
    public $id;
    public $fullName;
    public $emailAddress;
}

$apiResponse = [
    'user_id' => 1,
    'display_name' => 'John Doe',
    'contact_email' => 'john@example.com'
];

$mapper = new PropertyNameMapper([
    'user_id' => 'id',
    'display_name' => 'fullName',
    'contact_email' => 'emailAddress'
]);

$user = new User($apiResponse, $mapper);

// Result:
// $user->id = 1
// $user->fullName = 'John Doe'
// $user->emailAddress = 'john@example.com'
```

### Constructor vs copyFrom()

The constructor and `copyFrom()` method are similar but have different use cases:

**Constructor - Use when:**
- Creating a new instance and initializing it immediately
- Working with immutable-style objects
- Building objects from API responses or database rows

```php
// Constructor approach
$user = new User($data, $handler);
```

**copyFrom() - Use when:**
- Updating an existing instance
- Reusing an object instance
- Separating object creation from data population

```php
// copyFrom approach
$user = new User();
// ... do other setup ...
$user->copyFrom($data, $handler);
```

### toArray() Method

The BaseModel includes a `toArray()` method for easy serialization:

```php
$user = new User([
    'id' => 1,
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

$array = $user->toArray();
// Returns: ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']
```

The `toArray()` method uses the Serialize class internally, which:
- Calls getter methods if available
- Accesses public properties
- Handles nested objects recursively
- Respects property visibility rules

### Advanced Example: API DTO

```php
use ByJG\Serializer\BaseModel;
use ByJG\Serializer\PropertyHandler\SnakeToCamelCase;

class UserDTO extends BaseModel {
    public int $userId;
    public string $firstName;
    public string $lastName;
    public string $email;
    public DateTime $createdAt;

    public static function fromApiResponse(array $response): self {
        $valueHandler = function ($prop, $target, $value, $instance) {
            if ($target === 'createdAt' && is_string($value)) {
                return new DateTime($value);
            }
            if ($target === 'email') {
                return strtolower($value);
            }
            return $value;
        };

        return new self($response, new SnakeToCamelCase($valueHandler));
    }

    public function toApiResponse(): array {
        $data = $this->toArray();

        // Format DateTime for API
        if (isset($data['createdAt']) && $data['createdAt'] instanceof DateTime) {
            $data['createdAt'] = $data['createdAt']->format('c');
        }

        return $data;
    }
}

// Usage
$apiData = [
    'user_id' => 1,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'JOHN@EXAMPLE.COM',
    'created_at' => '2024-01-01T12:00:00+00:00'
];

$dto = UserDTO::fromApiResponse($apiData);
$response = $dto->toApiResponse();
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