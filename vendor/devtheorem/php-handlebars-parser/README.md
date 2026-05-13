# PHP Handlebars Parser

Parse [Handlebars](https://handlebarsjs.com) templates to a spec-compliant AST with PHP.

Implements the same lexical analysis and grammar specification as Handlebars.js, so any template
which can (or cannot) be parsed by Handlebars.js should parse (or error) the same way here.

> [!NOTE]  
> This project is only a parser. To compile Handlebars templates to native PHP for execution,
> see [PHP Handlebars](https://github.com/devtheorem/php-handlebars), which uses this parser.

## Installation

`composer require devtheorem/php-handlebars-parser`

## Usage

```php
use DevTheorem\HandlebarsParser\ParserFactory;

$parser = (new ParserFactory())->create();

$template = "Hello {{name}}!";

$result = $parser->parse($template);
```

If the template contains invalid syntax, an exception will be thrown.
Otherwise, `$result` will contain a `DevTheorem\HandlebarsParser\Ast\Program` instance.

## Whitespace handling

The `ignoreStandalone` Handlebars compilation option can be passed to `parse()`:

```php
$result = $parser->parse($template, ignoreStandalone: true);
```

## Author

Theodore Brown  
https://theodorejb.me
