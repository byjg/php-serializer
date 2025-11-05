---
sidebar_position: 8
---

# Property Handlers

Property handlers are responsible for transforming property names and values when copying data between objects. They implement the `PropertyHandlerInterface` and are used by the `ObjectCopy` class and `BaseModel` class to customize how properties are mapped and transformed.

## Overview

Property handlers provide two main capabilities:

1. **Property Name Mapping**: Transform property names from source to target (e.g., camelCase to snake_case)
2. **Value Transformation**: Modify property values during the copy process

The library provides several built-in property handlers:

- **DirectTransform** - Identity mapping (no changes to property names)
- **CamelToSnakeCase** - Converts camelCase to snake_case
- **SnakeToCamelCase** - Converts snake_case to camelCase
- **PropertyNameMapper** - Maps specific properties using a custom mapping array

## PropertyHandlerInterface

All property handlers implement the `PropertyHandlerInterface`:

```php
namespace ByJG\Serializer\PropertyHandler;

interface PropertyHandlerInterface
{
    /**
     * Maps a source property name to a target property name
     *
     * @param string $property The source property name
     * @return string The target property name
     */
    public function mapName(string $property): string;

    /**
     * Changes the value being copied
     *
     * @param string $propertyName The source property name
     * @param string $targetName The target property name
     * @param mixed $value The value to be changed
     * @param mixed|null $instance The full source object instance (optional)
     * @return mixed The modified value
     */
    public function transformValue(
        string $propertyName,
        string $targetName,
        mixed $value,
        mixed $instance = null
    ): mixed;
}
```

## Built-in Property Handlers

### DirectTransform

The simplest property handler that performs identity mapping - property names remain unchanged.

#### Basic Usage

```php
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\PropertyHandler\DirectTransform;

$source = ['name' => 'John', 'age' => 30];
$target = new stdClass();

ObjectCopy::copy($source, $target, new DirectTransform());

// Result:
// $target->name = 'John';
// $target->age = 30;
```

#### With Value Transformation

```php
$valueHandler = function ($propertyName, $targetName, $value, $instance = null) {
    if ($propertyName === 'name') {
        return strtoupper($value);
    }
    return $value;
};

ObjectCopy::copy($source, $target, new DirectTransform($valueHandler));

// Result:
// $target->name = 'JOHN';
// $target->age = 30;
```

### CamelToSnakeCase

Converts camelCase property names to snake_case.

#### Basic Conversion

```php
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\PropertyHandler\CamelToSnakeCase;

class Source {
    public $idModel = 1;
    public $clientName = 'John';
    public $userAge = 30;
}

class Target {
    public $id_model;
    public $client_name;
    public $user_age;
}

$source = new Source();
$target = new Target();

ObjectCopy::copy($source, $target, new CamelToSnakeCase());

// Result:
// $target->id_model = 1;
// $target->client_name = 'John';
// $target->user_age = 30;
```

#### Handling Acronyms

The handler intelligently handles acronyms:

```php
$source = new class {
    public $XMLHttpRequest = 'data';
    public $apiURL = 'https://example.com';
    public $userId = 123;
};

$target = new stdClass();

ObjectCopy::copy($source, $target, new CamelToSnakeCase());

// Result:
// $target->xml_http_request = 'data';
// $target->api_url = 'https://example.com';
// $target->user_id = 123;
```

#### With Value Transformation

```php
$valueHandler = function ($propertyName, $targetName, $value, $instance = null) {
    // Uppercase all string values
    if (is_string($value)) {
        return strtoupper($value);
    }
    return $value;
};

ObjectCopy::copy($source, $target, new CamelToSnakeCase($valueHandler));
```

### SnakeToCamelCase

Converts snake_case property names to camelCase.

#### Basic Conversion

```php
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\PropertyHandler\SnakeToCamelCase;

class Source {
    public $id_model = 1;
    public $client_name = 'John';
    public $user_age = 30;
}

class Target {
    public $idModel;
    public $clientName;
    public $userAge;
}

$source = new Source();
$target = new Target();

ObjectCopy::copy($source, $target, new SnakeToCamelCase());

// Result:
// $target->idModel = 1;
// $target->clientName = 'John';
// $target->userAge = 30;
```

#### Database to Object Mapping

Common use case: converting database results to objects:

```php
// Database result (snake_case)
$dbRow = [
    'user_id' => 1,
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email_address' => 'john@example.com',
    'created_at' => '2024-01-01 12:00:00'
];

class User {
    public $userId;
    public $firstName;
    public $lastName;
    public $emailAddress;
    public $createdAt;
}

$user = new User();
ObjectCopy::copy($dbRow, $user, new SnakeToCamelCase());
```

#### With DateTime Transformation

```php
$valueHandler = function ($propertyName, $targetName, $value, $instance = null) {
    // Convert timestamp strings to DateTime objects
    if (in_array($targetName, ['createdAt', 'updatedAt']) && is_string($value)) {
        return new DateTime($value);
    }
    return $value;
};

ObjectCopy::copy($dbRow, $user, new SnakeToCamelCase($valueHandler));
```

### PropertyNameMapper

Maps specific source properties to different target property names using a custom mapping array.

#### Basic Mapping

```php
use ByJG\Serializer\ObjectCopy;
use ByJG\Serializer\PropertyHandler\PropertyNameMapper;

class Source {
    public $id = 1;
    public $name = 'John';
    public $email = 'john@example.com';
}

class Target {
    public $userId;
    public $fullName;
    public $emailAddress;
}

$source = new Source();
$target = new Target();

$mapper = new PropertyNameMapper([
    'id' => 'userId',
    'name' => 'fullName',
    'email' => 'emailAddress'
]);

ObjectCopy::copy($source, $target, $mapper);

// Result:
// $target->userId = 1;
// $target->fullName = 'John';
// $target->emailAddress = 'john@example.com';
```

#### Partial Mapping

You don't need to map all properties - unmapped properties use their original names:

```php
$mapper = new PropertyNameMapper([
    'id' => 'userId'
    // 'name' and 'email' will remain as-is
]);

ObjectCopy::copy($source, $target, $mapper);

// Result:
// $target->userId = 1;
// $target->name = 'John';
// $target->email = 'john@example.com';
```

#### With Value Transformation

```php
$valueHandler = function ($propertyName, $targetName, $value, $instance = null) {
    if ($targetName === 'fullName') {
        return strtoupper($value);
    }
    if ($targetName === 'emailAddress') {
        return strtolower($value);
    }
    return $value;
};

$mapper = new PropertyNameMapper([
    'id' => 'userId',
    'name' => 'fullName',
    'email' => 'emailAddress'
], $valueHandler);

ObjectCopy::copy($source, $target, $mapper);
```

#### Computed Properties

Create new properties by combining multiple source properties:

```php
class Source {
    public $firstName = 'John';
    public $lastName = 'Doe';
    public $salary = 50000;
}

class Target {
    public $fullName;
    public $annualSalary;
}

$valueHandler = function ($propertyName, $targetName, $value, $instance = null) {
    if ($targetName === 'fullName' && $instance !== null) {
        // Combine firstName and lastName
        return trim(
            ($instance->firstName ?? '') . ' ' .
            ($instance->lastName ?? '')
        );
    }
    if ($targetName === 'annualSalary') {
        // Keep value as-is
        return $value;
    }
    return $value;
};

$mapper = new PropertyNameMapper([
    'firstName' => 'fullName',
    'salary' => 'annualSalary'
], $valueHandler);

$source = new Source();
$target = new Target();
ObjectCopy::copy($source, $target, $mapper);

// Result:
// $target->fullName = 'John Doe';
// $target->annualSalary = 50000;
```

## Value Handler Parameter Details

The value handler closure receives four parameters:

### 1. $propertyName

The original property name from the source object **before** any mapping.

```php
$valueHandler = function ($propertyName, $targetName, $value, $instance = null) {
    echo "Source property: $propertyName\n";
    return $value;
};
```

### 2. $targetName

The property name **after** mapping transformation. This is the name that will be used in the target object.

```php
$valueHandler = function ($propertyName, $targetName, $value, $instance = null) {
    echo "Source: $propertyName -> Target: $targetName\n";
    return $value;
};
```

### 3. $value

The actual value from the source property.

```php
$valueHandler = function ($propertyName, $targetName, $value, $instance = null) {
    // Transform the value
    if (is_string($value)) {
        return trim($value);
    }
    return $value;
};
```

### 4. $instance (optional)

The complete source object instance. Use this to access other properties when computing values.

```php
$valueHandler = function ($propertyName, $targetName, $value, $instance = null) {
    if ($targetName === 'displayName' && $instance !== null) {
        // Access multiple properties from source
        $title = $instance->title ?? '';
        $firstName = $instance->firstName ?? '';
        $lastName = $instance->lastName ?? '';

        return trim("$title $firstName $lastName");
    }
    return $value;
};
```

**Important Notes:**
- The `$instance` parameter provides access to the **source** object, not the target
- Use it when you need to read multiple source properties to compute a single target value
- Be cautious with performance - accessing `$instance` is slower than just using `$value`

## Creating Custom Property Handlers

You can create custom property handlers by implementing the `PropertyHandlerInterface`.

### Example: Prefix Property Handler

Add a prefix to all property names:

```php
namespace MyApp\PropertyHandlers;

use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use Closure;

class PrefixPropertyHandler implements PropertyHandlerInterface
{
    private string $prefix;
    private ?Closure $valueHandler;

    public function __construct(string $prefix, ?Closure $valueHandler = null)
    {
        $this->prefix = $prefix;
        $this->valueHandler = $valueHandler;
    }

    public function mapName(string $property): string
    {
        return $this->prefix . ucfirst($property);
    }

    public function transformValue(
        string $propertyName,
        string $targetName,
        mixed $value,
        mixed $instance = null
    ): mixed {
        if ($this->valueHandler !== null) {
            return ($this->valueHandler)($propertyName, $targetName, $value, $instance);
        }
        return $value;
    }
}
```

Usage:

```php
$source = ['name' => 'John', 'age' => 30];
$target = new stdClass();

ObjectCopy::copy($source, $target, new PrefixPropertyHandler('user'));

// Result:
// $target->userName = 'John';
// $target->userAge = 30;
```

### Example: JSON Property Handler

Handle JSON strings in properties:

```php
namespace MyApp\PropertyHandlers;

use ByJG\Serializer\PropertyHandler\DirectTransform;

class JsonPropertyHandler extends DirectTransform
{
    private array $jsonProperties;

    public function __construct(array $jsonProperties, ?Closure $valueHandler = null)
    {
        parent::__construct($valueHandler);
        $this->jsonProperties = $jsonProperties;
    }

    public function transformValue(
        string $propertyName,
        string $targetName,
        mixed $value,
        mixed $instance = null
    ): mixed {
        // Decode JSON properties
        if (in_array($propertyName, $this->jsonProperties) && is_string($value)) {
            return json_decode($value, true);
        }

        // Apply custom value handler if provided
        return parent::transformValue($propertyName, $targetName, $value, $instance);
    }
}
```

Usage:

```php
$source = [
    'id' => 1,
    'name' => 'John',
    'metadata' => '{"role":"admin","level":5}',
    'settings' => '{"theme":"dark","notifications":true}'
];

$target = new stdClass();

$handler = new JsonPropertyHandler(['metadata', 'settings']);
ObjectCopy::copy($source, $target, $handler);

// Result:
// $target->id = 1;
// $target->name = 'John';
// $target->metadata = ['role' => 'admin', 'level' => 5];
// $target->settings = ['theme' => 'dark', 'notifications' => true];
```

### Example: Type Coercion Handler

Enforce type coercion based on target property types:

```php
namespace MyApp\PropertyHandlers;

use ByJG\Serializer\PropertyHandler\DirectTransform;
use ReflectionClass;
use ReflectionProperty;

class TypeCoercionHandler extends DirectTransform
{
    private ReflectionClass $targetReflection;

    public function __construct(object $target, ?Closure $valueHandler = null)
    {
        parent::__construct($valueHandler);
        $this->targetReflection = new ReflectionClass($target);
    }

    public function transformValue(
        string $propertyName,
        string $targetName,
        mixed $value,
        mixed $instance = null
    ): mixed {
        // Get target property type
        if (!$this->targetReflection->hasProperty($targetName)) {
            return parent::transformValue($propertyName, $targetName, $value, $instance);
        }

        $property = $this->targetReflection->getProperty($targetName);
        $type = $property->getType();

        if ($type === null) {
            return parent::transformValue($propertyName, $targetName, $value, $instance);
        }

        // Coerce value to target type
        $typeName = $type->getName();

        return match ($typeName) {
            'int' => (int)$value,
            'float' => (float)$value,
            'string' => (string)$value,
            'bool' => (bool)$value,
            'array' => is_array($value) ? $value : [$value],
            default => parent::transformValue($propertyName, $targetName, $value, $instance)
        };
    }
}
```

Usage:

```php
class StrictUser {
    public int $id;
    public string $name;
    public bool $active;
}

$source = [
    'id' => '123',      // String that should be int
    'name' => 456,      // Number that should be string
    'active' => 1       // Int that should be bool
];

$target = new StrictUser();
$handler = new TypeCoercionHandler($target);
ObjectCopy::copy($source, $target, $handler);

// Result:
// $target->id = 123 (int);
// $target->name = '456' (string);
// $target->active = true (bool);
```

## Best Practices

### 1. Choose the Right Handler

- **DirectTransform**: When property names are already aligned
- **CamelToSnakeCase**: Converting from PHP objects to database format
- **SnakeToCamelCase**: Converting from database results to PHP objects
- **PropertyNameMapper**: Complex mappings or API integration

### 2. Use Value Handlers for Business Logic

Keep name mapping separate from value transformation:

```php
// Good - clear separation
$mapper = new PropertyNameMapper(['old_field' => 'newField']);

$valueHandler = function ($prop, $target, $value, $instance) {
    // Business logic here
    return processValue($value);
};

$handler = new PropertyNameMapper(['old_field' => 'newField'], $valueHandler);
```

### 3. Access $instance Sparingly

The `$instance` parameter is powerful but has performance implications:

```php
// Less efficient - accesses instance for every property
$handler = function ($prop, $target, $value, $instance) {
    return $instance->$prop . '_suffix';
};

// More efficient - only access instance when needed
$handler = function ($prop, $target, $value, $instance) {
    if ($target === 'fullName' && $instance !== null) {
        return $instance->firstName . ' ' . $instance->lastName;
    }
    return $value;
};
```

### 4. Handle Edge Cases

Always check for null values and missing properties:

```php
$handler = function ($prop, $target, $value, $instance) {
    if ($value === null) {
        return $value;  // Or provide default
    }

    if ($target === 'computed' && $instance !== null) {
        $field1 = $instance->field1 ?? '';  // Use null coalescing
        $field2 = $instance->field2 ?? '';
        return $field1 . $field2;
    }

    return $value;
};
```

### 5. Type Safety

Use type hints and checks:

```php
$handler = function ($prop, $target, $value, $instance) {
    if ($target === 'age' && is_numeric($value)) {
        return (int)$value;
    }

    if ($target === 'createdAt' && is_string($value)) {
        return new DateTime($value);
    }

    return $value;
};
```

## Performance Considerations

### Caching Property Handlers

If you're copying many objects of the same type, reuse the handler instance:

```php
// Good - reuse handler
$handler = new SnakeToCamelCase();
foreach ($rows as $row) {
    $user = new User();
    ObjectCopy::copy($row, $user, $handler);
    $users[] = $user;
}

// Less efficient - creates new handler each time
foreach ($rows as $row) {
    $user = new User();
    ObjectCopy::copy($row, $user, new SnakeToCamelCase());
    $users[] = $user;
}
```

### Avoid Complex Logic in Value Handlers

Keep value handlers simple and fast:

```php
// Good - simple transformation
$handler = function ($prop, $target, $value, $instance) {
    return is_string($value) ? trim($value) : $value;
};

// Less ideal - complex database query
$handler = function ($prop, $target, $value, $instance) {
    if ($target === 'userId') {
        // DON'T DO THIS - database call for each property
        return $db->query("SELECT id FROM users WHERE name = ?", $value);
    }
    return $value;
};
```

### Using $instance Parameter

The `$instance` parameter has overhead. Only use it when necessary:

```php
// Efficient - doesn't use $instance
$handler = function ($prop, $target, $value, $instance) {
    return strtoupper($value);
};

// Less efficient - uses $instance unnecessarily
$handler = function ($prop, $target, $value, $instance) {
    return isset($instance->$prop) ? strtoupper($instance->$prop) : '';
};
```

## Common Patterns

### API Response Transformation

```php
// API returns snake_case, app uses camelCase
$apiResponse = [
    'user_id' => 123,
    'created_at' => '2024-01-01',
    'is_active' => true
];

$handler = new SnakeToCamelCase(
    function ($prop, $target, $value, $instance) {
        // Convert timestamps
        if (str_ends_with($target, 'At') && is_string($value)) {
            return new DateTime($value);
        }
        return $value;
    }
);

$user = new User();
ObjectCopy::copy($apiResponse, $user, $handler);
```

### Form Data Processing

```php
// Form data with prefix, remove prefix
$formData = [
    'user_name' => 'John',
    'user_email' => 'john@example.com',
    'user_age' => '30'
];

$handler = new PropertyNameMapper([
    'user_name' => 'name',
    'user_email' => 'email',
    'user_age' => 'age'
], function ($prop, $target, $value, $instance) {
    // Sanitize and type cast
    if ($target === 'email') {
        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }
    if ($target === 'age') {
        return (int)$value;
    }
    return htmlspecialchars($value);
});
```

### Legacy System Integration

```php
// Legacy system uses different naming conventions
$legacyData = [
    'ID' => 1,
    'NAME' => 'JOHN DOE',
    'ADDR' => '123 Main St'
];

$handler = new PropertyNameMapper([
    'ID' => 'id',
    'NAME' => 'name',
    'ADDR' => 'address'
], function ($prop, $target, $value, $instance) {
    // Normalize case
    if (is_string($value)) {
        return ucwords(strtolower($value));
    }
    return $value;
});
```

## Related Components

- [ObjectCopy](objectcopy.md) - Uses property handlers for copying
- [BaseModel](basemodel.md) - Supports property handlers in constructor
- [DirectTransform](directtransform.md) - Base implementation of PropertyHandlerInterface
