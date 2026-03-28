<?php

declare(strict_types=1);

namespace WebmentionSender;

use DOMDocument;
use DOMXPath;
use WebmentionSender\Contract\HttpClientInterface;
use WebmentionSender\Exception\HttpException;

class EndpointDiscoverer
{
    public function __construct(private readonly HttpClientInterface $http) {}

    /**
     * Discovers the webmention endpoint for a URL per the W3C spec:
     * https://www.w3.org/TR/webmention/#sender-discovers-receiver-webmention-endpoint
     *
     * Checks (in order):
     *   1. HTTP Link header
     *   2. HTML <link rel="webmention"> or <a rel="webmention">
     *
     * Returns null if no endpoint is found or the target is unreachable.
     */
    public function discover(string $url): ?string
    {
        try {
            $response = $this->http->get($url);
        } catch (HttpException) {
            return null;
        }

        if ($response['status'] < 200 || $response['status'] >= 400) {
            return null;
        }

        return $this->extractFromLinkHeader($response['headers']['link'] ?? '', $url)
            ?? $this->extractFromHtml($response['body'], $url);
    }

    private function extractFromLinkHeader(string $header, string $baseUrl): ?string
    {
        // Handles: Link: <https://example.com/webmention>; rel="webmention"
        if (preg_match('/<([^>]+)>\s*;\s*rel=["\']?webmention["\']?/i', $header, $matches)) {
            return $this->resolveUrl(trim($matches[1]), $baseUrl);
        }

        return null;
    }

    private function extractFromHtml(string $html, string $baseUrl): ?string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html, LIBXML_NOERROR);

        $xpath = new DOMXPath($dom);

        // Matches rel="webmention" and multi-value rel="me webmention" on both <link> and <a>
        $nodes = $xpath->query(
            '//*[contains(concat(" ", normalize-space(@rel), " "), " webmention ")]/@href'
        );

        if ($nodes !== false && $nodes->length > 0) {
            return $this->resolveUrl((string) $nodes->item(0)->nodeValue, $baseUrl);
        }

        return null;
    }

    private function resolveUrl(string $url, string $base): string
    {
        if (str_starts_with($url, 'http')) {
            return $url;
        }

        $scheme = parse_url($base, PHP_URL_SCHEME) ?? 'https';
        $host   = parse_url($base, PHP_URL_HOST) ?? '';

        if (str_starts_with($url, '//')) {
            return $scheme . ':' . $url;
        }

        return $scheme . '://' . $host . '/' . ltrim($url, '/');
    }
}
