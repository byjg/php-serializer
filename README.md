# Serializer
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/byjg/serializer/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/byjg/serializer/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/byjg/serializer/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/byjg/serializer/?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/6375df04-b1f8-4f5f-94f8-a375f630250a/mini.png)](https://insight.sensiolabs.com/projects/6375df04-b1f8-4f5f-94f8-a375f630250a)
[![Build Status](https://travis-ci.org/byjg/serializer.svg?branch=master)](https://travis-ci.org/byjg/serializer)

Serialize any object into array and format it JSON or XML

# Basic Usage

Just use the Serializer class with any kind of object, stdClass or array;

```php
<?php
$serializer = new \ByJG\Serializer\SerializerObject($data);
$result = $object->build();
```

`$result` is an array. You can use a Formatter to transform it in JSON or XML.

# Formatting the Output with a formatter

```php
<?php
$serializer = new \ByJG\Serializer\SerializerObject($data);
$result = $serializer->build();

echo (new JsonFormatter())->process($result);
echo (new XmlFormatter())->process($result);
```

# Customizing the Serialization

## Ignore null elements: `setBuildNull(false)`

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
$serializer = new \ByJG\Serializer\SerializerObject($myclass);
$result = $serializer->setBuildNull(false)->build();
print_r($result);

// And the result will be:
// Array
// (
//     [name] => Joao
// )

```

## Do not parse some classes: `setDoNotParse([object])`

Sometimes we want to serialize the object but ignore some class types.

Setting this option below the whole classes defined in the setDoNotParse will be ignored and not parsed:

```php
<?php
$serializer = new \ByJG\Serializer\SerializerObject($myclass);
$result = $serializer->setDoNotParse([
    MyClass::class
]);
```



# Create a *bindable* object

```php
<?php
// Create the class
class MyClass extends BinderObject
{}

// Bind any data into the properties of myclass
$myclass->bind($data);

// You can convert to array all properties
$myclass->toArray();
```

or

```php
// Set all properties from $source that matches with the property in $target
BinderObject::bindObject($source, $target);

// Convert all properties of any object into array
BinderObject::toArrayFrom($source);
```

# Install

```
composer require "byjg/serialize=1.0.*"
```

# Test

```
phpunit
```

