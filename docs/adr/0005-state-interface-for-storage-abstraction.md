# ADR-0005: StateInterface for storage abstraction

## Status

Accepted

## Context

`WebmentionDispatcher` needs to check whether a webmention has already been sent and record new ones. In the first implementation it depended directly on the concrete `StateManager` class. This created two problems:

1. **Testability** — `StateManager` reads and writes a file on disk. Mocking a concrete class in PHPUnit requires removing `final`, which is a workaround rather than a design. Depending on an interface allows the test to supply a clean mock without touching the filesystem.
2. **Flexibility** — if the storage backend needs to change (e.g. SQLite for larger sites, or an in-memory stub for integration tests), depending on the concrete class would require changing type hints throughout.

## Decision

Extract `StateInterface` with two methods:

```php
interface StateInterface
{
    public function hasBeenSent(string $source, string $target): bool;
    public function markAsSent(string $source, string $target): void;
}
```

`StateManager` implements this interface. `WebmentionDispatcher` depends on `StateInterface`, not `StateManager`.

## Consequences

- `WebmentionDispatcher` is fully testable without touching the filesystem.
- Swapping the storage backend requires only a new class implementing `StateInterface` and a change to the entry point bootstrap — no other code changes.
- The interface is intentionally minimal. It does not expose listing or deleting entries, which are not needed by any current consumer.
