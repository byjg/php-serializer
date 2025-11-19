---
sidebar_position: 9
---

# Advanced Usage

This guide covers advanced usage patterns, performance optimization, security considerations, and integration strategies for the PHP Serializer library.

## Table of Contents

- [Performance Optimization](#performance-optimization)
- [Security Considerations](#security-considerations)
- [Integration Patterns](#integration-patterns)
- [Complex Data Structures](#complex-data-structures)
- [Error Handling](#error-handling)
- [Best Practices](#best-practices)

## Performance Optimization

### Internal Caching

The Serialize class implements automatic caching to improve performance when serializing multiple objects of the same type. The cache stores property metadata, ReflectionClass instances, and method existence checks. This provides significant performance benefits (3-5x speedup) when processing multiple objects of the same type.

For detailed information about caching behavior, performance impact, and cache lifetime, see the [Serialize class documentation - Performance and Caching section](serialize.md#performance-and-caching).

### Reusing Property Handlers

Property handlers should be reused when processing multiple objects:

```php
// Efficient - reuse handler instance
$handler = new SnakeToCamelCase();

foreach ($dbRows as $row) {
    $user = new User();
    ObjectCopy::copy($row, $user, $handler);
    $users[] = $user;
}

// Less efficient - creates new handler each iteration
foreach ($dbRows as $row) {
    $user = new User();
    ObjectCopy::copy($row, $user, new SnakeToCamelCase());
    $users[] = $user;
}
```

### Reusing Formatter Instances

Similarly, reuse formatter instances:

```php
// Efficient
$formatter = (new XmlFormatter())
    ->withRootElement("users")
    ->withListElement("user");

foreach ($batches as $batch) {
    $xml = $formatter->process($batch);
    // Process $xml
}

// Less efficient
foreach ($batches as $batch) {
    $xml = (new XmlFormatter())
        ->withRootElement("users")
        ->withListElement("user")
        ->process($batch);
}
```

### Stop at First Level

Use `withStopAtFirstLevel()` to avoid deep recursion when you only need top-level properties:

```php
class User {
    public $id;
    public $name;
    public $address;  // Complex object with many nested properties
    public $orders;   // Array of Order objects
}

// Only serialize first level - much faster
$result = Serialize::from($user)
    ->withStopAtFirstLevel()
    ->toArray();

// Result: id, name, address (object), orders (array)
// Nested properties of address and orders are not expanded
```

### Ignore Unnecessary Properties

Reduce processing time by ignoring properties you don't need:

```php
$result = Serialize::from($user)
    ->withIgnoreProperties([
        'internalCache',
        'tempData',
        'debugInfo',
        'largeBlob'
    ])
    ->toArray();
```

### Choosing the Right Format

Different formats have different performance characteristics:

```php
// Fastest to slowest for large datasets:
// 1. JSON - native PHP function, very fast
$json = Serialize::from($data)->toJson();

// 2. Array - no formatting overhead
$array = Serialize::from($data)->toArray();

// 3. CSV - efficient for tabular data
$csv = Serialize::from($data)->toCsv();

// 4. YAML - slower due to complex formatting
$yaml = Serialize::from($data)->toYaml();

// 5. XML - slowest due to DOM manipulation
$xml = Serialize::from($data)->toXml();
```

## Security Considerations

### Hiding Sensitive Data

Always hide sensitive properties before serialization:

```php
class User {
    public $id;
    public $username;
    private $password;
    private $apiKey;
    private $secretToken;

    public function getPassword() { return $this->password; }
    public function getApiKey() { return $this->apiKey; }
    public function getSecretToken() { return $this->secretToken; }
}

// Bad - exposes sensitive data
$userData = Serialize::from($user)->toJson();

// Good - explicitly ignore sensitive fields
$userData = Serialize::from($user)
    ->withIgnoreProperties(['password', 'apiKey', 'secretToken'])
    ->toJson();
```

### API Response Sanitization

Create dedicated DTO (Data Transfer Object) classes for API responses:

```php
class User {
    public $id;
    public $username;
    public $email;
    private $password;
    private $internalNotes;
}

class UserResponseDTO {
    public $id;
    public $username;
    public $email;
    // password and internalNotes not included
}

// Convert User to DTO
$user = $userRepository->find($id);
$dto = new UserResponseDTO();
ObjectCopy::copy($user, $dto);

// Serialize DTO (only safe fields included)
$response = Serialize::from($dto)->toJson();
```

### Unserializing User Input

**Never unserialize untrusted data with `unserialize()`:**

```php
// DANGEROUS - DO NOT DO THIS
$userInput = $_POST['data'];
$object = unserialize($userInput);  // CAN EXECUTE ARBITRARY CODE!

// Safe alternative - use JSON
$userInput = $_POST['data'];
$data = json_decode($userInput, true);

if (json_last_error() === JSON_ERROR_NONE) {
    $object = new MyClass();
    ObjectCopy::copy($data, $object);
}
```

### Preventing Information Disclosure

Use value handlers to sanitize data:

```php
$sanitizeHandler = function ($prop, $target, $value, $instance) {
    // Remove internal IDs
    if (str_starts_with($target, 'internal')) {
        return null;
    }

    // Truncate long strings
    if (is_string($value) && strlen($value) > 1000) {
        return substr($value, 0, 1000) . '...';
    }

    // Sanitize HTML
    if (in_array($target, ['description', 'bio', 'comment'])) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    return $value;
};

$result = Serialize::from($data)
    ->parseAttributes($sanitizeHandler, null);
```

### SQL Injection Prevention

When using serializer with database operations:

```php
// Bad - concatenating serialized data into SQL
$data = Serialize::from($object)->toJson();
$sql = "INSERT INTO data (json) VALUES ('$data')";  // VULNERABLE!

// Good - using prepared statements
$data = Serialize::from($object)->toJson();
$stmt = $pdo->prepare("INSERT INTO data (json) VALUES (?)");
$stmt->execute([$data]);
```

### Cross-Site Scripting (XSS) Prevention

Escape output when displaying serialized data:

```php
$json = Serialize::from($user)->toJson();

// Bad - directly echoing in HTML
echo "<div>$json</div>";  // VULNERABLE if $json contains </script>

// Good - proper escaping
echo '<div>' . htmlspecialchars($json, ENT_QUOTES, 'UTF-8') . '</div>';

// Even better - use Content-Type header for API responses
header('Content-Type: application/json');
echo $json;
```

## Integration Patterns

The PHP Serializer library integrates seamlessly with popular frameworks, ORMs, and libraries. For complete integration examples including:

- **ORM Integration**: Doctrine, Eloquent
- **Framework Integration**: Symfony, Laravel
- **API Integration**: REST clients and servers
- **Message Queue Integration**: RabbitMQ, Redis
- **Cache Integration**: PSR-6, PSR-16
- **GraphQL Integration**: Schema and resolvers

See the **[Integration Examples Guide](integration-examples.md)** for detailed, working examples.

## Complex Data Structures

### Circular References

The library does not automatically handle circular references. You must break the cycle:

```php
class User {
    public $id;
    public $name;
    public $company;  // References Company
}

class Company {
    public $id;
    public $name;
    public $users;  // References User[]
}

// Problem: circular reference
$user->company = $company;
$company->users[] = $user;

// Solution 1: Use withStopAtFirstLevel()
$result = Serialize::from($user)
    ->withStopAtFirstLevel()
    ->toArray();

// Solution 2: Ignore the circular property
$result = Serialize::from($user)
    ->withIgnoreProperties(['company'])
    ->toArray();

// Solution 3: Create DTOs without circular references
class UserDTO {
    public $id;
    public $name;
    public $companyId;  // Just the ID, not the full object
}
```

### Deeply Nested Objects

For deeply nested objects, consider performance implications:

```php
// Slow - serializes everything recursively
$result = Serialize::from($deeplyNestedObject)->toArray();

// Fast - only first level
$result = Serialize::from($deeplyNestedObject)
    ->withStopAtFirstLevel()
    ->toArray();

// Alternative - selective expansion
class OrderDTO {
    public $id;
    public $customer;  // Expand customer
    public $items;     // Expand items
    public $itemDetails;  // Don't expand item details

    public static function fromOrder(Order $order): self {
        $dto = new self();
        $dto->id = $order->getId();

        // Manually control expansion
        $dto->customer = Serialize::from($order->getCustomer())
            ->withStopAtFirstLevel()
            ->toArray();

        $dto->items = array_map(
            fn($item) => ['id' => $item->getId(), 'name' => $item->getName()],
            $order->getItems()
        );

        return $dto;
    }
}
```

### Collections and Iterators

```php
use Doctrine\Common\Collections\Collection;

// Handle Doctrine Collections
$result = Serialize::from($user)
    ->toArray();

// Collections are automatically converted to arrays
// But you may want to control the output:

$users = $company->getUsers();  // Collection

$userData = array_map(
    fn($user) => Serialize::from($user)
        ->withIgnoreProperties(['password'])
        ->toArray(),
    $users->toArray()
);
```

### Handling Special Types

```php
// DateTime objects
class Event {
    public DateTime $startDate;
    public DateTime $endDate;
}

$event = new Event();
$event->startDate = new DateTime('2024-01-01');
$event->endDate = new DateTime('2024-01-31');

// DateTime objects are serialized as arrays by default
$result = Serialize::from($event)->toArray();
// Result includes DateTime internal structure (not ideal)

// Better: use parseAttributes to format dates
$result = Serialize::from($event)->parseAttributes(
    function ($attr, $value, $keyName, $propName, $getterName) {
        if ($value instanceof DateTime) {
            return $value->format('Y-m-d H:i:s');
        }
        return $value;
    },
    null
);
```

## Error Handling

### JSON Encoding Errors

```php
try {
    $json = Serialize::from($data)->toJson();

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('JSON encoding failed: ' . json_last_error_msg());
    }

    return $json;
} catch (Exception $e) {
    // Log error
    error_log("Serialization error: " . $e->getMessage());

    // Return error response
    return json_encode(['error' => 'Serialization failed']);
}
```

### Handling Missing Properties

```php
// Source has properties target doesn't have
$source = ['id' => 1, 'name' => 'John', 'unknown' => 'value'];

class Target {
    public $id;
    public $name;
    // No 'unknown' property
}

$target = new Target();
ObjectCopy::copy($source, $target);  // 'unknown' is silently ignored

// This is expected behavior - extra properties are ignored
```

### Type Mismatch Handling

```php
// Use a value handler to handle type mismatches gracefully
$handler = new DirectTransform(
    function ($prop, $target, $value, $instance) {
        try {
            // Attempt type coercion
            if ($target === 'age' && is_numeric($value)) {
                return (int)$value;
            }

            if ($target === 'createdAt' && is_string($value)) {
                return new DateTime($value);
            }

            return $value;
        } catch (Exception $e) {
            // Log error and return null or default
            error_log("Value transformation error for {$target}: " . $e->getMessage());
            return null;
        }
    }
);
```

## Best Practices

### 1. Use DTOs for API Responses

```php
// Good - dedicated DTO
class UserResponseDTO {
    public $id;
    public $username;
    public $email;
    // Only public-safe fields
}

// Create from entity
$dto = new UserResponseDTO();
ObjectCopy::copy($user, $dto);
$json = Serialize::from($dto)->toJson();
```

### 2. Create Reusable Transformers

```php
class UserTransformer {
    public function toArray(User $user): array {
        return Serialize::from($user)
            ->withIgnoreProperties(['password', 'apiKey'])
            ->toArray();
    }

    public function toPublicArray(User $user): array {
        return Serialize::from($user)
            ->withIgnoreProperties(['password', 'apiKey', 'email', 'phone'])
            ->toArray();
    }
}
```

### 3. Document Security Implications

```php
class UserService {
    /**
     * Export users to JSON
     *
     * SECURITY: This method excludes sensitive fields (password, apiKey)
     * but includes email addresses. Only use for authorized admin users.
     */
    public function exportUsers(): string {
        $users = $this->repository->findAll();
        return Serialize::from($users)
            ->withIgnoreProperties(['password', 'apiKey'])
            ->toJson();
    }
}
```

### 4. Use Type Hints

```php
// Good - clear types
public function serializeUser(User $user): array {
    return Serialize::from($user)->toArray();
}

// Less clear
public function serializeUser($user) {
    return Serialize::from($user)->toArray();
}
```

### 5. Test Edge Cases

```php
class UserSerializerTest extends TestCase {
    public function testSerializeWithNullValues() {
        $user = new User();
        $user->name = null;

        $result = Serialize::from($user)->toArray();
        $this->assertArrayHasKey('name', $result);

        $result = Serialize::from($user)
            ->withDoNotParseNullValues()
            ->toArray();
        $this->assertArrayNotHasKey('name', $result);
    }

    public function testSerializeHidesSensitiveData() {
        $user = new User();
        $user->password = 'secret';

        $result = Serialize::from($user)
            ->withIgnoreProperties(['password'])
            ->toArray();

        $this->assertArrayNotHasKey('password', $result);
    }
}
```

### 6. Monitor Performance

```php
class PerformanceMonitor {
    public function monitorSerialization(User $user): array {
        $start = microtime(true);

        $result = Serialize::from($user)->toArray();

        $duration = microtime(true) - $start;

        if ($duration > 0.1) {  // 100ms threshold
            error_log("Slow serialization: {$duration}s for User ID {$user->getId()}");
        }

        return $result;
    }
}
```

## Related Components

- [Serialize](serialize.md) - Main serialization class
- [Formatters](formatters.md) - Output format customization
- [Property Handlers](propertyhandlers.md) - Property transformation
- [Troubleshooting](troubleshooting.md) - Common issues and solutions
