# Agent Overview — webmention-sender

This document is intended for AI coding assistants and automated tooling working on this codebase. It summarises the project's purpose, structure, and conventions in one place.

## What this project does

A CLI PHP tool that:
1. Fetches an RSS feed and extracts post URLs
2. Fetches each post's HTML page to discover outgoing external links
3. Discovers the webmention endpoint for each linked URL (W3C spec)
4. POSTs a webmention for each source→target pair not already recorded
5. Persists sent webmentions to a JSON state file to prevent re-sending

It is designed to run on shared hosting via cron with no runtime dependencies beyond a standard PHP 8.2+ installation.

## Entry point and bootstrap

`webmention-sender.php` is the only executable. It:
- Guards against non-CLI invocation (`PHP_SAPI !== 'cli'`)
- Registers a custom PSR-4 autoloader (no Composer at runtime)
- Loads `config.php` (user-created from `config.example.php`)
- Wires all services together via constructor injection
- Delegates to `WebmentionRunner`

## Class map

```
src/
  Config.php                Value object — all config, validated in constructor
  Post.php                  Value object — one RSS item (url, title, publishedAt)
  FeedParser.php            RSS fetch + parse → Post[]
  LinkExtractor.php         HTML fetch + DOM parse → external link URLs
  EndpointDiscoverer.php    W3C endpoint discovery (Link header + <link> tag)
  WebmentionDispatcher.php  Per-link cycle: state check → discover → POST → persist
  WebmentionRunner.php      Orchestrator: feed → posts → links → dispatcher
  HttpClient.php            curl wrapper (GET + POST, header parsing, timeouts)
  StateManager.php          JSON file persistence for sent webmentions
  Logger.php                Timestamped stdout/stderr with debug level
  Contract/
    HttpClientInterface.php
    StateInterface.php
  Exception/
    FeedParseException.php
    HttpException.php
    StateException.php
```

## Key conventions

**Finality:**
- `final` — value objects (`Config`, `Post`) and `HttpClient`
- not `final` — all service classes, so PHPUnit can mock them in tests

**Strict types:** every file has `declare(strict_types=1)`.

**PHP version:** 8.2+. Use readonly properties, constructor promotion, named arguments, and `#[Attribute]`-style PHPUnit test annotations throughout.

**Dependencies:** zero runtime Composer dependencies. Do not add to `require` in `composer.json`. PHPUnit is the only `require-dev` package.

**Testing:** one test file per class in `tests/Unit/`. All changes must include tests. Run with `./test`.

## Architecture decisions

Key decisions are documented as ADRs in `docs/adr/`:

| ADR | Decision |
|-----|----------|
| [0001](adr/0001-no-runtime-composer-dependency.md) | No runtime Composer dependency |
| [0002](adr/0002-json-file-for-state-persistence.md) | JSON file for state persistence |
| [0003](adr/0003-fetch-post-html-for-link-extraction.md) | Fetch post HTML rather than using RSS content |
| [0004](adr/0004-nested-json-state-structure.md) | Nested JSON structure for state |
| [0005](adr/0005-state-interface-for-storage-abstraction.md) | StateInterface for storage abstraction |

Consult the relevant ADR before changing anything these decisions govern.

## Further reading

- `README.md` — setup, usage, configuration reference
- `CONTRIBUTING.md` — contributor workflow, project structure, guidelines
