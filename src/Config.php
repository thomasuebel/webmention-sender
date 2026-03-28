<?php

declare(strict_types=1);

namespace WebmentionSender;

use InvalidArgumentException;

final class Config
{
    public function __construct(
        /** URL of the RSS feed to process. */
        public readonly string $feedUrl,

        /** Absolute path to the JSON state file for tracking sent webmentions. */
        public readonly string $stateFile,

        /** User-Agent header sent with all outgoing HTTP requests. */
        public readonly string $userAgent = 'WebmentionSender/1.0',

        /** Total transfer timeout in seconds for outgoing HTTP requests. */
        public readonly int $requestTimeout = 10,

        /** Connection timeout in seconds. Should be shorter than requestTimeout. */
        public readonly int $connectTimeout = 5,

        /**
         * When set, only posts published within this many days are processed.
         * Posts with no parseable publish date are always processed.
         */
        public readonly ?int $lookbackDays = null,

        /**
         * When true, discovers endpoints and logs what would be sent
         * but does not actually send any webmentions or update state.
         */
        public readonly bool $dryRun = false,

        /** When true, emits debug-level log lines. */
        public readonly bool $verbose = false,

        /**
         * Secret token required to trigger a run via HTTP (webmention-cron.php).
         * Leave null to disable HTTP access entirely.
         */
        public readonly ?string $cronToken = null,
    ) {
        if ($this->feedUrl === '' || !str_starts_with($this->feedUrl, 'http')) {
            throw new InvalidArgumentException('feedUrl must be a non-empty HTTP or HTTPS URL.');
        }

        if ($this->stateFile === '') {
            throw new InvalidArgumentException('stateFile must not be empty.');
        }

        if ($this->requestTimeout < 1) {
            throw new InvalidArgumentException('requestTimeout must be at least 1 second.');
        }

        if ($this->connectTimeout < 1) {
            throw new InvalidArgumentException('connectTimeout must be at least 1 second.');
        }

        if ($this->lookbackDays !== null && $this->lookbackDays < 1) {
            throw new InvalidArgumentException('lookbackDays must be a positive integer or null.');
        }

        if ($this->cronToken !== null && $this->cronToken === '') {
            throw new InvalidArgumentException('cronToken must not be an empty string; set it to null to disable HTTP access.');
        }
    }
}
