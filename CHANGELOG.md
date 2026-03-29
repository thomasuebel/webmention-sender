# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## v1.0.2 - 2026-03-29

### Fixed
- `LinkExtractor` now extracts `<link rel="in-reply-to">` elements from `<head>` in addition to `<a>` elements
- `WebmentionRunner` now verifies the state file is writable before fetching the feed or sending any webmentions; if the write fails, the run is aborted with an `[ERROR]` log entry so no webmentions are sent without being recorded
- `LinkExtractor` pre-processes HTML to quote unquoted `href` attribute values before DOM parsing, guarding against libxml2 truncating `href=https://...` at the colon on some versions

## v1.0.1 - 2026-03-28

### Added
- `webmention-cron.php` HTTP entry point for hosts that only support HTTP-based cron jobs
- `Config::cronToken` optional secret token for HTTP cron authentication; `null` disables HTTP access entirely

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
