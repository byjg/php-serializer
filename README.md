# Serializer

[![Build Status](https://github.com/byjg/serializer/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/serializer/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/serializer/)
[![GitHub license](https://img.shields.io/github/license/byjg/serializer.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/serializer.svg)](https://github.com/byjg/serializer/releases/)

Serialize any object into array and format it JSON, YAML or XML

## Converting any object/content into array

Just use the Serializer class with any kind of object, stdClass or array;

```php
<?php
$result = \ByJG\Serializer\SerializerObject::instance($data)->serialize();
$result2 = \ByJG\Serializer\SerializerObject::instance($anyJsonString)->fromJson()->serialize();
$result3 = \ByJG\Serializer\SerializerObject::instance($anyYamlString)->fromYaml()->serialize();
```

In the examples above `$result`, `$result2` and `$result3` will be an associative array.

## Formatting an array into JSON, YAML or ZML

```php
<?php
$data = [ ... any array content ... ]

echo (new JsonFormatter())->process($data);
echo (new XmlFormatter())->process($data);
echo (new YamlFormatter())->process($data);
echo (new PlainTextFormatter())->process($data);
```

## Customizing the Serialization

### Ignore null elements: `withDoNotSerializeNull()`

The SerializerObject brings all properties by default. For example:

```php
<?php
$myclass->setName('Joao');
$myclass->setAge(null);

$serializer = new \ByJG\Serializer\SerializerObject($myclass);
$result = $serializer->serialize();
print_r($result);

// Will return:
// Array
// (
//     [name] => Joao
//     [age] => 
// )
```

But you can setup for ignore the null elements:

```php
<?php
$result = \ByJG\Serializer\SerializerObject::instance($myclass)
            ->withDoNotSerializeNull()
            ->serialize();
print_r($result);

// And the result will be:
// Array
// (
//     [name] => Joao
// )

```

### Do not parse some classes: `withDoNotParse([object])`

Sometimes we want to serialize the object but ignore some class types.

Setting this option below the whole classes defined in the setDoNotParse will be ignored and not parsed:

```php
<?php
$result = \ByJG\Serializer\SerializerObject::instance($myclass)
            ->withDoNotParse([
                MyClass::class
            ])
            ->serialize();
```

## Create a *bindable* object

Add to the object the method `bind` that allows set contents from another object

```php
<?php
// Create the class
class MyClass extends BindableObject
{}

// Bind any data into the properties of myclass
$myclass->bindFrom($data);

// You can convert to array all properties
$myclass->bindTo($otherobject);
```

## Copy contents from any object to another

```php
// Set all properties from $source that matches with the property in $target
BinderObject::bind($source, $target);

// Convert all properties of any object into array
SerializerObject::serialize($source);
```

### Copy contents from an object with CamelCase properties to another with snake_case properties

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

BinderObject::bind($source, $target, new CamelToSnakeCase());
```

### Copy contents from an object with snake_case properties to another with CamelCase properties

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

BinderObject::bind($source, $target, new SnakeToCamelCase());
```


## Install

```
composer require "byjg/serialize"
```

## Test

```
vendor/bin/phpunit
```

## Dependencies

```mermaid
flowchart TD
    byjg/serializer --> ext-json
    byjg/serializer --> symfony/yaml
```

----
[Open source ByJG](http://opensource.byjg.com)
