<?php

declare(strict_types=1);

namespace WebmentionSender\Contract;

use WebmentionSender\Exception\StateException;

interface StateInterface
{
    public function hasBeenSent(string $source, string $target): bool;

    /**
     * @throws StateException
     */
    public function markAsSent(string $source, string $target): void;
}
