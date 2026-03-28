<?php

declare(strict_types=1);

namespace WebmentionSender;

use DOMDocument;
use WebmentionSender\Contract\HttpClientInterface;
use WebmentionSender\Exception\HttpException;

class LinkExtractor
{
    public function __construct(private readonly HttpClientInterface $http) {}

    /**
     * Fetches a post URL and returns all unique external links found in its HTML.
     * Returns an empty array if the page cannot be fetched.
     *
     * @return string[]
     */
    public function extract(string $postUrl): array
    {
        try {
            $response = $this->http->get($postUrl);
        } catch (HttpException) {
            return [];
        }

        if ($response['status'] < 200 || $response['status'] >= 300) {
            return [];
        }

        return $this->extractExternalLinks($response['body'], $postUrl);
    }

    /**
     * Parses HTML and returns unique external links, excluding links to the source domain.
     *
     * @return string[]
     */
    private function extractExternalLinks(string $html, string $sourceUrl): array
    {
        if ($html === '') {
            return [];
        }

        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR);

        $sourceHost = parse_url($sourceUrl, PHP_URL_HOST) ?? '';
        $links      = [];

        foreach ($dom->getElementsByTagName('a') as $anchor) {
            $href = $anchor->getAttribute('href');

            if (!str_starts_with($href, 'http')) {
                continue;
            }

            $host = parse_url($href, PHP_URL_HOST) ?? '';

            if ($host === '' || $host === $sourceHost) {
                continue;
            }

            $links[] = $href;
        }

        return array_values(array_unique($links));
    }
}
