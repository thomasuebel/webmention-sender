<?php

declare(strict_types=1);

// This file is loaded by webmention-sender.php after the autoloader is registered.
// Copy it to config.php and fill in your values. config.php is gitignored.

use WebmentionSender\Config;

return new Config(
    feedUrl:        'https://example.com/index.xml',
    stateFile:      __DIR__ . '/webmentions-sent.json',
    userAgent:      'WebmentionSender/1.0 (https://github.com/thomasuebel/webmention-sender)',
    requestTimeout: 10,
    connectTimeout: 5,
    lookbackDays:   30,     // set to null to process all posts on every run
    dryRun:         false,
    verbose:        true,

    // Required for HTTP cron (webmention-cron.php). Generate with:
    // php -r "echo bin2hex(random_bytes(32));"
    // cronToken:   'your-secret-token-here',
);
