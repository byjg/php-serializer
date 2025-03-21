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
    PropertyPatternInterface|Closure|null $propertyPattern = null, 
    Closure $changeValue = null
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
    new DifferentTargetProperty([
        "id_model" => "SomeId", 
        "client_name" => "SomeName", 
        "age" => "SomeAge"
    ])
);
```

### Custom Property Mapping and Value Transformation

You can use closures to customize how properties are mapped and values are transformed:

```php
// Custom property mapping
$propertyPattern = function ($propertyName) {
    // Execute logic to match the property name in the target
    // ex: change case, change name, different setter, etc.
    return 'custom_' . $propertyName;
};

// Custom value transformation
$changeValue = function ($sourceName, $targetName, $valueFound) {
    // Execute logic to change the value before setting it in the target
    // ex: change the date format, modify the value, etc.
    return strtoupper($valueFound);
};

$source = new Source();
$target = new Target();

ObjectCopy::copy(
    $source, 
    $target, 
    $propertyPattern,
    $changeValue
);
```

The `changeValue` parameter is a closure that gets called for each property with:
- `$sourceName`: The original property name from the source object
- `$targetName`: The mapped property name for the target (after applying the property pattern)
- `$valueFound`: The value from the source object

This allows you to transform values during the copying process, such as:
- Converting data types (string to int, etc.)
- Formatting dates or numbers
- Applying string transformations
- Creating complex objects from simple values

Example with date formatting:
```php
// Source has dates as strings, but target needs DateTime objects
$changeValue = function ($sourceName, $targetName, $valueFound) {
    if (in_array($targetName, ['createdAt', 'updatedAt']) && is_string($valueFound)) {
        return new DateTime($valueFound);
    }
    return $valueFound;
};

ObjectCopy::copy($source, $target, null, $changeValue);
```

## Available Property Pattern Classes

The library provides several built-in property pattern classes:

1. **CamelToSnakeCase**: Converts camelCase property names to snake_case
   ```php
   use ByJG\Serializer\ObjectCopy;
   use ByJG\Serializer\PropertyPattern\CamelToSnakeCase;
   
   // Example: "idModel" becomes "id_model"
   ObjectCopy::copy($source, $target, new CamelToSnakeCase());
   ```

2. **SnakeToCamelCase**: Converts snake_case property names to camelCase
   ```php
   use ByJG\Serializer\ObjectCopy;
   use ByJG\Serializer\PropertyPattern\SnakeToCamelCase;
   
   // Example: "id_model" becomes "idModel"
   ObjectCopy::copy($source, $target, new SnakeToCamelCase());
   ```

3. **DifferentTargetProperty**: Maps source properties to different target properties using an array
   ```php
   use ByJG\Serializer\ObjectCopy;
   use ByJG\Serializer\PropertyPattern\DifferentTargetProperty;
   
   // Map specific property names from source to target
   ObjectCopy::copy(
       $source, 
       $target, 
       new DifferentTargetProperty([
           "sourceProperty" => "targetProperty",
           "firstName" => "givenName",
           "lastName" => "familyName"
       ])
   );
   ```

## Creating Custom Property Pattern Classes

You can create your own property pattern class by implementing the `PropertyPatternInterface`:

```php
use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;

class MyCustomPropertyPattern implements PropertyPatternInterface
{
    public function map(string $sourcePropertyName): string|null
    {
        // Implement your custom property name mapping logic here
        // Return the mapped property name for the target
        // or null if the property should be skipped
        return "prefix_" . $sourcePropertyName;
    }
}

// Then use it:
ObjectCopy::copy($source, $target, new MyCustomPropertyPattern());
```
