<?php

declare(strict_types=1);

/**
 * RSS Webmention Sender — Entry Point
 *
 * Reads an RSS feed, fetches each post page to extract outgoing links, discovers
 * webmention endpoints for those links, and sends webmentions. A state file
 * prevents re-sending on subsequent runs.
 *
 * Usage:  php webmention-sender.php
 * Cron:   0 8 * * * php /path/to/webmention-sender.php >> /path/to/webmention-sender.log 2>&1
 *
 * @license MIT
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(0);
}

spl_autoload_register(function (string $class): void {
    $prefix = 'WebmentionSender\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use WebmentionSender\Config;
use WebmentionSender\EndpointDiscoverer;
use WebmentionSender\Exception\FeedParseException;
use WebmentionSender\Exception\HttpException;
use WebmentionSender\FeedParser;
use WebmentionSender\HttpClient;
use WebmentionSender\LinkExtractor;
use WebmentionSender\Logger;
use WebmentionSender\StateManager;
use WebmentionSender\WebmentionDispatcher;
use WebmentionSender\WebmentionRunner;

// ─── Bootstrap ────────────────────────────────────────────────────────────────

$configFile = __DIR__ . '/config.php';

if (!file_exists($configFile)) {
    fwrite(STDERR, "Error: config.php not found. Copy config.example.php to config.php and fill it in.\n");
    exit(1);
}

try {
    $config = require $configFile;
} catch (\InvalidArgumentException $e) {
    fwrite(STDERR, 'Error: invalid configuration — ' . $e->getMessage() . "\n");
    exit(1);
}

if (!$config instanceof Config) {
    fwrite(STDERR, "Error: config.php must return a Config instance.\n");
    exit(1);
}

$logger     = new Logger($config->verbose);
$http       = new HttpClient($config->userAgent, $config->requestTimeout, $config->connectTimeout);
$state      = new StateManager($config->stateFile);
$discoverer = new EndpointDiscoverer($http);
$dispatcher = new WebmentionDispatcher($http, $state, $discoverer, $logger, $config->dryRun);
$parser     = new FeedParser($http);
$extractor  = new LinkExtractor($http);

try {
    (new WebmentionRunner($config, $parser, $extractor, $dispatcher, $state, $logger))->run();
} catch (FeedParseException | HttpException $e) {
    $logger->error('Failed to fetch or parse feed: ' . $e->getMessage());
    exit(1);
}
