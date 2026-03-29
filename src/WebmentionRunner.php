<?php

declare(strict_types=1);

namespace WebmentionSender;

use DateTimeImmutable;
use WebmentionSender\Contract\StateInterface;
use WebmentionSender\Exception\FeedParseException;
use WebmentionSender\Exception\HttpException;
use WebmentionSender\Exception\StateException;

class WebmentionRunner
{
    public function __construct(
        private readonly Config $config,
        private readonly FeedParser $parser,
        private readonly LinkExtractor $linkExtractor,
        private readonly WebmentionDispatcher $dispatcher,
        private readonly StateInterface $state,
        private readonly Logger $logger,
    ) {}

    /**
     * @throws FeedParseException
     * @throws HttpException
     */
    public function run(): void
    {
        if ($this->config->dryRun) {
            $this->logger->info('Dry-run mode enabled — no webmentions will be sent.');
        }

        $this->logger->info('Fetching feed: ' . $this->config->feedUrl);

        $posts = $this->parser->parse($this->config->feedUrl);

        if ($this->config->lookbackDays !== null) {
            $cutoff = new DateTimeImmutable(sprintf('-%d days', $this->config->lookbackDays));
            $posts  = array_values(array_filter(
                $posts,
                fn(Post $post) => $post->publishedAt === null || $post->publishedAt >= $cutoff,
            ));
        }

        $this->logger->info(sprintf('Processing %d post(s).', count($posts)));

        foreach ($posts as $post) {
            $this->processPost($post);
        }

        $this->logger->info('Done.');
    }

    private function processPost(Post $post): void
    {
        $this->logger->debug(sprintf('Processing: %s', $post->title));

        $links = $this->linkExtractor->extract($post->url);

        if ($links === []) {
            $this->logger->debug('  No outgoing links found.');
            return;
        }

        foreach ($links as $target) {
            $this->dispatcher->dispatch($post->url, $target);
        }
    }
}
