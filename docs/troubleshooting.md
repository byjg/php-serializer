---
sidebar_position: 10
---

# Troubleshooting

This guide helps you diagnose and resolve common issues when using the PHP Serializer library.

## Common Issues

### Serialization Issues

#### Problem: Properties Not Being Serialized

**Symptoms:**
- Expected properties are missing from serialized output
- Empty arrays or partial data returned

**Possible Causes:**

**1. Properties are private without getter methods**

```php
class User {
    private $id = 1;        // No getter - won't be serialized
    private $name = 'John'; // No getter - won't be serialized
}

$result = Serialize::from(new User())->toArray();
// Result: [] (empty)
```

**Solution:** Add getter methods with the correct prefix (default is 'get'):

```php
class User {
    private $id = 1;
    private $name = 'John';

    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
}

$result = Serialize::from(new User())->toArray();
// Result: ['id' => 1, 'name' => 'John']
```

**2. Using withIgnoreProperties() accidentally**

```php
// Problem: password property ignored, but also ignoring 'name' by mistake
$result = Serialize::from($user)
    ->withIgnoreProperties(['password', 'name'])  // Oops!
    ->toArray();
```

**Solution:** Review your ignore list:

```php
$result = Serialize::from($user)
    ->withIgnoreProperties(['password'])  // Only ignore what's needed
    ->toArray();
```

**3. Using withStopAtFirstLevel() when you need nested data**

```php
// Problem: nested objects not expanded
$result = Serialize::from($user)
    ->withStopAtFirstLevel()  // Stops at first level
    ->toArray();

// address object is not expanded
```

**Solution:** Remove `withStopAtFirstLevel()` if you need nested data:

```php
$result = Serialize::from($user)->toArray();
// Now nested objects are fully serialized
```

**4. Null values with withDoNotParseNullValues()**

```php
$user = new User();
$user->name = null;

$result = Serialize::from($user)
    ->withDoNotParseNullValues()  // Ignores null values
    ->toArray();
// 'name' property missing because it's null
```

**Solution:** Only use `withDoNotParseNullValues()` when you intentionally want to exclude nulls.

#### Problem: Getter Method Pattern Not Matching

**Symptoms:**
- Properties with non-standard getter names not being serialized
- Custom getter prefix not working

**Example:**

```php
class User {
    private $name = 'John';

    public function fetchName() { return $this->name; }  // Using 'fetch' not 'get'
}

$result = Serialize::from(new User())->toArray();
// Result: [] (empty - getter not found)
```

**Solution:** Configure the getter prefix:

```php
$result = Serialize::from(new User())
    ->withMethodGetPrefix('fetch')  // Use 'fetch' instead of 'get'
    ->toArray();
// Result: ['name' => 'John']
```

#### Problem: Property Name Mismatch

**Symptoms:**
- Property names in output don't match expectations
- CamelCase/snake_case confusion

**Example:**

```php
class User {
    private $user_name = 'John';  // snake_case property

    public function getUserName() { return $this->user_name; }
}

$result = Serialize::from(new User())->toArray();
// Result: ['userName' => 'John']  // CamelCase in output
```

**Explanation:** By default, the getter method name determines the output key. `getUserName()` becomes `userName`.

**Solution:** Use `withMethodPattern()` to customize the transformation:

```php
// Keep underscores in property names
$result = Serialize::from(new User())
    ->withMethodPattern('/^get/', '')  // Remove 'get' prefix only
    ->toArray();
```

### ObjectCopy Issues

#### Problem: Properties Not Being Copied

**Symptoms:**
- Target object properties remain null or uninitialized
- Only some properties are copied

**Possible Causes:**

**1. Property name mismatch**

```php
class Source {
    public $userName = 'John';
}

class Target {
    public $user_name;  // Different naming convention
}

$source = new Source();
$target = new Target();
ObjectCopy::copy($source, $target);

// Result: $target->user_name is null (names don't match)
```

**Solution:** Use a property handler:

```php
ObjectCopy::copy($source, $target, new CamelToSnakeCase());
// Now $target->user_name = 'John'
```

**2. Target property doesn't exist**

```php
$source = ['id' => 1, 'name' => 'John', 'extra' => 'data'];

class Target {
    public $id;
    public $name;
    // No 'extra' property
}

$target = new Target();
ObjectCopy::copy($source, $target);
// 'extra' is silently ignored (expected behavior)
```

**This is expected behavior** - extra source properties are ignored if the target doesn't have them.

**3. Target has private/protected properties without setters**

```php
class Target {
    private $id;  // No setter method
    private $name;  // No setter method
}

$source = ['id' => 1, 'name' => 'John'];
$target = new Target();
ObjectCopy::copy($source, $target);
// Properties remain null - no way to set them
```

**Solution:** Add setter methods or make properties public:

```php
class Target {
    private $id;
    private $name;

    public function setId($id) { $this->id = $id; }
    public function setName($name) { $this->name = $name; }
}
```

#### Problem: Value Transformation Not Working

**Symptoms:**
- Custom value handler not being called
- Values not transformed as expected

**Example:**

```php
$handler = new SnakeToCamelCase(
    function ($prop, $target, $value, $instance) {
        echo "Handler called for: $prop\n";  // Never prints
        return strtoupper($value);
    }
);

ObjectCopy::copy($source, $target, $handler);
```

**Possible Causes:**

**1. Property doesn't exist in target**

If the target doesn't have a matching property, the handler is never called.

**Solution:** Verify target has the property:

```php
class Target {
    public $firstName;  // Make sure this exists
    public $lastName;
}
```

**2. Source property is null**

Value handlers are still called for null values, but make sure you handle them:

```php
$handler = function ($prop, $target, $value, $instance) {
    if ($value === null) {
        return null;  // Or provide a default
    }
    return strtoupper($value);
};
```

### JSON/XML/YAML Output Issues

#### Problem: JSON Encoding Fails

**Symptoms:**
- `toJson()` returns false or empty
- JSON is malformed

**Example:**

```php
$result = Serialize::from($data)->toJson();
// Returns: false or empty string
```

**Possible Causes:**

**1. Invalid UTF-8 characters**

```php
class User {
    public $name = "John\xA0Doe";  // Invalid UTF-8
}

$json = Serialize::from(new User())->toJson();
// Fails due to encoding issues
```

**Solution:** Clean the data first:

```php
$cleanedData = Serialize::from($user)
    ->parseAttributes(
        function ($attr, $value, $key, $prop, $getter) {
            if (is_string($value)) {
                return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
            return $value;
        },
        null
    );

$json = json_encode($cleanedData);
```

**2. Circular references**

```php
class User {
    public $name;
    public $company;
}

class Company {
    public $name;
    public $owner;
}

$user = new User();
$company = new Company();
$user->company = $company;
$company->owner = $user;  // Circular reference

// This will cause issues or infinite recursion
$json = Serialize::from($user)->toJson();
```

**Solution:** Break the cycle:

```php
$json = Serialize::from($user)
    ->withStopAtFirstLevel()
    ->toJson();

// Or ignore the circular property
$json = Serialize::from($user)
    ->withIgnoreProperties(['company'])
    ->toJson();
```

**3. Resource types**

```php
class FileHandler {
    public $handle;

    public function __construct() {
        $this->handle = fopen('file.txt', 'r');  // Resource type
    }
}

$json = Serialize::from(new FileHandler())->toJson();
// Resources cannot be JSON encoded
```

**Solution:** Ignore resource properties or convert them:

```php
$json = Serialize::from($handler)
    ->withIgnoreProperties(['handle'])
    ->toJson();
```

#### Problem: XML Output is Malformed

**Symptoms:**
- XML parsing errors
- Missing closing tags
- Invalid XML structure

**Possible Causes:**

**1. Special characters not escaped**

This is automatically handled by XmlFormatter, but if you see issues:

```php
class Product {
    public $name = "Product <Special> & \"Quoted\"";
}

$xml = Serialize::from(new Product())->toXml();
// XmlFormatter automatically escapes special characters
```

**2. Invalid XML element names**

```php
$data = [
    '123invalid' => 'value',  // Element names can't start with numbers
    'my-element' => 'value',  // Hyphens are valid
    'my.element' => 'value',  // Dots are valid
];

$xml = (new XmlFormatter())->process($data);
// May produce invalid XML for numeric keys
```

**Solution:** Transform keys before formatting:

```php
$cleaned = array_map(
    function($key, $value) {
        $key = preg_replace('/^[0-9]/', '_$0', $key);  // Prefix numbers
        return [$key => $value];
    },
    array_keys($data),
    $data
);
```

#### Problem: CSV Export Missing Columns

**Symptoms:**
- CSV has fewer columns than expected
- Headers don't match data

**Cause:** CSV formatter uses the first array element to determine headers:

```php
$data = [
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25, 'city' => 'NYC'],  // Extra column
];

$csv = Serialize::from($data)->toCsv();
// 'city' column missing because first row doesn't have it
```

**Solution:** Ensure all rows have the same keys:

```php
$data = [
    ['name' => 'John', 'age' => 30, 'city' => null],
    ['name' => 'Jane', 'age' => 25, 'city' => 'NYC'],
];

$csv = Serialize::from($data)->toCsv();
// Now all columns included
```

### Performance Issues

#### Problem: Slow Serialization

**Symptoms:**
- Serialization takes several seconds
- High CPU usage during serialization

**Possible Causes:**

**1. Deeply nested objects without withStopAtFirstLevel()**

```php
// Slow - serializes entire object graph
$result = Serialize::from($deeplyNested)->toArray();
```

**Solution:** Limit depth:

```php
$result = Serialize::from($deeplyNested)
    ->withStopAtFirstLevel()
    ->toArray();
```

**2. Serializing large collections**

```php
// Slow - 10,000 objects with nested properties
$users = $repository->findAll();  // 10,000 users
$result = Serialize::from($users)->toArray();
```

**Solution:** Batch processing or pagination:

```php
$page = 1;
$pageSize = 100;

while ($users = $repository->findBy([], null, $pageSize, ($page - 1) * $pageSize)) {
    $batch = Serialize::from($users)
        ->withStopAtFirstLevel()
        ->toArray();

    // Process batch
    processBatch($batch);

    $page++;
}
```

**3. Creating new property handlers in loops**

```php
// Inefficient
foreach ($users as $user) {
    ObjectCopy::copy($user, new UserDTO(), new SnakeToCamelCase());
}
```

**Solution:** Reuse property handler:

```php
$handler = new SnakeToCamelCase();
foreach ($users as $user) {
    $dto = new UserDTO();
    ObjectCopy::copy($user, $dto, $handler);
    $dtos[] = $dto;
}
```

#### Problem: High Memory Usage

**Symptoms:**
- PHP memory limit errors
- Memory usage grows continuously

**Possible Causes:**

**1. Holding references to serialized data**

```php
$results = [];
foreach ($largeDataset as $item) {
    $results[] = Serialize::from($item)->toArray();  // Accumulating in memory
}
```

**Solution:** Process and discard:

```php
foreach ($largeDataset as $item) {
    $result = Serialize::from($item)->toArray();

    // Process immediately
    sendToApi($result);

    // Don't keep reference
    unset($result);
}
```

**2. Circular references preventing garbage collection**

```php
// Objects with circular references aren't garbage collected
$user->company = $company;
$company->owner = $user;
```

**Solution:** Break references when done:

```php
$result = Serialize::from($user)
    ->withStopAtFirstLevel()
    ->toArray();

// Break circular reference
$user->company = null;
$company->owner = null;

unset($user, $company);
```

### Type-Related Issues

#### Problem: DateTime Objects Not Formatted Correctly

**Symptoms:**
- DateTime objects serialized as arrays
- Date format not as expected

**Example:**

```php
class Event {
    public DateTime $date;
}

$event = new Event();
$event->date = new DateTime('2024-01-01');

$result = Serialize::from($event)->toArray();
// Result: ['date' => ['date' => '2024-01-01 00:00:00', 'timezone' => ...]]
```

**Solution:** Use `parseAttributes()` to format:

```php
$result = Serialize::from($event)->parseAttributes(
    function ($attr, $value, $key, $prop, $getter) {
        if ($value instanceof DateTime) {
            return $value->format('Y-m-d');
        }
        return $value;
    },
    null
);
// Result: ['date' => '2024-01-01']
```

#### Problem: Boolean Values Converting to Strings

**Symptoms:**
- Boolean `false` becomes `""` (empty string)
- Boolean `true` becomes `"1"`

**Cause:** Using `withOnlyString()`:

```php
$data = ['active' => true, 'deleted' => false];

$result = Serialize::from($data)
    ->withOnlyString()
    ->toArray();
// Result: ['active' => '1', 'deleted' => '']
```

**Solution:** Only use `withOnlyString()` when you specifically need string output. Otherwise, remove it:

```php
$result = Serialize::from($data)->toArray();
// Result: ['active' => true, 'deleted' => false]
```

#### Problem: Integer IDs Becoming Strings

**Symptoms:**
- Numeric IDs converted to strings
- Type checking fails

**Cause:** Using `withOnlyString()` or CSV import:

```php
$csv = "id,name\n1,John\n2,Jane";
$result = Serialize::fromCsv($csv)->toArray();
// Result: [['id' => '1', 'name' => 'John'], ...]
// IDs are strings, not integers
```

**Solution:** Use value handler to cast types:

```php
$data = Serialize::fromCsv($csv)->toArray();

$handler = new DirectTransform(
    function ($prop, $target, $value, $instance) {
        if ($target === 'id' && is_numeric($value)) {
            return (int)$value;
        }
        return $value;
    }
);

$typed = [];
foreach ($data as $row) {
    $obj = new stdClass();
    ObjectCopy::copy($row, $obj, $handler);
    $typed[] = $obj;
}
```

## Debugging Tips

### Enable Error Reporting

```php
// Enable all errors during development
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Inspect Intermediate Results

```php
// Check what's being serialized
$array = Serialize::from($user)->toArray();
var_dump($array);  // Inspect before formatting

$json = json_encode($array);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON Error: " . json_last_error_msg() . "\n";
    var_dump($array);
}
```

### Check Property Accessibility

```php
// Verify getter methods exist
$reflection = new ReflectionClass($user);
$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

foreach ($methods as $method) {
    if (str_starts_with($method->getName(), 'get')) {
        echo $method->getName() . "\n";
    }
}
```

### Test with Simple Data First

```php
// Start simple
$simple = ['id' => 1, 'name' => 'test'];
$result = Serialize::from($simple)->toJson();
echo $result . "\n";

// Gradually add complexity
$withObject = new stdClass();
$withObject->id = 1;
$withObject->name = 'test';
$result = Serialize::from($withObject)->toJson();
echo $result . "\n";
```

### Use Type Declarations

```php
// Catch type errors early
class User {
    public int $id;          // Type declaration
    public string $name;      // Type declaration
    public ?string $email;    // Nullable type

    // PHP will throw TypeError if wrong type assigned
}
```

## Getting Help

If you're still experiencing issues:

1. **Check the documentation** - Review the relevant docs section
2. **Review examples** - Look at test files for usage patterns
3. **Simplify the problem** - Create a minimal reproducible example
4. **Check GitHub issues** - Search for similar issues at https://github.com/byjg/php-serializer/issues
5. **Report a bug** - Open a new issue with a reproducible example

When reporting issues, include:
- PHP version
- Library version
- Minimal code example
- Expected vs actual output
- Error messages (if any)

## Related Components

- [Serialize](serialize.md) - Main serialization class
- [ObjectCopy](objectcopy.md) - Object copying utility
- [Formatters](formatters.md) - Output formatting
- [Property Handlers](propertyhandlers.md) - Property transformation
- [Advanced Usage](advanced-usage.md) - Performance and security
