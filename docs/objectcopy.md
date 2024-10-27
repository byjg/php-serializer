# ObjectCopy class

The class ObjectCopy is used to copy the contents from one object to another.

The target object doesn't need to have the same properties as the source object 
as you can apply transformations that allow you match source and target. 

```php
Object::copy(
    object|array $source, 
    object|array $target, 
    PropertyPatternInterface|Closure|null $propertyPattern = null, 
    Closure $changeValue = null
): void
```

## Examples

### Copy contents from an object to another

```php
$soruce = [ "idModel" => 1 , "clientName" => "John", "age" => 30 ];
class Target
{
    public $idModel;
    public $clientName;
    public $age;
}

$source = new Source(...);
$target = new Target();
ObjectCopy::copy($source, $target);
```

### Copy from CamelCase properties to another with snake_case properties

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

ObjectCopy::copy($source, $target, new CamelToSnakeCase());
```

### Copy from snake_case properties to another with CamelCase properties

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

ObjectCopy::copy($source, $target, new SnakeToCamelCase());
```

### Copy contents and use a map to match the properties

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

### Object Copy Special Cases

```php
$propertyPattern = function ($propertyName) {
    // Execute the logic to match the property name in the target
    // ex: change case, change name, different setter, etc
};

$changeValue = function ($sourceName, $targetName, $valueFound) {
    // Execute the logic to change the value before set in the target
    // ex: change the date format, change the value, etc
};

Object::copy(
    object|array $source, 
    object|array $target, 
    PropertyPatternInterface|Closure|null $propertyPattern = null, 
    Closure $changeValue = null
): void
```
