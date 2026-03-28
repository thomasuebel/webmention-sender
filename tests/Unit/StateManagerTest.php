<?php

declare(strict_types=1);

namespace WebmentionSender\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use WebmentionSender\Exception\StateException;
use WebmentionSender\StateManager;

final class StateManagerTest extends TestCase
{
    private string $stateFile;

    protected function setUp(): void
    {
        $this->stateFile = sys_get_temp_dir() . '/webmention-test-' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->stateFile)) {
            unlink($this->stateFile);
        }
    }

    #[Test]
    public function itReturnsFalseForUnsentPairs(): void
    {
        $manager = new StateManager($this->stateFile);

        $this->assertFalse($manager->hasBeenSent('https://source.com/post', 'https://target.com/page'));
    }

    #[Test]
    public function itReturnsTrueAfterMarkingAsSent(): void
    {
        $manager = new StateManager($this->stateFile);
        $manager->markAsSent('https://source.com/post', 'https://target.com/page');

        $this->assertTrue($manager->hasBeenSent('https://source.com/post', 'https://target.com/page'));
    }

    #[Test]
    public function itPersistsStateAcrossInstances(): void
    {
        $manager = new StateManager($this->stateFile);
        $manager->markAsSent('https://source.com/post', 'https://target.com/page');

        $reloaded = new StateManager($this->stateFile);

        $this->assertTrue($reloaded->hasBeenSent('https://source.com/post', 'https://target.com/page'));
    }

    #[Test]
    public function itTreatsSourceTargetPairsIndependently(): void
    {
        $manager = new StateManager($this->stateFile);
        $manager->markAsSent('https://source.com/post', 'https://target-a.com/page');

        $this->assertFalse($manager->hasBeenSent('https://source.com/post', 'https://target-b.com/page'));
    }

    #[Test]
    public function itDoesNotCreateStateFileUntilFirstWrite(): void
    {
        new StateManager($this->stateFile);

        $this->assertFileDoesNotExist($this->stateFile);
    }

    #[Test]
    public function itWritesNestedJsonStructure(): void
    {
        $manager = new StateManager($this->stateFile);
        $manager->markAsSent('https://source.com/post', 'https://target.com/page');

        $state = json_decode(file_get_contents($this->stateFile), true);

        $this->assertArrayHasKey('https://source.com/post', $state);
        $this->assertArrayHasKey('https://target.com/page', $state['https://source.com/post']);
    }

    #[Test]
    public function itThrowsOnInvalidJsonStateFile(): void
    {
        file_put_contents($this->stateFile, 'not valid json {{{');

        $this->expectException(StateException::class);
        $this->expectExceptionMessage('invalid JSON');

        new StateManager($this->stateFile);
    }
}
