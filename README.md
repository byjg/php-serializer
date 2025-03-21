# PHP Serializer

[![Build Status](https://github.com/byjg/php-serializer/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/php-serializer/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/php-serializer/)
[![GitHub license](https://img.shields.io/github/license/byjg/php-serializer.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/php-serializer.svg)](https://github.com/byjg/serializer/releases/)

A powerful PHP library for seamless object serialization, conversion, and property manipulation.

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
$json = \ByJG\Serializer\Serialize::instance()
    ->from($object)
    ->withMethodGetPrefix()
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
class User extends \ByJG\Serializer\ObjectCopy
{
    public $id;
    public $name;
    
    // Automatically inherits copyFrom() and copyTo() methods
}
```

## Documentation

| Component               | Description                                           | Link                                         |
|-------------------------|-------------------------------------------------------|----------------------------------------------|
| **Serialize**           | Core component for converting objects between formats | [Documentation](docs/serialize.md)           |
| **ObjectCopy**          | Copy properties between different object structures   | [Documentation](docs/objectcopy.md)          |
| **ObjectCopyInterface** | Interface for implementing copyable objects           | [Documentation](docs/objectcopyinterface.md) |


## Installation

```bash
composer require "byjg/serializer"
```

## Testing

```bash
./vendor/bin/phpunit
```

## Dependencies

```mermaid
flowchart TD
    byjg/serializer --> ext-json
    byjg/serializer --> symfony/yaml
    byjg/serializer --> ext-simplexml
```

----
[Open source ByJG](http://opensource.byjg.com)
