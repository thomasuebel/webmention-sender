# ADR-0007: Robust link extraction for unquoted attributes and link elements

## Status

Accepted

## Context

Two classes of link were silently dropped during extraction:

**1. Unquoted `href` attributes.**
PHP's `DOMDocument` (backed by libxml2) does not reliably parse unquoted attribute values that contain `://`. The value is truncated at the colon, so `href=https://example.com/post` yields `href="https"` in the resulting DOM. This is a common output of microformat-aware templates, which emit elements such as:

```html
<a class=u-in-reply-to rel=in-reply-to href=https://example.com/post hidden></a>
```

Because the href is not recovered correctly, no link is extracted and no webmention is sent.

**2. `<link>` elements in `<head>`.**
The same microformat pattern is also emitted as an HTML `<link>` element:

```html
<link rel="in-reply-to" href="https://example.com/post">
```

`LinkExtractor` only scanned `<a>` elements, so any target declared exclusively via a `<link>` element was ignored.

Both gaps cause webmentions to be silently dropped for posts that explicitly mark their reply targets, which is the most important case to get right.

## Decision

**Unquoted `href` fix:** Apply a targeted regex pre-pass to the raw HTML before it is handed to `DOMDocument`, quoting any unquoted `href` value that looks like an absolute URL (`href=https://...` becomes `href="https://..."`). This is narrow in scope and does not affect attributes other than `href`, or hrefs that are already quoted.

**`<link>` element scanning:** After processing `<a>` elements, also iterate over `getElementsByTagName('link')` and collect external `href` values from elements whose `rel` attribute includes `in-reply-to`. Scanning only `in-reply-to` (rather than all `<link>` elements) avoids sending webmentions for stylesheet, preload, and other resource-hint links that point to CDN or third-party hosts but are not content references.

## Consequences

- Reply webmentions are reliably sent regardless of whether the theme outputs quoted or unquoted `href` values.
- `<link rel="in-reply-to">` in `<head>` now triggers webmention sending.
- The regex pre-pass adds a negligible cost (one `preg_replace` call per page fetch) and introduces no new dependency.
- Only `in-reply-to` is handled for `<link>` elements; other microformat link types (`like-of`, `repost-of`, etc.) via `<link>` remain out of scope unless a future need arises.
