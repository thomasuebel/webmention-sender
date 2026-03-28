<?php

declare(strict_types=1);

namespace WebmentionSender;

use DateTimeImmutable;
use DateTimeInterface;
use WebmentionSender\Contract\HttpClientInterface;
use WebmentionSender\Exception\FeedParseException;
use WebmentionSender\Exception\HttpException;

class FeedParser
{
    public function __construct(private readonly HttpClientInterface $http) {}

    /**
     * Fetches and parses an RSS feed, returning a Post for each item with a non-empty URL.
     * Link extraction is intentionally not performed here — use LinkExtractor for that.
     *
     * @return Post[]
     * @throws FeedParseException
     * @throws HttpException
     */
    public function parse(string $feedUrl): array
    {
        $response = $this->http->get($feedUrl);

        if ($response['status'] !== 200) {
            throw new FeedParseException(
                sprintf('Feed returned HTTP %d for %s', $response['status'], $feedUrl)
            );
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($response['body']);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if ($xml === false) {
            throw new FeedParseException('Failed to parse RSS feed: invalid XML');
        }

        $posts = [];

        foreach ($xml->channel->item as $item) {
            $url   = trim((string) $item->link);
            $title = trim((string) $item->title);

            if ($url === '') {
                continue;
            }

            $posts[] = new Post($url, $title, $this->parseDate((string) $item->pubDate));
        }

        return $posts;
    }

    /**
     * Parses an RSS pubDate string into a DateTimeImmutable.
     * Returns null if the string is empty or cannot be parsed.
     */
    private function parseDate(string $raw): ?DateTimeImmutable
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::RFC2822, $raw);

        if ($date !== false) {
            return $date;
        }

        try {
            return new DateTimeImmutable($raw);
        } catch (\Exception) {
            return null;
        }
    }
}
