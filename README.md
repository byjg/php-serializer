# Serializer
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/byjg/serializer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/byjg/serializer/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/byjg/serializer/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/byjg/serializer/?branch=master)
[![Build Status](https://github.com/byjg/serializer/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/serializer/actions/workflows/phpunit.yml)

Serialize any object into array and format it JSON or XML

## Basic Usage

Just use the Serializer class with any kind of object, stdClass or array;

```php
<?php
$serializer = new \ByJG\Serializer\SerializerObject($data);
$result = $object->serialize();
```

`$result` is an array. You can use a Formatter to transform it in JSON or XML.

## Formatting the Output with a formatter

```php
<?php
$serializer = new \ByJG\Serializer\SerializerObject($data);
$result = $serializer->serialize();

echo (new JsonFormatter())->process($result);
echo (new XmlFormatter())->process($result);
```

## Customizing the Serialization

### Ignore null elements: `setBuildNull(false)`

The SerializerObject brings all properties by default. For example:

```php
<?php
$myclass->setName('Joao');
$myclass->setAge(null);

$serializer = new \ByJG\Serializer\SerializerObject($myclass);
$result = $serializer->build();
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

### Do not parse some classes: `setDoNotParse([object])`

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

## Install

```
composer require "byjg/serialize=2.0.*"
```

## Test

```
phpunit
```

----
[Open source ByJG](http://opensource.byjg.com)
