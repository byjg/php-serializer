# Changelog for Version 6.0

## Overview

Version 6.0 is a major release that introduces significant improvements to the serialization library, including CSV support, enhanced object copying capabilities, and important architectural refinements. This release includes breaking changes and requires PHP 8.3 or higher.

## New Features

### CSV Serialization Support
- Added `CsvFormatter` class for converting data to CSV format
- Introduced `Serialize::fromCsv()` method for parsing CSV content
- Support for CSV headers and round-trip serialization/deserialization
- Proper handling of escaped values in CSV output

### Enhanced Object Copying
- Introduced `ObjectCopyTrait` for easy implementation of copyable objects
- Added `BaseModel` abstract class with built-in object copying functionality
- Expanded property handler system with new built-in handlers:
  - `PropertyNameMapper` for flexible property name mapping
  - Enhanced `CamelToSnakeCase` with value transformation support
  - Enhanced `SnakeToCamelCase` with value transformation support

### Property Filtering

- Added `withOnlyProperties(array $properties)` to `Serialize` — allowlist filter that includes only the specified fields in the output
- Added `withoutOnlyProperties()` to reset the allowlist

### Property Handler Improvements
- Property handlers now receive the source object in `transformValue()` method
- Added `DirectTransform` for basic property transformations
- Improved property pattern matching capabilities

### Documentation Enhancements
- Comprehensive documentation for all core components
- Added guides for formatters, property handlers, and advanced usage
- Integration examples for popular frameworks (Symfony, Laravel, Doctrine)
- Troubleshooting guide for common issues
- ByJG ecosystem integration documentation

### PHP 8.4 Support
- Full compatibility with PHP 8.4
- Added `#[Override]` attributes to relevant methods

### Development Improvements
- Updated PHPUnit requirement to ^10.5
- Separated Psalm analysis into dedicated workflow job
- Improved CI/CD pipeline with better container options
- Updated to use GitHub Actions checkout v5

## Bug Fixes

- Simplified `XmlFormatter::process` to handle single-item and empty array cases directly
- Reduced complexity of various functions to improve maintainability
- Fixed escape handling in CSV serialization methods
- Removed unused `use` statements across the codebase

## Breaking Changes

| Before | After | Description |
|--------|-------|-------------|
| `PropertyPatternInterface` | `PropertyHandlerInterface` | Interface renamed to better reflect its purpose in handling properties rather than just pattern matching |
| `PropertyPattern\CamelToSnakeCase` | `PropertyHandler\CamelToSnakeCase` | Moved from `PropertyPattern` namespace to `PropertyHandler` namespace |
| `PropertyPattern\SnakeToCamelCase` | `PropertyHandler\SnakeToCamelCase` | Moved from `PropertyPattern` namespace to `PropertyHandler` namespace |
| `PropertyPattern\DifferentTargetProperty` | `PropertyHandler\PropertyNameMapper` | Renamed and moved to `PropertyHandler` namespace with enhanced functionality |
| `changeValue()` method | `transformValue()` method | Method renamed in `PropertyHandlerInterface` and all implementing classes for clarity |
| `transformValue($propertyName, $value)` | `transformValue($propertyName, $value, $source)` | Added `$source` parameter to receive the source object for context-aware transformations |
| PHP 8.1 and 8.2 support | PHP >= 8.3 required | Minimum PHP version increased to 8.3 |
| PHPUnit ^9.6 | PHPUnit ^10.5 | Minimum PHPUnit version updated for testing |
| `ObjectCopy` internal implementation | Refactored with cleaner architecture | Internal refactoring may affect code that extends `ObjectCopy` directly |

## Path to Upgrade from 5.x to 6.x

### Step 1: Check PHP Version
Ensure your environment is running PHP 8.3 or higher:
```bash
php -v
```

If you're on PHP 8.1 or 8.2, you'll need to upgrade your PHP version before migrating to 6.0.

### Step 2: Update Dependencies
Update your `composer.json`:
```bash
composer require "byjg/serializer:^6.0"
```

### Step 3: Update Property Pattern References

#### Option A: Simple Find and Replace
If you're using the standard property handlers, update the namespace and class names:

**Before (5.x):**
```php
use ByJG\Serializer\PropertyPattern\PropertyPatternInterface;
use ByJG\Serializer\PropertyPattern\CamelToSnakeCase;
use ByJG\Serializer\PropertyPattern\SnakeToCamelCase;
use ByJG\Serializer\PropertyPattern\DifferentTargetProperty;

$copy = new ObjectCopy(new CamelToSnakeCase());
```

**After (6.x):**
```php
use ByJG\Serializer\PropertyHandler\PropertyHandlerInterface;
use ByJG\Serializer\PropertyHandler\CamelToSnakeCase;
use ByJG\Serializer\PropertyHandler\SnakeToCamelCase;
use ByJG\Serializer\PropertyHandler\PropertyNameMapper;

$copy = new ObjectCopy(new CamelToSnakeCase());
```

#### Option B: Update Custom Property Handlers
If you've implemented custom property handlers:

**Before (5.x):**
```php
class MyCustomHandler implements PropertyPatternInterface
{
    public function match($propertyName) { /* ... */ }
    public function changeValue($propertyName, $value) { /* ... */ }
}
```

**After (6.x):**
```php
class MyCustomHandler implements PropertyHandlerInterface
{
    public function match($propertyName) { /* ... */ }
    public function transformValue($propertyName, $value, $source) {
        // Note: $source parameter is now available
        /* ... */
    }
}
```

### Step 4: Update DifferentTargetProperty Usage
If you were using `DifferentTargetProperty`, switch to `PropertyNameMapper`:

**Before (5.x):**
```php
use ByJG\Serializer\PropertyPattern\DifferentTargetProperty;

$mapper = new DifferentTargetProperty(['old_name' => 'new_name']);
```

**After (6.x):**
```php
use ByJG\Serializer\PropertyHandler\PropertyNameMapper;

$mapper = new PropertyNameMapper(['old_name' => 'new_name']);
```

### Step 5: Leverage New Features

#### Use ObjectCopyTrait for Easier Object Copying
**New in 6.x:**
```php
class User implements \ByJG\Serializer\ObjectCopyInterface
{
    use \ByJG\Serializer\ObjectCopyTrait;

    public $id;
    public $name;

    // Automatically inherits copyFrom() and copyTo() methods
}

$user = new User();
$user->copyFrom(['id' => 1, 'name' => 'John']);
```

#### Use BaseModel for Quick Implementation
**New in 6.x:**
```php
class User extends \ByJG\Serializer\BaseModel
{
    public $id;
    public $name;

    // Inherits all serialization and copying functionality
}
```

#### Utilize CSV Serialization
**New in 6.x:**
```php
// Serialize to CSV
$csv = \ByJG\Serializer\Serialize::from($data)->toCsv();

// Deserialize from CSV
$objects = \ByJG\Serializer\Serialize::fromCsv($csvString, MyClass::class);
```

### Step 6: Update Tests
If you're running PHPUnit tests, update your PHPUnit version:
```bash
composer require --dev "phpunit/phpunit:^10.5"
```

Make sure all tests pass after the migration.

### Step 7: Review Documentation
Review the updated documentation for new capabilities and best practices:
- [Core Components Documentation](docs/)
- [Property Handlers Guide](docs/propertyhandlers.md)
- [Advanced Usage](docs/advanced-usage.md)

### Common Migration Issues

#### Issue: "Class PropertyPatternInterface not found"
**Solution:** Update the namespace from `PropertyPattern` to `PropertyHandler`

#### Issue: "Method changeValue() not found"
**Solution:** Rename the method to `transformValue()` and add the `$source` parameter

#### Issue: "Incompatible with PHP 8.2"
**Solution:** Upgrade your PHP environment to 8.3 or higher

#### Issue: Custom property handlers breaking
**Solution:** Update the interface implementation and method signatures according to Step 3, Option B above

### Testing Your Migration

After completing the migration steps:

1. Run your test suite:
   ```bash
   ./vendor/bin/phpunit
   ```

2. Test serialization/deserialization with your actual data models

3. Verify that property copying works as expected with your custom handlers (if any)

4. Check that CSV serialization works for your use cases (if applicable)

### Rollback Plan

If you encounter issues during migration:

1. Revert to version 5.x:
   ```bash
   composer require "byjg/serializer:^5.0"
   ```

2. Document the specific issues encountered

3. Review the breaking changes table above and ensure all required changes are implemented

4. Try the migration again with the corrected code

## Additional Notes

- The library now has a more modular architecture with clear separation of concerns
- Property handlers are more powerful with access to source objects during transformation
- Enhanced documentation makes it easier to integrate with popular frameworks
- CSV support opens new use cases for data export and import functionality
- The codebase is more maintainable with reduced complexity and better test coverage
