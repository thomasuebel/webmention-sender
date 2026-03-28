# ADR-0003: Fetch post HTML for link extraction

## Status

Accepted

## Context

Webmentions must be sent for each external link found in a post. The two obvious sources for those links are:

1. **The RSS feed** — Hugo's default RSS template only populates `<description>` with the post summary, not the full HTML. `<content:encoded>` is not emitted by default. Relying on RSS content would silently miss links in any post where the feed does not include full HTML.

2. **The post's HTML page** — fetching the canonical URL of each post gives the full rendered page, including all links regardless of RSS configuration.

## Decision

`LinkExtractor` fetches each post's HTML page via HTTP GET and extracts links from the DOM. `FeedParser` is responsible only for reading post URLs and titles from the feed — link extraction is explicitly not its concern.

## Consequences

- Link extraction is reliable regardless of how the RSS feed is configured.
- Each run makes one additional HTTP GET request per post. For a blog with many old posts this is mitigated by `Config::lookbackDays`, which limits processing to recent posts.
- The tool depends on the post page being publicly accessible at the URL in the feed.
- `FeedParser` and `LinkExtractor` have clearly separated responsibilities, which simplifies testing both independently.
