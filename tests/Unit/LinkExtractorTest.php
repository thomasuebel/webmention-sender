<?php

declare(strict_types=1);

namespace WebmentionSender\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WebmentionSender\Contract\HttpClientInterface;
use WebmentionSender\Exception\HttpException;
use WebmentionSender\LinkExtractor;

final class LinkExtractorTest extends TestCase
{
    #[Test]
    public function itExtractsExternalLinks(): void
    {
        $http = $this->mockGet(
            'https://example.com/blog/post/',
            '<html><body><a href="https://other.com/page">Link</a></body></html>',
        );

        $links = (new LinkExtractor($http))->extract('https://example.com/blog/post/');

        $this->assertSame(['https://other.com/page'], $links);
    }

    #[Test]
    public function itIgnoresSelfLinks(): void
    {
        $html = <<<HTML
            <html><body>
                <a href="https://example.com/blog/other/">Internal</a>
                <a href="https://other.com/page">External</a>
            </body></html>
            HTML;

        $http = $this->mockGet('https://example.com/blog/post/', $html);

        $links = (new LinkExtractor($http))->extract('https://example.com/blog/post/');

        $this->assertSame(['https://other.com/page'], $links);
    }

    #[Test]
    public function itIgnoresRelativeAndNonHttpLinks(): void
    {
        $html = <<<HTML
            <html><body>
                <a href="/relative/path">Relative</a>
                <a href="mailto:hi@example.com">Email</a>
            </body></html>
            HTML;

        $http = $this->mockGet('https://example.com/blog/post/', $html);

        $links = (new LinkExtractor($http))->extract('https://example.com/blog/post/');

        $this->assertSame([], $links);
    }

    #[Test]
    public function itDeduplicatesLinks(): void
    {
        $html = <<<HTML
            <html><body>
                <a href="https://other.com/page">First mention</a>
                <a href="https://other.com/page">Second mention</a>
            </body></html>
            HTML;

        $http = $this->mockGet('https://example.com/blog/post/', $html);

        $links = (new LinkExtractor($http))->extract('https://example.com/blog/post/');

        $this->assertCount(1, $links);
        $this->assertSame('https://other.com/page', $links[0]);
    }

    #[Test]
    public function itReturnsEmptyArrayOnHttpException(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willThrowException(new HttpException('Connection failed'));

        $links = (new LinkExtractor($http))->extract('https://example.com/blog/post/');

        $this->assertSame([], $links);
    }

    #[Test]
    public function itReturnsEmptyArrayOnNonSuccessResponse(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willReturn(['status' => 404, 'headers' => [], 'body' => '']);

        $links = (new LinkExtractor($http))->extract('https://example.com/blog/post/');

        $this->assertSame([], $links);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function mockGet(string $url, string $body): HttpClientInterface
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
             ->with($url)
             ->willReturn(['status' => 200, 'headers' => [], 'body' => $body]);
        return $http;
    }
}
