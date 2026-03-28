# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## v1.0.0 - 2026-03-28

### Added
- `StateInterface` contract so state storage can be swapped without changing dependent classes
- `WebmentionDispatcher` class handling the per-link dispatch cycle (endpoint discovery, HTTP POST, state update)
- `Config::connectTimeout` — separate connection timeout (default: 5s) independent of transfer timeout
- `Config::lookbackDays` — optional filter to skip posts older than N days
- `Post::publishedAt` — parsed from RSS `<pubDate>`, used by lookback filter
- Config validation in `Config` constructor with descriptive `InvalidArgumentException` messages

### Changed
- `WebmentionRunner` no longer calls `exit()` — throws `FeedParseException` or `HttpException` instead; entry point handles exit codes
- `WebmentionRunner` delegates dispatch logic to `WebmentionDispatcher`; runner is now orchestration-only
- `StateManager` implements `StateInterface`; state file now uses a nested JSON structure (`source → target → sent_at`) instead of a flat string key
- `StateManager` key collisions no longer possible (URL-keyed nesting replaces `"source -> target"` string keys)
- `HttpClient` accumulates duplicate `Link` headers by joining with `, ` so multi-value and multi-header Link fields are handled correctly
- `HttpClient` sets `CURLOPT_CONNECTTIMEOUT` separately from `CURLOPT_TIMEOUT`
- `FeedParser` saves and restores `libxml_use_internal_errors` state instead of setting it globally
- `FeedParser` clears libxml errors after parsing to avoid leaking error state
