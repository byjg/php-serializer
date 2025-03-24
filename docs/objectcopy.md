---
sidebar_position: 2
---

# ObjectCopy class

The `ObjectCopy` class is used to copy the contents from one object to another.

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

The library provides several built-in property handler classes:

1. **CamelToSnakeCase**: Converts camelCase property names to snake_case
   ```php
   use ByJG\Serializer\ObjectCopy;
   use ByJG\Serializer\PropertyHandler\CamelToSnakeCase;
   
   // Basic usage: "idModel" becomes "id_model"
   ObjectCopy::copy($source, $target, new CamelToSnakeCase());
   
   // With value transformation
   $valueHandler = function ($propName, $targetName, $value, $source = null) {
       // Transform values here, with optional access to full source object
       return $value;
   };
   ObjectCopy::copy($source, $target, new CamelToSnakeCase($valueHandler));
   ```

2. **SnakeToCamelCase**: Converts snake_case property names to camelCase
   ```php
   use ByJG\Serializer\ObjectCopy;
   use ByJG\Serializer\PropertyHandler\SnakeToCamelCase;
   
   // Basic usage: "id_model" becomes "idModel"
   ObjectCopy::copy($source, $target, new SnakeToCamelCase());
   
   // With value transformation
   $valueHandler = function ($propName, $targetName, $value, $source = null) {
       // Transform values here, with optional access to full source object
       return $value;
   };
   ObjectCopy::copy($source, $target, new SnakeToCamelCase($valueHandler));
   ```

3. **PropertyNameMapper**: Maps source properties to different target properties using an array
   ```php
   use ByJG\Serializer\ObjectCopy;
   use ByJG\Serializer\PropertyHandler\PropertyNameMapper;
   
   // Basic usage
   ObjectCopy::copy(
       $source, 
       $target, 
       new PropertyNameMapper([
           "sourceProperty" => "targetProperty",
           "firstName" => "givenName",
           "lastName" => "familyName"
       ])
   );
   
   // With value transformation
   $valueHandler = function ($propName, $targetName, $value, $source = null) {
       // Transform values here, with optional access to full source object
       if ($targetName === 'givenName' && $source !== null) {
           // You can access other properties in the source object
           return ucfirst($value) . ' (' . $source->nickname . ')';
       }
       return $value;
   };
   ObjectCopy::copy(
       $source, 
       $target, 
       new PropertyNameMapper([
           "sourceProperty" => "targetProperty",
           "firstName" => "givenName",
           "lastName" => "familyName"
       ], $valueHandler)
   );
   ```

## Creating Custom Property Handler Classes

You can create your own property handler class by implementing the `PropertyHandlerInterface`:

```php
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;

class MyCustomPropertyHandler implements PropertyHandlerInterface
{
    private ?Closure $valueHandler;
    
    public function __construct(?Closure $valueHandler = null)
    {
        $this->valueHandler = $valueHandler;
    }
    
    public function mapName(string $property): string
    {
        // Implement your custom property name mapping logic here
        return "prefix_" . $property;
    }
    
    public function transformValue(string $propertyName, string $targetName, mixed $value, mixed $instance = null): mixed
    {
        // Apply custom value transformation
        if ($this->valueHandler !== null) {
            return ($this->valueHandler)($propertyName, $targetName, $value, $instance);
        }
        
        // Or implement your own logic without a closure
        if ($targetName === 'prefix_age') {
            return (int)$value * 2;
        }
        
        // You can also use other properties from the source object
        if ($targetName === 'prefix_fullName' && $instance !== null) {
            // Access other properties from the source object
            $firstName = isset($instance->firstName) ? $instance->firstName : '';
            $lastName = isset($instance->lastName) ? $instance->lastName : '';
            return trim($firstName . ' ' . $lastName);
        }
        
        return $value;
    }
}

// Then use it:
ObjectCopy::copy($source, $target, new MyCustomPropertyHandler());

// Or with a custom value handler:
$valueHandler = function ($propName, $targetName, $value, $source = null) {
    // Custom transformations that can access other properties in $source
    return $value;
};
ObjectCopy::copy($source, $target, new MyCustomPropertyHandler($valueHandler));
```