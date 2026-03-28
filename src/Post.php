<?php

declare(strict_types=1);

namespace WebmentionSender;

use DateTimeImmutable;

final class Post
{
    public function __construct(
        public readonly string $url,
        public readonly string $title,
        public readonly ?DateTimeImmutable $publishedAt = null,
    ) {}
}
