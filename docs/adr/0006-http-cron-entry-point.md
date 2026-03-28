# ADR-0006: HTTP cron entry point for hosts without CLI access

## Status

Accepted

## Context

The primary entry point (`webmention-sender.php`) is CLI-only and is designed to live above the webroot. Some shared hosts do not permit cron jobs to run via `php` on the command line, and only support scheduling via an HTTP request to a URL.

The options considered were:

1. **Modify `webmention-sender.php` to support both CLI and HTTP**: removes the CLI guard and adds token auth inline. Simple, but violates the existing design constraint (the script lives above the webroot and must never be HTTP-accessible). It would also require the file to move into the webroot, exposing `src/`, `config.php`, and the state file to potential HTTP access depending on server configuration.

2. **Separate HTTP entry point (`webmention-cron.php`)**: a thin file placed in the webroot that authenticates, then bootstraps the same service graph as `webmention-sender.php`. The rest of the codebase (source, config, state) stays above the webroot.

3. **Shell-out from a PHP wrapper**: a webroot PHP file that calls `shell_exec('php /path/to/webmention-sender.php')`. Fragile, host-dependent, and requires `exec`/`shell_exec` to be enabled.

## Decision

Introduce `webmention-cron.php` as a separate entry point to be copied into the webroot. It registers the same PSR-4 autoloader and wires the same service graph as `webmention-sender.php`. It does not modify or import from the CLI entry point.

**Authentication** uses a shared secret (`Config::$cronToken`) checked with `hash_equals()` to prevent timing attacks. The token can be supplied via an `Authorization` header (preferred, not logged by web servers) or a `?token=` query parameter (for cron services that only support plain URLs). HTTP access is disabled by default (`cronToken: null`); an empty string token is explicitly rejected by `Config` validation.

**`SENDER_DIR`** is a constant at the top of the file that the user adjusts to point at the sender directory. Its existence is validated before the autoloader runs, with failures reported via `error_log()`.

## Consequences

- Hosts with HTTP-only cron can use the tool without changing the above-webroot layout.
- `webmention-sender.php` is unchanged; the CLI-only guarantee holds.
- Service wiring is duplicated between the two entry points. This is an accepted trade-off: the bootstrap is short, and a shared bootstrap file would add indirection for little gain at the current scale.
- Users must keep `SENDER_DIR` up to date if they move the sender directory.
- The `cronToken` must be kept secret. Tokens passed as query parameters appear in web server access logs; the README recommends the `Authorization` header where possible.
