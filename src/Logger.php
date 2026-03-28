<?php

declare(strict_types=1);

namespace WebmentionSender;

class Logger
{
    public function __construct(private readonly bool $verbose) {}

    public function info(string $message): void
    {
        echo '[' . date('c') . '] [INFO]  ' . $message . "\n";
    }

    public function debug(string $message): void
    {
        if ($this->verbose) {
            echo '[' . date('c') . '] [DEBUG] ' . $message . "\n";
        }
    }

    public function error(string $message): void
    {
        fwrite(STDERR, '[' . date('c') . '] [ERROR] ' . $message . "\n");
    }
}
