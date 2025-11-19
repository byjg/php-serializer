---
sidebar_position: 7
---

# Formatters

Formatters are responsible for converting array data into various output formats like JSON, XML, YAML, CSV, and plain text. All formatters implement the `FormatterInterface` and can be used directly or through the `Serialize` class.

## Overview

The PHP Serializer library provides several built-in formatters:

- **JsonFormatter** - Converts data to JSON format
- **XmlFormatter** - Converts data to XML format with customization options
- **YamlFormatter** - Converts data to YAML format
- **CsvFormatter** - Converts data to CSV format
- **PlainTextFormatter** - Converts data to plain text with customization options

## Using Formatters

### Direct Usage

You can use formatters directly by instantiating them and calling the `process()` method:

```php
use ByJG\Serializer\Formatter\JsonFormatter;
use ByJG\Serializer\Formatter\XmlFormatter;

$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
];

// Direct formatter usage
echo (new JsonFormatter())->process($data);
echo (new XmlFormatter())->process($data);
```

### Through Serialize Class

Alternatively, use the `Serialize` class for a more convenient API:

```php
use ByJG\Serializer\Serialize;

$result = Serialize::from($data)->toJson();
$result = Serialize::from($data)->toXml();
$result = Serialize::from($data)->toYaml();
$result = Serialize::from($data)->toCsv();
$result = Serialize::from($data)->toPlainText();
```

## Built-in Formatters

### JsonFormatter

The simplest formatter that converts data to JSON format using PHP's native `json_encode()`.

```php
use ByJG\Serializer\Formatter\JsonFormatter;

$formatter = new JsonFormatter();
echo $formatter->process($data);

// Output:
// {"name":"John Doe","email":"john@example.com","age":30}
```

**Features:**
- No configuration options
- Uses PHP's `json_encode()` with default settings
- Handles both arrays and objects

### XmlFormatter

Converts data to XML format with extensive customization options.

#### Basic Usage

```php
use ByJG\Serializer\Formatter\XmlFormatter;

$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com'
];

$formatter = new XmlFormatter();
echo $formatter->process($data);

// Output:
// <?xml version="1.0"?>
// <root>
//   <name>John Doe</name>
//   <email>john@example.com</email>
// </root>
```

#### Configuration Methods

**1. withRootElement(string $rootElement): XmlFormatter**

Customizes the XML root element name (default: "root").

```php
$xml = (new XmlFormatter())
    ->withRootElement("user")
    ->process($data);

// Output:
// <?xml version="1.0"?>
// <user>
//   <name>John Doe</name>
//   <email>john@example.com</email>
// </user>
```

**2. withListElement(string $listElement): XmlFormatter**

Customizes the element name for array items (default: "item").

```php
$users = [
    ['name' => 'John', 'age' => 30],
    ['name' => 'Jane', 'age' => 25]
];

$xml = (new XmlFormatter())
    ->withRootElement("users")
    ->withListElement("user")
    ->process($users);

// Output:
// <?xml version="1.0"?>
// <users>
//   <user>
//     <name>John</name>
//     <age>30</age>
//   </user>
//   <user>
//     <name>Jane</name>
//     <age>25</age>
//   </user>
// </users>
```

**3. withListElementSuffix(): XmlFormatter**

Adds numeric suffixes to list element names for better identification.

```php
$xml = (new XmlFormatter())
    ->withRootElement("users")
    ->withListElement("user")
    ->withListElementSuffix()  // Enables numeric suffixes
    ->process($users);

// Output:
// <?xml version="1.0"?>
// <users>
//   <user0>
//     <name>John</name>
//     <age>30</age>
//   </user0>
//   <user1>
//     <name>Jane</name>
//     <age>25</age>
//   </user1>
// </users>
```

#### Complete Example

```php
$products = [
    ['id' => 1, 'name' => 'Laptop', 'price' => 999],
    ['id' => 2, 'name' => 'Mouse', 'price' => 25]
];

$xml = (new XmlFormatter())
    ->withRootElement("catalog")
    ->withListElement("product")
    ->withListElementSuffix()
    ->process($products);
```

### YamlFormatter

Converts data to YAML format using the Symfony YAML component.

```php
use ByJG\Serializer\Formatter\YamlFormatter;

$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'roles' => ['admin', 'user']
];

$formatter = new YamlFormatter();
echo $formatter->process($data);

// Output:
// name: John Doe
// email: john@example.com
// roles:
//   - admin
//   - user
```

**Features:**
- No configuration options
- Uses Symfony YAML component
- Handles nested arrays and objects elegantly

### CsvFormatter

Converts data to CSV (Comma-Separated Values) format.

#### Basic Usage

```php
use ByJG\Serializer\Formatter\CsvFormatter;

$users = [
    ['name' => 'John Doe', 'email' => 'john@example.com', 'age' => 30],
    ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'age' => 25]
];

$formatter = new CsvFormatter();
echo $formatter->process($users);

// Output:
// name,email,age
// "John Doe",john@example.com,30
// "Jane Smith",jane@example.com,25
```

#### Behavior

- **Headers**: Automatically generates headers from the keys of the first array element
- **Single Arrays**: If you pass a single associative array, it's automatically wrapped in an array
- **Empty Arrays**: Returns an empty string
- **Escape Character**: Uses backslash (`\\`) as the escape character
- **Multidimensional Support**: Handles arrays of arrays; nested objects are not supported

#### Example with Single Object

```php
$user = ['name' => 'John', 'email' => 'john@example.com'];

echo (new CsvFormatter())->process($user);

// Output:
// name,email
// John,john@example.com
```

### PlainTextFormatter

Converts data to plain text format with extensive customization options.

#### Basic Usage

```php
use ByJG\Serializer\Formatter\PlainTextFormatter;

$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 30
];

$formatter = new PlainTextFormatter();
echo $formatter->process($data);

// Output:
// John Doe
// john@example.com
// 30
```

#### Configuration Methods

**1. withBreakLine(string $breakLine): PlainTextFormatter**

Customizes the line break character/string (default: `"\n"`).

```php
$text = (new PlainTextFormatter())
    ->withBreakLine(" | ")
    ->process($data);

// Output: John Doe | john@example.com | 30 |
```

**2. withStartOfLine(string $startOfLine): PlainTextFormatter**

Adds a prefix to the start of each line (default: `""`).

```php
$text = (new PlainTextFormatter())
    ->withStartOfLine("- ")
    ->process($data);

// Output:
// - John Doe
// - john@example.com
// - 30
```

**3. withIgnorePropertyName(bool $ignorePropertyName): PlainTextFormatter**

Controls whether property names are included in the output (default: `true`).

```php
$text = (new PlainTextFormatter())
    ->withIgnorePropertyName(false)  // Include property names
    ->process($data);

// Output:
// name=John Doe
// email=john@example.com
// age=30
```

#### HTML List Example

Create an HTML list using PlainTextFormatter:

```php
$data = ['Apple', 'Banana', 'Orange'];

$html = (new PlainTextFormatter())
    ->withStartOfLine("<li>")
    ->withBreakLine("</li>\n")
    ->process($data);

echo "<ul>\n" . $html . "</ul>";

// Output:
// <ul>
// <li>Apple</li>
// <li>Banana</li>
// <li>Orange</li>
// </ul>
```

#### Complete Example

```php
$products = [
    'Laptop' => 999,
    'Mouse' => 25,
    'Keyboard' => 75
];

$text = (new PlainTextFormatter())
    ->withIgnorePropertyName(false)
    ->withStartOfLine("* ")
    ->withBreakLine("\n")
    ->process($products);

// Output:
// * Laptop=999
// * Mouse=25
// * Keyboard=75
```

## FormatterInterface

All formatters implement the `FormatterInterface`, which defines a single method:

```php
namespace ByJG\Serializer\Formatter;

interface FormatterInterface
{
    /**
     * Process the serializable data and return formatted output
     *
     * @param array|object $serializable The data to format
     * @return string|bool The formatted output
     */
    public function process(array|object $serializable): string|bool;
}
```

## Creating Custom Formatters

You can create custom formatters by implementing the `FormatterInterface`:

### Example: HTML Table Formatter

```php
namespace MyApp\Formatters;

use ByJG\Serializer\Formatter\FormatterInterface;
use ByJG\Serializer\Serialize;

class HtmlTableFormatter implements FormatterInterface
{
    private bool $includeHeaders = true;
    private string $tableClass = '';

    public function withTableClass(string $class): self
    {
        $this->tableClass = $class;
        return $this;
    }

    public function withoutHeaders(): self
    {
        $this->includeHeaders = false;
        return $this;
    }

    public function process(array|object $serializable): string|bool
    {
        // Convert object to array if needed
        if (is_object($serializable)) {
            $serializable = Serialize::from($serializable)->toArray();
        }

        // Ensure we have an array of arrays
        if (empty($serializable)) {
            return '<table></table>';
        }

        $isMulti = is_array(reset($serializable));
        if (!$isMulti) {
            $serializable = [$serializable];
        }

        // Start table
        $class = $this->tableClass ? " class=\"{$this->tableClass}\"" : '';
        $html = "<table{$class}>\n";

        // Add headers
        if ($this->includeHeaders) {
            $headers = array_keys(reset($serializable));
            $html .= "  <thead>\n    <tr>\n";
            foreach ($headers as $header) {
                $html .= "      <th>" . htmlspecialchars($header) . "</th>\n";
            }
            $html .= "    </tr>\n  </thead>\n";
        }

        // Add rows
        $html .= "  <tbody>\n";
        foreach ($serializable as $row) {
            $html .= "    <tr>\n";
            foreach ($row as $value) {
                $html .= "      <td>" . htmlspecialchars($value) . "</td>\n";
            }
            $html .= "    </tr>\n";
        }
        $html .= "  </tbody>\n</table>";

        return $html;
    }
}
```

### Using the Custom Formatter

```php
use MyApp\Formatters\HtmlTableFormatter;

$users = [
    ['name' => 'John Doe', 'email' => 'john@example.com'],
    ['name' => 'Jane Smith', 'email' => 'jane@example.com']
];

$html = (new HtmlTableFormatter())
    ->withTableClass('table table-striped')
    ->process($users);

echo $html;
```

### Example: Markdown Formatter

```php
namespace MyApp\Formatters;

use ByJG\Serializer\Formatter\FormatterInterface;
use ByJG\Serializer\Serialize;

class MarkdownFormatter implements FormatterInterface
{
    public function process(array|object $serializable): string|bool
    {
        if (is_object($serializable)) {
            $serializable = Serialize::from($serializable)->toArray();
        }

        if (empty($serializable)) {
            return '';
        }

        // Check if it's a list of objects
        $isMulti = is_array(reset($serializable));
        if (!$isMulti) {
            // Single object - format as key-value pairs
            $markdown = '';
            foreach ($serializable as $key => $value) {
                $markdown .= "**{$key}**: {$value}\n\n";
            }
            return $markdown;
        }

        // Multiple objects - format as table
        $headers = array_keys(reset($serializable));

        // Create header row
        $markdown = '| ' . implode(' | ', $headers) . " |\n";

        // Create separator
        $markdown .= '| ' . implode(' | ', array_fill(0, count($headers), '---')) . " |\n";

        // Create data rows
        foreach ($serializable as $row) {
            $markdown .= '| ' . implode(' | ', array_values($row)) . " |\n";
        }

        return $markdown;
    }
}
```

### Using the Markdown Formatter

```php
use MyApp\Formatters\MarkdownFormatter;

$users = [
    ['name' => 'John Doe', 'role' => 'Admin'],
    ['name' => 'Jane Smith', 'role' => 'User']
];

echo (new MarkdownFormatter())->process($users);

// Output:
// | name | role |
// | --- | --- |
// | John Doe | Admin |
// | Jane Smith | User |
```

## Best Practices

### 1. Choose the Right Formatter

- **JsonFormatter**: API responses, data storage, JavaScript integration
- **XmlFormatter**: Legacy systems, SOAP services, RSS feeds
- **YamlFormatter**: Configuration files, human-readable data
- **CsvFormatter**: Data exports, spreadsheet compatibility
- **PlainTextFormatter**: Log files, simple displays, custom text formats

### 2. Use Configuration Methods

Take advantage of formatter configuration methods to customize output:

```php
// Good - customized for your use case
$xml = (new XmlFormatter())
    ->withRootElement("products")
    ->withListElement("product")
    ->process($data);

// Less ideal - generic root element
$xml = (new XmlFormatter())->process($data);
```

### 3. Method Chaining

All configuration methods support fluent interfaces:

```php
$text = (new PlainTextFormatter())
    ->withBreakLine("\n")
    ->withStartOfLine("* ")
    ->withIgnorePropertyName(false)
    ->process($data);
```

### 4. Combine with Serialize Modifiers

Use Serialize modifiers before formatting:

```php
$json = Serialize::from($user)
    ->withIgnoreProperties(['password', 'secret'])
    ->withDoNotParseNullValues()
    ->toJson();
```

### 5. Custom Formatters

When built-in formatters don't meet your needs:
- Implement `FormatterInterface`
- Support method chaining with configuration
- Handle both arrays and objects
- Consider edge cases (empty data, nested structures)

## Performance Considerations

### Caching

Formatters don't cache results. If you're formatting the same data multiple times, cache the output:

```php
// Bad - formats twice
$json1 = (new JsonFormatter())->process($data);
$json2 = (new JsonFormatter())->process($data);

// Good - format once, reuse
$json = (new JsonFormatter())->process($data);
$result1 = $json;
$result2 = $json;
```

### Large Datasets

For large datasets, consider:
- **CSV**: Most efficient for tabular data
- **JSON**: Good balance of speed and features
- **XML**: Can be verbose and slower for large datasets

### Memory Usage

Formatters process data in memory. For very large datasets:
- Use streaming APIs if available
- Process data in chunks
- Consider database exports instead of in-memory formatting

## Related Components

- [Serialize](serialize.md) - Main serialization class that uses formatters
- [ObjectCopy](objectcopy.md) - Copy data between objects before formatting
- [BaseModel](basemodel.md) - Model base class with `toArray()` for formatting
