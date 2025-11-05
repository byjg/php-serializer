---
sidebar_position: 12
---

# ByJG Ecosystem

The PHP Serializer is a foundational component used internally across the ByJG ecosystem. It provides seamless conversion between different data types and formats, enabling smooth integration between components.

## Overview

The Serializer is **built into several ByJG components** to handle data transformation automatically:

| Component | How Serializer is Used | Documentation |
|-----------|------------------------|---------------|
| **MicroORM** | Provides the mapping between database rows and model objects | [View Docs](https://github.com/byjg/php-micro-orm) |
| **AnyDataset** | Uses Serializer formatters (JsonFormatter, XmlFormatter) for output serialization | [View Docs](https://github.com/byjg/php-anydataset) |
| **Cache Engine** | Serializes complex objects for storage and retrieval | [View Docs](https://github.com/byjg/php-cache-engine) |
| **WebRequest** | Handles request/response body serialization | [View Docs](https://github.com/byjg/php-webrequest) |
| **REST Server** | Formats API responses automatically | [View Docs](https://opensource.byjg.com) |

**Key Benefits:**
- ✅ Consistent data transformation across all components
- ✅ Automatic type conversion between formats (JSON, XML, YAML, CSV)
- ✅ Reduced boilerplate code
- ✅ Standardized serialization behavior throughout your application

## How Serializer is Used in Each Component

### MicroORM

The Serializer provides the **mapping functionality** in MicroORM. When you define models with attributes, the Serializer handles converting database rows (typically snake_case) to your model properties (typically camelCase) and vice versa. This eliminates the need for manual property mapping.

**Internal Usage:**
- Maps database field names to model property names using PropertyHandlers
- Converts database result sets to model objects automatically
- Handles type conversion during data retrieval and persistence

### AnyDataset

AnyDataset uses the Serializer's **formatters** to provide output serialization. When you work with different data sources (databases, XML, JSON, CSV), AnyDataset leverages JsonFormatter, XmlFormatter, and other formatters to convert data between formats seamlessly.

**Internal Usage:**
- Provides FormatterInterface implementations for various output formats
- Enables format conversion (CSV to JSON, JSON to XML, etc.)
- Standardizes data structure transformation across all data sources

### Cache Engine

The Cache Engine uses the Serializer to **prepare complex objects for caching**. Before storing objects in cache backends (Redis, Memcached, Filesystem), the Serializer converts them to simple arrays that can be safely serialized and stored.

**Internal Usage:**
- Converts complex objects to cacheable array structures
- Ensures consistent serialization format across different cache backends
- Handles deserialization when retrieving cached data

### WebRequest

WebRequest integrates with the Serializer for **HTTP request and response body handling**. When making API calls or processing responses, the Serializer handles JSON/XML encoding and decoding automatically.

**Internal Usage:**
- Serializes request bodies to JSON or other formats
- Deserializes API responses to PHP arrays or objects
- Handles content-type negotiation and format detection

### REST Server

REST Server uses the Serializer to **format API responses automatically**. Based on the requested format (JSON, XML, YAML), it uses the appropriate Serializer formatter to generate the response.

**Internal Usage:**
- Automatically formats controller return values
- Supports multiple output formats based on Accept headers
- Ensures consistent API response structure

## Why Use ByJG Components Together?

### 1. **Seamless Integration**

All components speak the same "language" through the Serializer:

```php
// Data flows naturally between components
$dbResult = $repository->get($id);           // MicroORM
$cached = Serialize::from($dbResult)->toArray();  // Serializer
$cache->set($key, $cached);                  // Cache Engine
$json = Serialize::from($cached)->toJson();  // Serializer
```

### 2. **Consistent Behavior**

The same serialization rules apply everywhere:

```php
// Configure once, use everywhere
$userData = Serialize::from($user)
    ->withIgnoreProperties(['password', 'apiKey'])
    ->toArray();

// Use in cache
$cache->set('user', $userData);

// Use in API response
echo Serialize::from($userData)->toJson();

// Use in queue message
$queue->publish(Serialize::from($userData)->toJson());
```

### 3. **Less Code, More Productivity**

```php
// Without Serializer - manual conversion
$userData = [
    'id' => $user->getId(),
    'name' => $user->getName(),
    'email' => $user->getEmail(),
    // ... repeat for every property
];

// With Serializer - automatic
$userData = Serialize::from($user)->toArray();
```

## Property Handlers Across Components

Property handlers work consistently across the ecosystem:

```php
use ByJG\Serializer\PropertyHandler\SnakeToCamelCase;

$handler = new SnakeToCamelCase();

// Database → Model (MicroORM)
$dbRow = ['user_id' => 1, 'first_name' => 'John'];
ObjectCopy::copy($dbRow, $user, $handler);

// Cache → Model (Cache Engine)
$cached = $cache->get('user');
ObjectCopy::copy($cached, $user, $handler);

// API → Model (WebRequest)
$apiResponse = json_decode($response->getBody(), true);
ObjectCopy::copy($apiResponse, $user, $handler);
```

## Best Practices

### 1. Use Serializer for All Data Conversions

```php
// Good - consistent serialization
$json = Serialize::from($user)->toJson();
$xml = Serialize::from($user)->toXml();
$csv = Serialize::from($users)->toCsv();

// Avoid - manual conversion
$json = json_encode([/* manual array */]);
```

### 2. Cache Serialized Data

```php
// Good - serialize before caching
$cache->set($key, Serialize::from($model)->toArray());

// Avoid - caching raw objects (may not serialize properly)
$cache->set($key, $model);
```

### 3. Use Property Handlers for Format Conversion

```php
// Good - automatic conversion
ObjectCopy::copy($dbRow, $model, new SnakeToCamelCase());

// Avoid - manual property mapping
$model->userId = $dbRow['user_id'];
$model->firstName = $dbRow['first_name'];
```

### 4. Configure Serialization Consistently

```php
// Define serialization rules once
class User extends BaseModel {
    public static function toPublicArray($user): array {
        return Serialize::from($user)
            ->withIgnoreProperties(['password', 'apiKey', 'internalNotes'])
            ->toArray();
    }
}

// Use everywhere
$cache->set($key, User::toPublicArray($user));
echo Serialize::from(User::toPublicArray($user))->toJson();
```

## Related Documentation
- [Integration Examples](integration-examples.md) - General framework integration patterns
- [Property Handlers](propertyhandlers.md) - Data transformation techniques
- [Formatters](formatters.md) - Output format customization
- [Advanced Usage](advanced-usage.md) - Performance and security considerations
