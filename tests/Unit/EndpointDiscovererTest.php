<?php

declare(strict_types=1);

namespace WebmentionSender\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WebmentionSender\Contract\HttpClientInterface;
use WebmentionSender\EndpointDiscoverer;
use WebmentionSender\Exception\HttpException;

final class EndpointDiscovererTest extends TestCase
{
    #[Test]
    public function itDiscoversEndpointFromLinkHeader(): void
    {
        $http = $this->mockGet(headers: ['link' => '<https://example.com/webmention>; rel="webmention"']);

        $endpoint = (new EndpointDiscoverer($http))->discover('https://example.com/post');

        $this->assertSame('https://example.com/webmention', $endpoint);
    }

    #[Test]
    public function itDiscoversEndpointFromHtmlLinkTag(): void
    {
        $html = '<html><head><link rel="webmention" href="https://example.com/webmention"></head></html>';
        $http = $this->mockGet(body: $html);

        $endpoint = (new EndpointDiscoverer($http))->discover('https://example.com/post');

        $this->assertSame('https://example.com/webmention', $endpoint);
    }

    #[Test]
    public function itDiscoversEndpointFromMultiValueRelAttribute(): void
    {
        $html = '<html><head><link rel="me webmention" href="https://example.com/webmention"></head></html>';
        $http = $this->mockGet(body: $html);

        $endpoint = (new EndpointDiscoverer($http))->discover('https://example.com/post');

        $this->assertSame('https://example.com/webmention', $endpoint);
    }

    #[Test]
    public function itPrefersLinkHeaderOverHtml(): void
    {
        $html = '<html><head><link rel="webmention" href="https://example.com/html-endpoint"></head></html>';
        $http = $this->mockGet(
            headers: ['link' => '<https://example.com/header-endpoint>; rel="webmention"'],
            body: $html,
        );

        $endpoint = (new EndpointDiscoverer($http))->discover('https://example.com/post');

        $this->assertSame('https://example.com/header-endpoint', $endpoint);
    }

    #[Test]
    public function itResolvesRootRelativeEndpointUrl(): void
    {
        $html = '<html><head><link rel="webmention" href="/webmention"></head></html>';
        $http = $this->mockGet(body: $html);

        $endpoint = (new EndpointDiscoverer($http))->discover('https://example.com/post');

        $this->assertSame('https://example.com/webmention', $endpoint);
    }

    #[Test]
    public function itReturnsNullWhenNoEndpointFound(): void
    {
        $http = $this->mockGet(body: '<html><head><title>No webmention here</title></head></html>');

        $endpoint = (new EndpointDiscoverer($http))->discover('https://example.com/post');

        $this->assertNull($endpoint);
    }

    #[Test]
    public function itReturnsNullOnHttpException(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willThrowException(new HttpException('Connection refused'));

        $endpoint = (new EndpointDiscoverer($http))->discover('https://example.com/post');

        $this->assertNull($endpoint);
    }

    #[Test]
    public function itReturnsNullOnErrorStatusCode(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willReturn(['status' => 404, 'headers' => [], 'body' => '']);

        $endpoint = (new EndpointDiscoverer($http))->discover('https://example.com/post');

        $this->assertNull($endpoint);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /** @param array<string, string> $headers */
    private function mockGet(array $headers = [], string $body = ''): HttpClientInterface
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willReturn(['status' => 200, 'headers' => $headers, 'body' => $body]);
        return $http;
    }
}
