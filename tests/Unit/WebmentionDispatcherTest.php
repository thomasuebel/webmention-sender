<?php

declare(strict_types=1);

namespace WebmentionSender\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use WebmentionSender\Contract\HttpClientInterface;
use WebmentionSender\Contract\StateInterface;
use WebmentionSender\EndpointDiscoverer;
use WebmentionSender\Logger;
use WebmentionSender\WebmentionDispatcher;

final class WebmentionDispatcherTest extends TestCase
{
    private HttpClientInterface&MockObject $http;
    private StateInterface&MockObject $state;
    private EndpointDiscoverer&MockObject $discoverer;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->http       = $this->createMock(HttpClientInterface::class);
        $this->state      = $this->createMock(StateInterface::class);
        $this->discoverer = $this->createMock(EndpointDiscoverer::class);
        $this->logger     = new Logger(verbose: false);
    }

    #[Test]
    public function itSendsWebmentionAndUpdatesState(): void
    {
        $source   = 'https://source.com/blog/post/';
        $target   = 'https://target.com/page';
        $endpoint = 'https://target.com/webmention';

        $this->state->method('hasBeenSent')->willReturn(false);
        $this->discoverer->method('discover')->with($target)->willReturn($endpoint);
        $this->http->method('post')
                   ->with($endpoint, ['source' => $source, 'target' => $target])
                   ->willReturn(['status' => 202]);

        $this->state->expects($this->once())->method('markAsSent')->with($source, $target);

        $this->dispatcher()->dispatch($source, $target);
    }

    #[Test]
    public function itSkipsAlreadySentLinks(): void
    {
        $this->state->method('hasBeenSent')->willReturn(true);

        $this->discoverer->expects($this->never())->method('discover');
        $this->http->expects($this->never())->method('post');

        $this->dispatcher()->dispatch('https://source.com/post/', 'https://target.com/page');
    }

    #[Test]
    public function itSkipsLinksWithNoEndpoint(): void
    {
        $this->state->method('hasBeenSent')->willReturn(false);
        $this->discoverer->method('discover')->willReturn(null);

        $this->http->expects($this->never())->method('post');
        $this->state->expects($this->never())->method('markAsSent');

        $this->dispatcher()->dispatch('https://source.com/post/', 'https://target.com/page');
    }

    #[Test]
    public function itDoesNotSendOrUpdateStateInDryRunMode(): void
    {
        $this->state->method('hasBeenSent')->willReturn(false);
        $this->discoverer->method('discover')->willReturn('https://target.com/webmention');

        $this->http->expects($this->never())->method('post');
        $this->state->expects($this->never())->method('markAsSent');

        $this->dispatcher(dryRun: true)->dispatch('https://source.com/post/', 'https://target.com/page');
    }

    #[Test]
    public function itDoesNotUpdateStateOnFailedSend(): void
    {
        $this->state->method('hasBeenSent')->willReturn(false);
        $this->discoverer->method('discover')->willReturn('https://target.com/webmention');
        $this->http->method('post')->willReturn(['status' => 500]);

        $this->state->expects($this->never())->method('markAsSent');

        $this->dispatcher()->dispatch('https://source.com/post/', 'https://target.com/page');
    }

    #[Test]
    public function itDoesNotUpdateStateOnAcceptedResponse(): void
    {
        $this->state->method('hasBeenSent')->willReturn(false);
        $this->discoverer->method('discover')->willReturn('https://target.com/webmention');
        $this->http->method('post')->willReturn(['status' => 201]);

        $this->state->expects($this->once())->method('markAsSent');

        $this->dispatcher()->dispatch('https://source.com/post/', 'https://target.com/page');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function dispatcher(bool $dryRun = false): WebmentionDispatcher
    {
        return new WebmentionDispatcher(
            $this->http,
            $this->state,
            $this->discoverer,
            $this->logger,
            $dryRun,
        );
    }
}
