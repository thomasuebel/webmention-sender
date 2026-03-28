# ADR-0002: JSON file for state persistence

## Status

Accepted

## Context

The tool needs to track which source→target webmention pairs have already been sent, so that re-running (e.g. daily via cron) does not result in duplicate sends. Several storage options were considered:

- **Flat text file** — simple but awkward to query or parse reliably
- **SQLite** — structured and queryable, but requires the `pdo_sqlite` extension which is not universally available on shared hosting
- **JSON file** — structured, human-readable, no extension beyond `json` (always available), trivially inspectable with any text editor

## Decision

Use a JSON file as the state store. The `StateManager` class reads the file on construction and writes it after every `markAsSent()` call. The format is a nested object keyed by source URL, then target URL, with an ISO 8601 timestamp as the value.

```json
{
  "https://example.com/blog/post/": {
    "https://other.com/page": "2026-03-26T08:00:00+01:00"
  }
}
```

The `StateInterface` contract means the storage backend can be swapped (e.g. to SQLite) without changing any other class.

## Consequences

- No database extension required. Works on any PHP 8.2+ host.
- The state file is human-readable and can be manually edited or deleted to force re-sending.
- Write-on-every-send means the file is always consistent with what was actually sent, at the cost of one file write per successful webmention.
- Concurrent runs (two cron jobs running simultaneously) could theoretically cause a write race. This is an acceptable risk for a daily cron on a personal blog.
