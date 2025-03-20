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

## Available Property Pattern Classes

The library provides several built-in property pattern classes:

1. **CamelToSnakeCase**: Converts camelCase property names to snake_case
   ```php
   // Example: "idModel" becomes "id_model"
   ObjectCopy::copy($source, $target, new CamelToSnakeCase());
   ```

2. **SnakeToCamelCase**: Converts snake_case property names to camelCase
   ```php
   // Example: "id_model" becomes "idModel"
   ObjectCopy::copy($source, $target, new SnakeToCamelCase());
   ```

3. **DifferentTargetProperty**: Maps source properties to different target properties using an array
   ```php
   ObjectCopy::copy($source, $target, new DifferentTargetProperty([
       "sourceProperty" => "targetProperty"
   ]));
   ```

You can also create your own property pattern class by implementing the `PropertyPatternInterface`.
