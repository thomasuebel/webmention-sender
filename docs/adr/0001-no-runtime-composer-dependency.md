# ADR-0001: No runtime Composer dependency

## Status

Accepted

## Context

The tool is designed to run on shared hosting (e.g. allinkl) via a cron job, where running `composer install` in production is possible but adds friction. Many shared hosts have PHP available but Composer is not guaranteed, or requires SSH access which may not be available. The deployment model should be as simple as possible: copy the files, configure, and set up the cron.

## Decision

The entry point (`webmention-sender.php`) registers a custom PSR-4 autoloader that maps the `WebmentionSender\` namespace to `src/`. No `vendor/autoload.php` is required at runtime. Composer is used exclusively as a development tool for PHPUnit.

## Consequences

- Deployment requires only a PHP binary and the source files. No `composer install` step in production.
- Composer packages may not be added to `require` — only `require-dev`. Any functionality needed at runtime must be implemented using the PHP standard library.
- The custom autoloader is simple and covers the PSR-4 convention used by all classes in this project. It does not support more complex autoloading scenarios, which is an acceptable trade-off for a single-namespace CLI tool.
