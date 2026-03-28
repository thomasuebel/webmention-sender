# Contributing

Contributions are welcome. Please open an issue before submitting a pull request for anything beyond small bug fixes, so we can agree on direction first.

## Requirements

- PHP 8.2+
- Composer

## Setup

```sh
git clone https://github.com/thomasuebel/webmention-sender.git
cd webmention-sender
composer install
```

## Running tests

```sh
./test
```

Or directly:

```sh
vendor/bin/phpunit
```

## Project structure

```
webmention-sender.php       Entry point — CLI guard, PSR-4 autoloader, bootstrap
config.example.php          Copy to config.php and fill in values
src/
  Config.php                Value object with validation; all configuration lives here
  Post.php                  Value object representing one RSS item (url, title, publishedAt)
  FeedParser.php            Fetches and parses RSS feed; returns Post[]
  LinkExtractor.php         Fetches a post's HTML page; extracts external links
  EndpointDiscoverer.php    Discovers webmention endpoint for a URL (W3C spec)
  WebmentionDispatcher.php  Per-link cycle: check state, discover, POST, update state
  WebmentionRunner.php      Orchestrator: feed → posts → links → dispatcher
  HttpClient.php            curl-based HTTP client; implements HttpClientInterface
  StateManager.php          JSON state file; implements StateInterface
  Logger.php                Timestamped stdout/stderr logging
  Contract/
    HttpClientInterface.php
    StateInterface.php
  Exception/
    FeedParseException.php
    HttpException.php
    StateException.php
docs/adr/                   Architecture Decision Records (Nygard format)
tests/Unit/                 PHPUnit 11 unit tests — one file per class
```

## Guidelines

- All changes must include tests. New classes get a new test file; changes to existing behaviour update the existing tests.
- Run `./test` before submitting — no failing tests, no exceptions.
- Keep classes focused. If a class starts doing two things, it should be two classes.
- No external runtime dependencies. The tool is designed to run on shared hosting with a plain `php` binary. Do not add Composer packages to `require` (only `require-dev` for tooling).
- Value objects (`Config`, `Post`) and `HttpClient` are `final`. Service classes are not, so PHPUnit can mock them in tests. Follow this convention for any new classes.
- PHP 8.2+ syntax is expected throughout: readonly properties, constructor promotion, named arguments, and `#[Attribute]`-style PHPUnit annotations.
