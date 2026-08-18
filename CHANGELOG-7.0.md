# Changelog for Version 7.0

> **Status: in development.** This document tracks changes landing on the `7.0` branch.
> Nothing here is released yet, and the contents may still change.

## Overview

Version 7.0 starts as a performance-focused release. `ObjectCopy` gains a direct copy path
for its most common usage, and the property handlers were simplified. There are no breaking
changes so far, and the PHP requirement is unchanged (`>=8.3 <8.6`).

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
