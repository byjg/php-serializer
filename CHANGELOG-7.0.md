# Changelog - Version 7.0

> **Status: in development.** This document tracks changes landing on the `7.0` branch.
> Nothing here is released yet, and the contents may still change.

## Overview

Version 7.0 starts as a performance-focused release. `ObjectCopy` gains a direct copy path
for its most common usage, and the property handlers were simplified. Backed enums are now supported,
and are serialized as their value (the one breaking change). The PHP requirement is now `>=8.3 <8.7` (see Requirements below).

## New Features

### Backed enums

A property typed with a backed enum (`public ?Status $status`) works in both directions:

- `Serialize` writes a backed enum as its `->value`, as `json_encode()` does, at every level, so
  also with `withStopAtFirstLevel()`. It used to become `{"name": ..., "value": ...}`, or stay an
  enum object at the first level (which byjg/micro-orm passes to the database).
- `ObjectCopy` builds the enum from an `int` or `string` with `from()` when the target property,
  or its setter's parameter, is typed with the enum (`Status` or `?Status`). Writing `'PAID'` into
  that property used to be a `TypeError`. An unknown value throws a `ValueError`; a type that also
  accepts a scalar (`string|Status`) receives the value as it is.

A pure enum is unchanged. See `docs/serialize.md` and `docs/objectcopy.md`.

### Reading XML: `Serialize::fromXml()`

XML was output-only; it is now a source like JSON, YAML and CSV:

```php
Serialize::fromXml($xml)->toArray();
Serialize::fromXml($xml, listElement: 'user', listElementSuffix: true)->toJson();
```

It accepts an XML string, a `SimpleXMLElement` or a DOM node, and reads with the conventions
`toXml()` writes, so `toXml()` then `fromXml()` returns the same array: the root element is
dropped, `@attributes` holds the attributes, `@value` the text of an element that also has
attributes or children, and elements named like the list element (`item`) are list entries.
Every value is a string. CDATA is read as text; entities are not expanded and nothing is
fetched from the network (no XXE). Invalid XML throws `InvalidArgumentException`.
`XmlFormatter::parse()` does the work, with the formatter's configuration.

Namespaces are kept: an element or attribute in a namespace keeps its prefix (`x:isbn`,
`xsi:type`), and a declaration is an attribute, as in XML (`@attributes['xmlns:x']`, `xmlns` for a
default namespace). `toXml()` writes them back, so a namespaced document survives
`fromXml()` then `toXml()`.

### `toXml()` options, attributes and text

- `toXml(rootElement: 'users', listElement: 'user', listElementSuffix: true)` sets what was
  only available on `XmlFormatter` directly.
- An `@attributes` key is written as the attributes of its element, and an `@value` key as
  its text.

### Reading a file block by block: `Serialize::fromFile()` and `toIterator()`

`fromFile()` detects the format by the extension (`.csv`, `.xml`, `.json`, `.yaml`, `.yml`).
A CSV or XML file is not loaded: it is read one block at a time, each time it is used, so a file
with millions of records keeps the memory flat while you loop:

```php
foreach (Serialize::fromFile('big.csv')->withOnlyProperties(['name'])->toIterator() as $block) {
    $block->toArray();     // one CSV record
}

Serialize::fromFile('export.xml', blockElement: 'row')->toJson();   // all the blocks, as a list
```

- A block is a CSV record keyed by the header row, or an XML element: each child of the root
  element, or the elements named by `blockElement` at any depth, read as `fromXml()` reads a document.
- Each block is a `Serialize` with the same options. Every `to*()` method works over all the
  blocks and returns them as a list.
- A block keeps the value read, returned by the new `getValue()`: the original `DOMElement` of an
  XML block, the record of a CSV block. It is converted only when a `to*()` method is called.
- `toXml()` of an XML block writes the original element, without a conversion (which loses the
  order of siblings, namespaced elements and mixed text); with an option or a parameter it converts.
- JSON and YAML have no streaming parser in PHP: the file is read by `fromFile()`, as
  `fromJson()` and `fromYaml()` do.
- `toIterator()` also works on any `Serialize`: the blocks are the items of a list of arrays or
  objects, and anything else is a single block.
- The readers, `Reader\CsvReader` and `Reader\XmlBlockReader`, implement the new `Reader\FileBlocks`
  interface, and `from()` reads a `FileBlocks` as `fromFile()` does:
  `Serialize::from(new CsvReader('big.csv'))`. Another object that can be iterated (a collection) is
  converted as before.

- `Reader\CsvReader::records($handle, $hasHeader, $separator = ',', $enclosure = '"')` reads the records
  of an open stream with another separator (`;`, `|`, a tab...) or enclosure (`'`).
- `new Reader\XmlBlockReader($filename, $blockElement, $namespaces = [])`: with a prefix of `$namespaces`
  (`'a:entry'`, `['a' => 'http://www.w3.org/2005/Atom']`), the block elements are matched by namespace URI
  and local name, whatever prefix the file uses (none, for a default namespace). Without it, the name is
  compared as written in the file.

### `ObjectCopy::writerFor()`

`ObjectCopy::writerFor(string $className, string $name)` returns how `copy()` writes a value with that name
into an object of that class: `[member, isSetter, enumClass]` for its setter (any case) or public property
(exact case, else any case), or `false` when the class has neither. For code that fills many objects of the
same class with the same rules as `copy()`, such as the byjg/micro-orm entity hydrator.

**Documentation:** [ObjectCopy](docs/objectcopy.md)

## Performance

### `ObjectCopy` direct copy path for an array source

`ObjectCopy::copy()` previously routed every copy through the full `Serialize` pipeline. When the
source is an **array** (a database row, a request body), with or without a property handler, it now
iterates the array directly and makes the same calls the pipeline made: each key in order, a backed
enum as its value, then the property handler (`mapName()`, `transformValue()` with the whole source
array) and the write. The behaviour is pinned by
`ObjectCopyTest::testArrayWithHandlerGivesTheHandlerWhatThePipelineGives()`, which runs the `Serialize`
pipeline on the same sources (backed enums, integer keys, nested values) and checks the handler gets the
same calls. A copy from an object source still uses the pipeline.

### How a class takes each value is resolved once

For each value, the write into an object resolved the setter (`method_exists('set' . $name)`), the public
property (exact case, else any case) and the backed enum of the member. That is now resolved once per class
and name, and cached statically (classes do not change at runtime); the new public
`ObjectCopy::writerFor()` returns it (see New Features). Only a property the instance itself has (dynamic,
or magic `__isset()`/`__set()`) is still checked on the object, as before. The behaviour is pinned by
`ObjectCopyWriteTest`.

### Measured impact

200,000 copies of a five-property model from an array, PHP 8.5, Xdebug off, median of three runs:

| Scenario | 6.0.2 | 7.0 | |
|---|---|---|---|
| Array source, no property handler | 0.55 s | **0.25 s** | ~2.2x faster |
| Array source, with property handler (`SnakeToCamelCase`) | 0.75 s | **0.45 s** | ~1.65x faster |

For entities read from a database (byjg/anydataset-db with byjg/micro-orm), together with the changes in
those libraries: about 5x faster than at the start of 7.0 development.

## Internal Changes

- `ObjectCopy::applyAttribute()` no longer threads a property-name cache through its arguments;
  target assignment moved into a dedicated `applyToTarget()` helper shared by both copy paths.
- `CamelToSnakeCase` and `SnakeToCamelCase` use arrow functions for their `preg_replace_callback`
  bodies, dropped an unused `use Closure;` import, and had trailing whitespace cleaned up.

These are all `private`/internal to `final class ObjectCopy`, so they are not part of the public API.

- `fromCsv()` reads its records with the new `Reader\CsvReader`, which `fromFile()` also uses.

## Bug Fixes

- A static property is no longer serialized nor copied into: it is class state, not data of the object.
  `Serialize` read it as an instance property (warning "Undefined property" and notice "Accessing static
  property as non static"), and `ObjectCopy` wrote a key with its name as a dynamic property of the object
  (deprecated since PHP 8.2), leaving the static property unchanged.
- `Serialize::withMethodPattern()` now rejects a search pattern shorter than 2 characters, as its
  message ("cannot be empty or less than 2 characters") already said. The check used `&&`
  (`empty($search) && strlen($search) <= 2`), so only `''` and `'0'` were rejected and a
  one-character pattern such as `'/'` was stored, failing later in `preg_replace()` with
  "No ending delimiter". A 2-character pattern (`'//'`) is still accepted.
- `ObjectCopy::copy()` now copies a backed enum the same way on every path. An array source
  without a property handler (the fast path) copied the enum object as it was, while every other
  path (an object source, or any source with a handler) goes through `Serialize` and copied its
  `->value`. A target typed with the enum got the same result either way, but a `string|Enum`
  property, a `stdClass` or an array target got the enum object from one path and its value
  from the other. The fast path now copies the value too.

- `toXml()` wrote a list of scalars with the index as the element name
  (`['tags' => ['a', 'b']]` gave `<tags><0>a</0><1>b</1></tags>`), which is not valid XML.
  It now uses the list element, as it already did for a list of arrays:
  `<tags><item>a</item><item>b</item></tags>`.
- `Serialize::fromCsv()` split the content into lines before reading it, so a quoted value with a
  line break (allowed by RFC 4180) became two broken rows: `Joao,"line1\nline2"` gave a `note` of
  `line1` and a next row starting with `line2"`. It now reads records with `fgetcsv()`, which keeps
  the line break in the value. Lines ending in CRLF still work.
- `Serialize::fromCsv()` with a header row dropped a value silently when a record had more fields than
  the header, and left a key out when it had fewer (`"name,age\nJane"` gave `['name' => 'Jane']`). A
  record must have as many fields as the header (RFC 4180): otherwise it throws
  `ByJG\Serializer\Exception\InvalidArgumentException` ("Invalid CSV: record 2 has 1 fields, the header has 2").
- `toXml()` of a list with a single array or object (`[['name' => 'John']]`) threw a `TypeError`:
  the formatter returned the item instead of XML. It is now written as a list, like a longer one:
  `<root><item><name>John</name></item></root>`. A list with a single scalar is still its text.

- `withOnlyProperties()` filtered every level, so it emptied what it kept and dropped the items of a
  list: `withOnlyProperties(['name', 'address'])` gave `'address' => []`, and a list of records
  (`[['name' => 'John', 'age' => 30], ...]`) with `withOnlyProperties(['name'])` gave `[]`, while
  `toIterator()` gave each record filtered. It now filters the properties of the model, or of each
  item of a list of arrays or objects; a property kept keeps its whole value.
  `withIgnoreProperties()` is unchanged: it still removes a property at every level.
- An anonymous class (`new class { ... }`) ignored the options: `withIgnoreProperties()`,
  `withOnlyProperties()`, `withDoNotParseNullValues()` and the function of `parseAttributes()`, so
  `withIgnoreProperties(['password'])` still wrote the `password` of an anonymous object. It now has
  them as a class has, and `parseAttributes()` receives the attribute of its property. For the same
  reason, `ObjectCopy::copy()` with a property handler copied nothing from an anonymous class source; it
  now copies its properties.

## Breaking Changes

### `@attributes` and `@value` are reserved in `toXml()`

An array key named `@attributes` or `@value` is now written as the element's attributes or
text, instead of as a child element (whose name was not valid XML).

### A prefixed key in `toXml()` needs its namespace declaration

A key with a prefix (`'dc:title'`) was written without it (`<title>`). It is now written in the
namespace its prefix is declared for, by an `xmlns:dc` key in `@attributes` of that element or
a parent; without a declaration it throws `InvalidArgumentException`, since `<dc:title>` alone is
not valid XML. Declare the namespace, or rename the key.

### `Serialize::$model` is now `$value`

The protected property that holds what is serialized is renamed from `$model` to `$value`, as returned
by the new `getValue()`: it is not always a model, but an array, an object, an XML node or a block of a
file. The parameter of `from()` is renamed too. A subclass that reads `$this->model` reads
`$this->value`, and a call with a named argument, `Serialize::from(model: $x)`, is now
`Serialize::from(value: $x)`.

### A backed enum is serialized as its value

`Serialize::from($model)->toArray()` (and `toJson()`, `toYaml()`, ...) gave a backed enum as
`['name' => 'Paid', 'value' => 'PAID']`; it now gives `'PAID'`. With `withStopAtFirstLevel()`
it gave the enum object; it now gives `'PAID'`. Code that read `['value']` from the output
reads the value directly.

### `fromJson()` and `fromYaml()` refuse a scalar and invalid content

`fromJson()` and `fromYaml()` accepted content that is a scalar (`"text"`, `42`), and `fromJson()` invalid
JSON (held as `null`); the error came later, as a `TypeError` from whichever `to*()` method was called. They
now go through `from()`, which takes only an array or an object, so a scalar throws `TypeError` from
`fromJson()`/`fromYaml()` itself, and invalid JSON throws `JsonException` (invalid YAML already threw
`ParseException`).

### `ObjectCopy` copies a backed enum from an array as its value

`ObjectCopy::copy(['status' => Status::Paid], $target)` (array source, no property handler)
put the enum object into a `stdClass` or array target, or into a property that also takes a
scalar (`string|Status`); it now puts `'PAID'`, as an object source or a property handler
already did. A property or setter typed with the enum (`Status`, `?Status`) still gets
`Status::Paid`.

## Path to Upgrade from 6.x to 7.x

If you read a backed enum from `Serialize` output as `['value']` (or as the enum object at the
first level), read the value directly. If you copy an array holding a backed enum into a
`stdClass`, an array or a `string|Enum` property and expect the enum object, call
`Status::from()` on the copied value. If you extend `Serialize` and read `$this->model`, or call
`Serialize::from(model: ...)`, use `$value` instead. Otherwise no action is required. Existing code using `ObjectCopy`, `ObjectCopyTrait`,
`BaseModel`, `Serialize`, and the property handlers continues to work unchanged.

For the 6.x migration guide, see [CHANGELOG-6.0.md](CHANGELOG-6.0.md).

## Requirements

- PHP 8.3, 8.4, 8.5 and 8.6 are now supported: `"php": ">=8.3 <8.7"`.
  The previous `<8.6` upper bound excluded PHP 8.6, since `<8.6` is exclusive.

## Toolchain

- PHPUnit updated to `^12.5`.
- Psalm is installed as `psalm/phar` (`^6.16`) instead of `vimeo/psalm`.

  `vimeo/psalm` lists the PHP versions it supports and no published release includes
  8.6, so as a dev dependency it made `composer install` fail on the 8.6 build job
  before any test ran. `psalm/phar` requires only `php ^8.2` and bundles its own
  dependencies, so it installs on every PHP version in the matrix and cannot conflict
  with the project's. Psalm itself still refuses to *run* on 8.6, which is why the
  Psalm job uses 8.5. `composer psalm` runs it.

  PHPUnit 13 is deliberately **not** used. It requires PHP `>=8.4.1`, which would
  break the 8.3 floor.

## Continuous Integration

- The build matrix now includes PHP 8.6.
- The Psalm job now runs on PHP 8.5. Psalm declares
  `~8.1.31 || ~8.2.27 || ~8.3.16 || ~8.4.3 || ~8.5.0` and therefore does not run on
  PHP 8.6.

## Housekeeping

- `phpunit.xml.dist` renamed to `phpunit.xml`.
