<?php

declare(strict_types=1);

namespace WebmentionSender\Contract;

use WebmentionSender\Exception\HttpException;

interface HttpClientInterface
{
    /**
     * @return array{status: int, headers: array<string, string>, body: string}
     * @throws HttpException
     */
    public function get(string $url): array;

    /**
     * @param array<string, string> $fields
     * @return array{status: int}
     * @throws HttpException
     */
    public function post(string $url, array $fields): array;
}
