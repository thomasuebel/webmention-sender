<?php

declare(strict_types=1);

namespace WebmentionSender;

use WebmentionSender\Contract\HttpClientInterface;
use WebmentionSender\Contract\StateInterface;
use WebmentionSender\Exception\HttpException;
use WebmentionSender\Exception\StateException;

/**
 * Handles the per-link dispatch cycle: check state, discover endpoint,
 * send webmention, update state.
 */
class WebmentionDispatcher
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly StateInterface $state,
        private readonly EndpointDiscoverer $discoverer,
        private readonly Logger $logger,
        private readonly bool $dryRun = false,
    ) {}

    /**
     * Discovers the webmention endpoint for $target and sends a webmention
     * from $source. Skips silently if already sent or no endpoint is found.
     */
    public function dispatch(string $source, string $target): void
    {
        if ($this->state->hasBeenSent($source, $target)) {
            $this->logger->debug(sprintf('  Already sent, skipping: %s', $target));
            return;
        }

        $this->logger->debug(sprintf('  Discovering endpoint: %s', $target));
        $endpoint = $this->discoverer->discover($target);

        if ($endpoint === null) {
            $this->logger->debug(sprintf('  No webmention endpoint found: %s', $target));
            return;
        }

        $this->logger->info(sprintf('  -> %s', $target));
        $this->logger->debug(sprintf('     Endpoint: %s', $endpoint));

        if ($this->dryRun) {
            $this->logger->info(sprintf('     [DRY RUN] Would POST to: %s', $endpoint));
            return;
        }

        try {
            $response = $this->http->post($endpoint, [
                'source' => $source,
                'target' => $target,
            ]);

            if ($response['status'] >= 200 && $response['status'] < 300) {
                $this->logger->info(sprintf('     Sent (HTTP %d).', $response['status']));
                $this->state->markAsSent($source, $target);
            } else {
                $this->logger->error(sprintf(
                    '     Endpoint returned HTTP %d for %s -> %s',
                    $response['status'],
                    $source,
                    $target,
                ));
            }
        } catch (HttpException $e) {
            $this->logger->error('     Request failed: ' . $e->getMessage());
        } catch (StateException $e) {
            $this->logger->error('     Could not persist state: ' . $e->getMessage());
        }
    }
}
