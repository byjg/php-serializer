---
sidebar_key: serializer
tags: [php, text-manipulation]
---

# Multi-Format Serializer

A powerful multi-format serialization library that converts objects, arrays, and data between JSON, XML, YAML, CSV, PHP serialize, and plain text formats with intelligent property mapping and transformation.

[![Sponsor](https://img.shields.io/badge/Sponsor-%23ea4aaa?logo=githubsponsors&logoColor=white&labelColor=0d1117)](https://github.com/sponsors/byjg)
[![Build Status](https://github.com/byjg/php-serializer/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-serializer/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](https://opensource.byjg.com)
[![Install MCP Server](https://img.shields.io/badge/Install-MCP_Server-8A2BE2?logo=modelcontextprotocol&logoColor=white)](https://opensource.byjg.com/docs/ai/mcpserver-byjg-docs/)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-serializer/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-serializer.svg)](https://opensource.byjg.com/license/)
[![GitHub release](https://img.shields.io/github/release/byjg/php-serializer.svg)](https://github.com/byjg/php-serializer/releases/)

## Features

- **Format Conversion**: Transform objects to JSON, XML, YAML, or Arrays, and back again
- **Property Control**: Filter, transform, and manipulate object properties during conversion
- **Object Mapping**: Copy properties between different object structures with intelligent mapping
- **Property Pattern Matching**: Customize how properties are matched and transformed
- **Attribute Support**: Process PHP attributes during serialization and deserialization
- **Type Safety**: Maintain data types during transformations

## Quick Examples

### Convert an object to JSON

```php
$object = new MyClass();
$json = \ByJG\Serializer\Serialize::from($object)
    ->toJson();
```

### Copy properties between objects

```php
$source = ["id" => 1, "name" => "John"];
$target = new User();
\ByJG\Serializer\ObjectCopy::copy($source, $target);
```

### Create a copyable object

```php
class User implements \ByJG\Serializer\ObjectCopyInterface
{
    use \ByJG\Serializer\ObjectCopyTrait;
    
    public $id;
    public $name;
    
    // Automatically inherits copyFrom() and copyTo() methods
}
```

## Documentation

### Core Components

| Component               | Description                                                     | Link                                         |
|-------------------------|-----------------------------------------------------------------|----------------------------------------------|
| **Serialize**           | Core component for converting objects between formats           | [Documentation](docs/serialize.md)           |
| **ObjectCopy**          | Final utility class for copying properties between objects      | [Documentation](docs/objectcopy.md)          |
| **ObjectCopyTrait**     | Trait implementing copyable object functionality                | [Documentation](docs/objectcopytrait.md)     |
| **ObjectCopyInterface** | Interface for implementing copyable objects                     | [Documentation](docs/objectcopyinterface.md) |
| **BaseModel**           | Abstract base class with object copying functionality           | [Documentation](docs/basemodel.md)           |
| **DirectTransform**     | Basic property handler for direct transformations in ObjectCopy | [Documentation](docs/directtransform.md)     |

### Guides

- **[Formatters](docs/formatters.md)** - JSON, XML, YAML, CSV, and Plain Text output formatting
- **[Property Handlers](docs/propertyhandlers.md)** - Transform property names and values during copying
- **[Advanced Usage](docs/advanced-usage.md)** - Performance optimization, security, and complex patterns
- **[Integration Examples](docs/integration-examples.md)** - Framework integration (Symfony, Laravel, Doctrine, etc.)
- **[ByJG Ecosystem](docs/byjg-ecosystem.md)** - How Serializer integrates with other ByJG components
- **[Troubleshooting](docs/troubleshooting.md)** - Common issues and solutions


## Installation

```bash
composer require "byjg/serializer"
```

## Running Tests

```bash
./vendor/bin/phpunit
```

----
[Open source ByJG](https://opensource.byjg.com)
