<?php

declare(strict_types=1);

namespace WebmentionSender\Tests\Unit;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WebmentionSender\Config;
use WebmentionSender\FeedParser;
use WebmentionSender\LinkExtractor;
use WebmentionSender\Logger;
use WebmentionSender\Post;
use WebmentionSender\WebmentionDispatcher;
use WebmentionSender\WebmentionRunner;

final class WebmentionRunnerTest extends TestCase
{
    private FeedParser&MockObject $parser;
    private LinkExtractor&MockObject $linkExtractor;
    private WebmentionDispatcher&MockObject $dispatcher;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->parser        = $this->createMock(FeedParser::class);
        $this->linkExtractor = $this->createMock(LinkExtractor::class);
        $this->dispatcher    = $this->createMock(WebmentionDispatcher::class);
        $this->logger        = new Logger(verbose: false);
    }

    #[Test]
    public function itExtractsLinksAndDispatchesForEachPost(): void
    {
        $source = 'https://source.com/blog/post/';
        $target = 'https://target.com/page';

        $this->parser->method('parse')->willReturn([new Post($source, 'My Post')]);
        $this->linkExtractor->method('extract')->with($source)->willReturn([$target]);

        $this->dispatcher->expects($this->once())->method('dispatch')->with($source, $target);

        $this->runner()->run();
    }

    #[Test]
    public function itSkipsPostsWithNoOutgoingLinks(): void
    {
        $this->parser->method('parse')->willReturn([new Post('https://source.com/post/', 'Post')]);
        $this->linkExtractor->method('extract')->willReturn([]);

        $this->dispatcher->expects($this->never())->method('dispatch');

        $this->runner()->run();
    }

    #[Test]
    public function itFiltersPostsOlderThanLookbackWindow(): void
    {
        $recentPost = new Post('https://source.com/recent/', 'Recent', new DateTimeImmutable('-5 days'));
        $oldPost    = new Post('https://source.com/old/', 'Old', new DateTimeImmutable('-60 days'));

        $this->parser->method('parse')->willReturn([$recentPost, $oldPost]);
        $this->linkExtractor->method('extract')->willReturn(['https://target.com/page']);

        // Only the recent post should be processed
        $this->dispatcher->expects($this->once())->method('dispatch')
             ->with('https://source.com/recent/', $this->anything());

        $this->runner(lookbackDays: 30)->run();
    }

    #[Test]
    public function itAlwaysProcessesPostsWithNullPublishedAt(): void
    {
        $postWithNoDate = new Post('https://source.com/post/', 'No Date', null);

        $this->parser->method('parse')->willReturn([$postWithNoDate]);
        $this->linkExtractor->method('extract')->willReturn(['https://target.com/page']);

        $this->dispatcher->expects($this->once())->method('dispatch');

        $this->runner(lookbackDays: 30)->run();
    }

    #[Test]
    public function itProcessesAllPostsWhenLookbackDaysIsNull(): void
    {
        $posts = [
            new Post('https://source.com/old/', 'Very Old Post', new DateTimeImmutable('-500 days')),
            new Post('https://source.com/new/', 'New Post', new DateTimeImmutable('-1 day')),
        ];

        $this->parser->method('parse')->willReturn($posts);
        $this->linkExtractor->method('extract')->willReturn(['https://target.com/page']);

        $this->dispatcher->expects($this->exactly(2))->method('dispatch');

        $this->runner(lookbackDays: null)->run();
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function runner(?int $lookbackDays = null): WebmentionRunner
    {
        return new WebmentionRunner(
            new Config(
                feedUrl:      'https://source.com/index.xml',
                stateFile:    sys_get_temp_dir() . '/test-state.json',
                lookbackDays: $lookbackDays,
            ),
            $this->parser,
            $this->linkExtractor,
            $this->dispatcher,
            $this->logger,
        );
    }
}
