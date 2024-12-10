# Serializer

[![Build Status](https://github.com/byjg/serializer/actions/workflows/phpunit.yml/badge.svg?branch=master)](https://github.com/byjg/serializer/actions/workflows/phpunit.yml)
[![Opensource ByJG](https://img.shields.io/badge/opensource-byjg-success.svg)](http://opensource.byjg.com)
[![GitHub source](https://img.shields.io/badge/Github-source-informational?logo=github)](https://github.com/byjg/serializer/)
[![GitHub license](https://img.shields.io/github/license/byjg/serializer.svg)](https://opensource.byjg.com/opensource/licensing.html)
[![GitHub release](https://img.shields.io/github/release/byjg/serializer.svg)](https://github.com/byjg/serializer/releases/)

The Serializer library is a versatile tool that allows you to convert any object, array, or `stdClass` 
into JSON, XML, YAML, or an array. It also enables you to apply filters to properties during 
the conversion process. Additionally, you can parse attributes and apply transformations to property 
values on the fly.

The library also allows you to copy content from one object to another, even if their properties differ.

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
