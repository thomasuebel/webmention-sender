<?php

declare(strict_types=1);

namespace WebmentionSender;

use WebmentionSender\Contract\StateInterface;
use WebmentionSender\Exception\StateException;

class StateManager implements StateInterface
{
    /**
     * Nested structure: source URL → target URL → ISO 8601 sent_at timestamp.
     *
     * @var array<string, array<string, string>>
     */
    private array $state = [];

    /**
     * @throws StateException
     */
    public function __construct(private readonly string $stateFile)
    {
        $this->load();
    }

    public function hasBeenSent(string $source, string $target): bool
    {
        return isset($this->state[$source][$target]);
    }

    /**
     * @throws StateException
     */
    public function markAsSent(string $source, string $target): void
    {
        $this->state[$source][$target] = date('c');
        $this->persist();
    }

    /**
     * @throws StateException
     */
    private function load(): void
    {
        if (!file_exists($this->stateFile)) {
            return;
        }

        $contents = file_get_contents($this->stateFile);

        if ($contents === false) {
            throw new StateException(sprintf('Could not read state file: %s', $this->stateFile));
        }

        $decoded = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new StateException(sprintf('State file contains invalid JSON: %s', $this->stateFile));
        }

        $this->state = $decoded ?? [];
    }

    /**
     * @throws StateException
     */
    private function persist(): void
    {
        $result = file_put_contents(
            $this->stateFile,
            json_encode($this->state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        if ($result === false) {
            throw new StateException(sprintf('Could not write state file: %s', $this->stateFile));
        }
    }
}
