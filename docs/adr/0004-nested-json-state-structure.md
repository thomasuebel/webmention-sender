# ADR-0004: Nested JSON state structure

## Status

Accepted (supersedes initial flat key design)

## Context

The initial state file used a flat JSON object with a concatenated string key:

```json
{
  "https://source.com/post -> https://target.com/page": {
    "source": "...",
    "target": "...",
    "sent_at": "..."
  }
}
```

This had two problems:

1. **Theoretical collision** — a URL containing the literal string ` -> ` would produce an ambiguous key.
2. **Poor queryability** — answering "what targets have I sent for this source post?" requires scanning all keys with string matching rather than a simple object lookup.

## Decision

Use a nested structure keyed first by source URL, then by target URL:

```json
{
  "https://source.com/post/": {
    "https://target.com/page": "2026-03-26T08:00:00+01:00"
  }
}
```

The `sent_at` timestamp is the leaf value. Source and target are already known from the key path, so repeating them in the value is unnecessary.

## Consequences

- No key collision is possible regardless of URL content.
- All targets for a given source are directly accessible as `$state[$source]`.
- The format is more compact: one string per entry instead of a nested object.
- This is a breaking change to the state file schema. Any existing state files in the old format will be misread as if nothing has been sent. Since the tool was not yet released when this decision was made, no migration path is needed.
