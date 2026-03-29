<?php

declare(strict_types=1);

namespace WebmentionSender\Contract;

use WebmentionSender\Exception\StateException;

interface StateInterface
{
    /**
     * Verifies that the state can be persisted by performing a real write.
     * Call this before sending any webmentions so that a permission problem
     * is detected upfront rather than after mentions have been dispatched.
     *
     * @throws StateException
     */
    public function assertWritable(): void;

    public function hasBeenSent(string $source, string $target): bool;

    /**
     * @throws StateException
     */
    public function markAsSent(string $source, string $target): void;
}
