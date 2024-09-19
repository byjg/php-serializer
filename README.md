# Serializer

[![Build Status](https://github.com/byjg/serializer/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/serializer/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/serializer/)
[![GitHub license](https://img.shields.io/github/license/byjg/serializer.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/serializer.svg)](https://github.com/byjg/serializer/releases/)

The Serializer is a library is a versatile tool that allows you convert any object, array or stdClass 
to JSON, XML, YAML, and Array, and apply some filter to the properties. During the conversion you can
parse attributes and apply some transformation to the property values on the fly.

Also allow you to copy contents from an object to another, even if they have different properties.

For more information, please check:

- [Serialize](docs/serialize.md)
- [ObjectCopy](docs/objectcopy.md)
- [ObjectCopyInterface](docs/objectcopyinterface.md)


## Install

```
composer require "byjg/serialize"
```

## Test

```
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
