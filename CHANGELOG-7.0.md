# Changelog for Version 7.0

> **Status: in development.** This document tracks changes landing on the `7.0` branch.
> Nothing here is released yet, and the contents may still change.

## Overview

Version 7.0 starts as a performance-focused release. `ObjectCopy` gains a direct copy path
for its most common usage, and the property handlers were simplified. There are no breaking
changes so far. The PHP requirement is now `>=8.3 <8.7` (see Requirements below).

## Performance

### `ObjectCopy` direct copy path

`ObjectCopy::copy()` previously routed every copy through the full `Serialize` pipeline.
When the source is an **array** and **no property handler** is given, that pipeline does no
work beyond iterating the array: `withStopAtFirstLevel()` makes every value pass through
untouched, no property filters are configured, and null values are copied by default.

That case now uses direct iteration instead. The resulting assignments are identical — the
behaviour is pinned by `ObjectCopyTest::testFastPathMatchesPipeline()`, which runs the same
inputs through both paths and asserts the targets match.

Copies made with a property handler, or from an object source, are unaffected and still use
the pipeline.

### Cached property-name resolution

The case-insensitive property lookup used when a target has no setter and no exactly matching
property is now cached statically, keyed by class name, rather than rebuilt on every call to
`copy()`. Class properties do not change at runtime, so the cache is both correct and bounded
by the number of classes copied into.

### Measured impact

200,000 copies of a five-property model, PHP 8.5, no opcache JIT, median of three runs:

| Scenario | 6.0 | 7.0 | |
|---|---|---|---|
| Array source, no property handler | 3.67 s | **1.26 s** | ~2.9x faster |
| Array source, with property handler | 5.09 s | 4.90 s | ~4% faster |

The second row only benefits from the cached property-name resolution; it still goes through
the pipeline, since a property handler may map or transform every name and value.

## Internal Changes

- `ObjectCopy::applyAttribute()` no longer threads a property-name cache through its arguments;
  target assignment moved into a dedicated `applyToTarget()` helper shared by both copy paths.
- `CamelToSnakeCase` and `SnakeToCamelCase` use arrow functions for their `preg_replace_callback`
  bodies, dropped an unused `use Closure;` import, and had trailing whitespace cleaned up.

These are all `private`/internal to `final class ObjectCopy`, so they are not part of the public API.

## Breaking Changes

None so far.

## Path to Upgrade from 6.x to 7.x

No action required at this point. Existing code using `ObjectCopy`, `ObjectCopyTrait`,
`BaseModel`, `Serialize`, and the property handlers continues to work unchanged.

For the 6.x migration guide, see [CHANGELOG-6.0.md](CHANGELOG-6.0.md).

## Requirements

- PHP 8.3, 8.4, 8.5 and 8.6 are now supported: `"php": ">=8.3 <8.7"`.
  The previous `<8.6` upper bound excluded PHP 8.6, since `<8.6` is exclusive.

## Toolchain

- PHPUnit updated to `^12.5`.
- Psalm updated to `^6.16`.

  PHPUnit 13 is deliberately **not** used. It requires PHP `>=8.4.1`, which would
  break the 8.3 floor, and it pulls `sebastian/diff ^9.0`, which the newest stable
  Psalm (6.16.1) does not accept — that combination silently resolves Psalm to an
  unreleased `6.x-dev` branch. Pinning PHPUnit to `^12.5` keeps a single stable
  PHPUnit and a single stable Psalm across the whole matrix.

## Continuous Integration

- The build matrix now includes PHP 8.6.
- The Psalm job now runs on PHP 8.5. Psalm 6.16.1 declares
  `~8.1.31 || ~8.2.27 || ~8.3.16 || ~8.4.3 || ~8.5.0` and therefore cannot be
  installed on PHP 8.6.

## Housekeeping

- `phpunit.xml.dist` renamed to `phpunit.xml`.
