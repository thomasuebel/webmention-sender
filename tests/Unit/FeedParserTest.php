<?php

declare(strict_types=1);

namespace WebmentionSender\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WebmentionSender\Contract\HttpClientInterface;
use WebmentionSender\Exception\FeedParseException;
use WebmentionSender\FeedParser;

final class FeedParserTest extends TestCase
{
    #[Test]
    public function itParsesPostsFromFeed(): void
    {
        $http = $this->mockGet($this->rssFixture([
            ['title' => 'Post One', 'link' => 'https://example.com/blog/post-one/'],
            ['title' => 'Post Two', 'link' => 'https://example.com/blog/post-two/'],
        ]));

        $posts = (new FeedParser($http))->parse('https://example.com/index.xml');

        $this->assertCount(2, $posts);
        $this->assertSame('Post One', $posts[0]->title);
        $this->assertSame('https://example.com/blog/post-one/', $posts[0]->url);
        $this->assertSame('Post Two', $posts[1]->title);
    }

    #[Test]
    public function itSkipsItemsWithEmptyUrls(): void
    {
        $http = $this->mockGet($this->rssFixture([
            ['title' => 'Valid', 'link' => 'https://example.com/blog/valid/'],
            ['title' => 'No URL', 'link' => ''],
        ]));

        $posts = (new FeedParser($http))->parse('https://example.com/index.xml');

        $this->assertCount(1, $posts);
        $this->assertSame('Valid', $posts[0]->title);
    }

    #[Test]
    public function itParsesPubDateIntoPublishedAt(): void
    {
        $http = $this->mockGet($this->rssFixture([
            [
                'title'   => 'Dated Post',
                'link'    => 'https://example.com/blog/dated/',
                'pubDate' => 'Thu, 26 Mar 2026 10:00:00 +0100',
            ],
        ]));

        $posts = (new FeedParser($http))->parse('https://example.com/index.xml');

        $this->assertNotNull($posts[0]->publishedAt);
        $this->assertSame('2026-03-26', $posts[0]->publishedAt->format('Y-m-d'));
    }

    #[Test]
    public function itSetsPublishedAtToNullForMissingPubDate(): void
    {
        $http = $this->mockGet($this->rssFixture([
            ['title' => 'Post', 'link' => 'https://example.com/blog/post/'],
        ]));

        $posts = (new FeedParser($http))->parse('https://example.com/index.xml');

        $this->assertNull($posts[0]->publishedAt);
    }

    #[Test]
    public function itThrowsOnNonSuccessfulHttpResponse(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willReturn(['status' => 404, 'headers' => [], 'body' => '']);

        $this->expectException(FeedParseException::class);
        $this->expectExceptionMessage('HTTP 404');

        (new FeedParser($http))->parse('https://example.com/index.xml');
    }

    #[Test]
    public function itThrowsOnInvalidXml(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willReturn(['status' => 200, 'headers' => [], 'body' => 'this is not xml']);

        $this->expectException(FeedParseException::class);

        (new FeedParser($http))->parse('https://example.com/index.xml');
    }

    #[Test]
    public function itReturnsEmptyArrayForFeedWithNoItems(): void
    {
        $http = $this->mockGet($this->rssFixture([]));

        $posts = (new FeedParser($http))->parse('https://example.com/index.xml');

        $this->assertSame([], $posts);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function mockGet(string $body): HttpClientInterface
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')->willReturn(['status' => 200, 'headers' => [], 'body' => $body]);
        return $http;
    }

    /** @param array<array{title: string, link: string, pubDate?: string}> $items */
    private function rssFixture(array $items): string
    {
        $itemsXml = implode('', array_map(function (array $item): string {
            $pubDate = isset($item['pubDate'])
                ? sprintf('<pubDate>%s</pubDate>', htmlspecialchars($item['pubDate']))
                : '';

            return sprintf(
                '<item><title>%s</title><link>%s</link>%s</item>',
                htmlspecialchars($item['title']),
                htmlspecialchars($item['link']),
                $pubDate,
            );
        }, $items));

        return <<<XML
            <?xml version="1.0" encoding="utf-8"?>
            <rss version="2.0">
              <channel>
                <title>Test Blog</title>
                <link>https://example.com/</link>
                {$itemsXml}
              </channel>
            </rss>
            XML;
    }
}
