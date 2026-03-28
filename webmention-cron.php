<?php

declare(strict_types=1);

/**
 * RSS Webmention Sender — HTTP Cron Entry Point
 *
 * For hosts that only support HTTP-based cron jobs.
 * Copy this file into your webroot and adjust SENDER_DIR if needed.
 *
 * Trigger via cron:
 *   https://your-blog.com/webmention-cron.php?token=<your-secret-token>
 *
 * Or with an Authorization header (token stays out of server logs):
 *   Authorization: <your-secret-token>
 *
 * Set cronToken in config.php to a long random string, e.g.:
 *   php -r "echo bin2hex(random_bytes(32));"
 */

// Path to the webmention-sender directory.
// Default assumes this file sits in your webroot and the sender is one level up.
// Adjust if your directory layout differs.
define('SENDER_DIR', dirname(__DIR__) . '/webmention-sender');

// ─── Sanity check ─────────────────────────────────────────────────────────────

if (!is_dir(SENDER_DIR) || !file_exists(SENDER_DIR . '/src/Config.php')) {
    http_response_code(500);
    error_log('webmention-cron: SENDER_DIR is not set correctly: ' . SENDER_DIR);
    exit;
}

// ─── Autoloader ───────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class): void {
    $prefix = 'WebmentionSender\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $file = SENDER_DIR . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// ─── Config ───────────────────────────────────────────────────────────────────

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

$configFile = SENDER_DIR . '/config.php';

if (!file_exists($configFile)) {
    http_response_code(500);
    error_log('webmention-cron: config.php not found at ' . $configFile);
    exit;
}

try {
    $config = require $configFile;
} catch (\InvalidArgumentException $e) {
    http_response_code(500);
    error_log('webmention-cron: invalid configuration — ' . $e->getMessage());
    exit;
}

if (!$config instanceof Config) {
    http_response_code(500);
    error_log('webmention-cron: config.php must return a Config instance.');
    exit;
}

// ─── Token auth ───────────────────────────────────────────────────────────────

if ($config->cronToken === null) {
    // HTTP access not enabled — refuse silently
    http_response_code(404);
    exit;
}

$provided = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_GET['token'] ?? '');

if (!hash_equals($config->cronToken, $provided)) {
    http_response_code(403);
    exit;
}

// ─── Run ──────────────────────────────────────────────────────────────────────

header('Content-Type: text/plain; charset=utf-8');

$logger     = new Logger($config->verbose);
$http       = new HttpClient($config->userAgent, $config->requestTimeout, $config->connectTimeout);
$state      = new StateManager($config->stateFile);
$discoverer = new EndpointDiscoverer($http);
$dispatcher = new WebmentionDispatcher($http, $state, $discoverer, $logger, $config->dryRun);
$parser     = new FeedParser($http);
$extractor  = new LinkExtractor($http);

try {
    (new WebmentionRunner($config, $parser, $extractor, $dispatcher, $logger))->run();
    http_response_code(200);
} catch (FeedParseException | HttpException $e) {
    $logger->error('Failed to fetch or parse feed: ' . $e->getMessage());
    http_response_code(500);
}
