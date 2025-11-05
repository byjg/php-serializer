---
sidebar_position: 2
---

# ObjectCopy class

The `ObjectCopy` class is a final utility class used to copy the contents from one object to another.

The target object doesn't need to have the same properties as the source object, as you can apply transformations 
that allow you to match the source and target.

```php
ObjectCopy::copy(
    object|array $source, 
    object|array $target, 
    ?PropertyHandlerInterface $propertyHandler = null
): void
```

## Examples

### Copy contents from one object to another

```php
$source = [ "idModel" => 1 , "clientName" => "John", "age" => 30 ];
class Target
{
    public $idModel;
    public $clientName;
    public $age;
}

$target = new Target();
ObjectCopy::copy($source, $target);
```

### Copy from CamelCase properties to snake_case properties

```php
class Source
{
    public $idModel;
    public $clientName;
    public $age;
}

class Target
{
    public $id_model;
    public $client_name;
    public $age;
}

$source = new Source();
$source->idModel = 1;
$source->clientName = 'John';
$source->age = 30;

$target = new Target();
ObjectCopy::copy($source, $target, new CamelToSnakeCase());
```

### Copy from snake_case properties to CamelCase properties

```php
class Source
{
    public $id_model;
    public $client_name;
    public $age;
}

class Target
{
    public $idModel;
    public $clientName;
    public $age;
}

$source = new Source();
$source->id_model = 1;
$source->client_name = 'John';
$source->age = 30;

$target = new Target();
ObjectCopy::copy($source, $target, new SnakeToCamelCase());
```

### Copy contents and use a map to match properties

```php
class Source
{
    public $id_model;
    public $client_name;
    public $age;
}

class Target
{
    public $SomeId;
    public $SomeName;
    public $SomeAge;
}

$source = new Source();
$source->id_model = 1;
$source->client_name = 'John';
$source->age = 30;

$target = new Target();
ObjectCopy::copy(
    $source,
    $target,
    new PropertyNameMapper([
        "id_model" => "SomeId", 
        "client_name" => "SomeName", 
        "age" => "SomeAge"
    ])
);
```

### Property Mapping with Value Transformation

You can transform values during property copying by passing a closure to the property handler constructor:

```php
$source = new Source();
$source->id_model = 1;
$source->client_name = 'John';
$source->age = 30;

$target = new Target();

// Value transformation function
$valueHandler = function ($propertyName, $targetName, $value) {
    if ($targetName === 'clientName') {
        return strtoupper($value);
    }
    if ($targetName === 'age') {
        return $value + 1;
    }
    return $value;
};

// Apply both property mapping and value transformation
ObjectCopy::copy($source, $target, new SnakeToCamelCase($valueHandler));

// Result:
// $target->idModel = 1;
// $target->clientName = 'JOHN';
// $target->age = 31;
```

The `valueHandler` closure receives:
- `$propertyName`: The original property name from the source object
- `$targetName`: The mapped property name for the target (after applying the name mapping)
- `$value`: The value from the source object
- `$instance`: The complete source object instance (optional)

Example with date formatting:
```php
// Source has dates as strings, but target needs DateTime objects
$valueHandler = function ($propertyName, $targetName, $value) {
    if (in_array($targetName, ['createdAt', 'updatedAt']) && is_string($value)) {
        return new DateTime($value);
    }
    return $value;
};

ObjectCopy::copy($source, $target, new SnakeToCamelCase($valueHandler));
```

Example with access to other properties in the source object:
```php
// Use other properties from the source object when transforming values
$valueHandler = function ($propertyName, $targetName, $value, $source) {
    if ($targetName === 'fullName') {
        return $source->firstName . ' ' . $source->lastName;
    }
    return $value;
};

ObjectCopy::copy($source, $target, new PropertyNameMapper([
    "firstName" => "fullName"
], $valueHandler));
```

## Available Property Handler Classes

The library provides several built-in property handler classes for common transformations:

- **DirectTransform** - Identity mapping (no property name changes)
- **CamelToSnakeCase** - Converts camelCase to snake_case
- **SnakeToCamelCase** - Converts snake_case to camelCase
- **PropertyNameMapper** - Maps specific properties using a custom array

All property handlers support optional value transformation via a closure parameter.

For complete documentation including:
- Detailed usage examples for each handler
- Value transformation patterns
- Creating custom property handlers
- PropertyHandlerInterface reference
- Best practices and performance tips

See the **[Property Handlers Guide](propertyhandlers.md)**.

## Related Components

- [Property Handlers](propertyhandlers.md) - Complete guide to property transformation
- [ObjectCopyInterface](objectcopyinterface.md) - Interface for defining copyable objects
- [ObjectCopyTrait](objectcopytrait.md) - Trait implementing the interface methods
- [BaseModel](basemodel.md) - Abstract base class with copying functionality
- [DirectTransform](directtransform.md) - Base property handler class