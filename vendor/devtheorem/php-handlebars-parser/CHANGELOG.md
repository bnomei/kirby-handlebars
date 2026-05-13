# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [2.1.1] Short Circuit - 2026-04-29

### Changed
- Optimized lexer and whitespace control by avoiding unnecessary function calls.
- Excluded the internal grammar directory from package exports.


## [2.1.0] Uncontrolled Space - 2026-04-26

### Added
- `parseWithoutProcessing()` method.

### Changed
- Optimized whitespace control and lexer position advancement.
- Removed underscore from `PathExpression::this_` to align with the Handlebars.js AST.

### Fixed
- Close tag indentation wasn't consistently stripped from chained `{{else if}}` branches of a standalone block.
- Tilde whitespace control in one `{{~else if}}` branch unexpectedly leaked onto unrelated branches.

Both of the above issues stemmed from bugs in the Handlebars.js parser, and I opened PR
[handlebars-lang/handlebars-parser#31](https://github.com/handlebars-lang/handlebars-parser/pull/31) to also fix them there.


## [2.0.0] Stateless Parser - 2026-04-02

### Changed
- Moved the optional `ignoreStandalone` parameter from `ParserFactory::create()` to the `Parser::parse()` method.
  This makes the parser instance independent of options so it can be reused when compiling multiple templates,
  enabling better performance and lower memory usage.


## [1.1.2] Zero Head - 2026-04-01

### Fixed
- Parsing of `0` as path expression head (https://github.com/devtheorem/php-handlebars/issues/16).

### Changed
- Optimized parser slightly by replacing a few unnecessary `preg_match` calls.


## [1.1.1] Root SubExpression - 2026-03-23

### Fixed
- Parsing of SubExpressions that are PathExpression roots.


## [1.1.0] Optimal Lexer - 2026-02-24

This release will be the foundation for [PHP Handlebars](https://github.com/devtheorem/php-handlebars) 1.0.

### Changed
- Optimized position tracking to avoid per-token full-text rescans.
- Made use of the `preg_match()` offset parameter and `\G` anchor to avoid per-token string allocations.
- Combined alternation pattern per state: replaced N separate `preg_match()` calls (one per rule) with a single
  call against a per-state `/\G(?:(rule0)|(rule1)|...)/` pattern, letting PCRE try all alternatives in one pass.

### Added
- Benchmark to test parsing a large, complex template 1000 times.

In this release, parsing a complex, 13 KB Handlebars template is now about 30x faster than in v1.0.0.


## [1.0.0] Initial Release - 2026-02-20

The full Handlebars.js grammar, whitespace control, and parse error handling is now implemented in native PHP.


[2.1.1]: https://github.com/devtheorem/php-handlebars-parser/compare/v2.1.0...v2.1.1
[2.1.0]: https://github.com/devtheorem/php-handlebars-parser/compare/v2.0.0...v2.1.0
[2.0.0]: https://github.com/devtheorem/php-handlebars-parser/compare/v1.1.2...v2.0.0
[1.1.2]: https://github.com/devtheorem/php-handlebars-parser/compare/v1.1.1...v1.1.2
[1.1.1]: https://github.com/devtheorem/php-handlebars-parser/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/devtheorem/php-handlebars-parser/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/devtheorem/php-handlebars-parser/tree/v1.0.0
