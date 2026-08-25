<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Garrison\DelegateMissionTerminalTransitionCoordinator;
use App\Imperium\Runtime\Garrison\TerminalTransitionFaultInjector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DelegateMissionTerminalTransitionCoordinatorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-terminal-transition-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public static function checkpoints(): iterable
    {
        foreach (['PREPARED', 'CUSTODY_RESTORED', 'BINDING_RETIRED', 'TERMINAL_RECORDED', 'COMPLETE'] as $checkpoint) {
            yield $checkpoint => [$checkpoint];
        }
    }

    #[DataProvider('checkpoints')]
    public function testEveryInterruptedCheckpointResumesForwardToOneTerminalState(string $checkpoint): void
    {
        $priorCustody = $this->writeState('var/imperium/offices/garrison/custody/custody-123.json', [
            'custody_id' => 'custody-123',
            'custody_state' => 'DELEGATE_MISSION_DEPLOYED_BOUND',
            'available' => false,
        ]);
        $restoredCustody = $priorCustody;
        unset($restoredCustody['record_digest']);
        $restoredCustody['custody_state'] = 'ADMITTED_HELD';
        $restoredCustody['available'] = true;
        $restoredCustody = $this->seal($restoredCustody);
        $priorBinding = $this->writeState('var/imperium/mission/occupancy/binding-123.json', [
            'binding_id' => 'binding-123',
            'status' => 'DELEGATE_MISSION_DEPLOYED_BOUND',
            'seat_bound' => true,
        ]);
        $retiredBinding = $priorBinding;
        unset($retiredBinding['record_digest']);
        $retiredBinding['status'] = 'DELEGATE_MISSION_MANIFESTATION_RETURNED_UNBOUND_RETIRED';
        $retiredBinding['seat_bound'] = false;
        $retiredBinding = $this->seal($retiredBinding);
        $terminal = ['terminal_id' => 'terminal-123', 'status' => 'RETURNED_RETIRED_TERMINAL'];
        $fault = new class($checkpoint) implements TerminalTransitionFaultInjector {
            public function __construct(private string $checkpoint)
            {
            }

            public function after(string $checkpoint): void
            {
                if ($checkpoint === $this->checkpoint) {
                    throw new \RuntimeException('INJECTED_TERMINAL_TRANSITION_FAILURE');
                }
            }
        };

        try {
            (new DelegateMissionTerminalTransitionCoordinator($this->root, faults: $fault))->run(
                'authorization-123',
                'terminal-123',
                $terminal,
                $priorCustody,
                $restoredCustody,
                $priorBinding,
                $retiredBinding,
            );
            self::fail('The selected checkpoint must interrupt the first attempt.');
        } catch (\RuntimeException $exception) {
            self::assertSame('INJECTED_TERMINAL_TRANSITION_FAILURE', $exception->getMessage());
        }

        $record = (new DelegateMissionTerminalTransitionCoordinator($this->root))
            ->resumeForAuthorization('authorization-123');

        self::assertSame('RETURNED_RETIRED_TERMINAL', $record['status']);
        self::assertSame($restoredCustody, $this->read('var/imperium/offices/garrison/custody/custody-123.json'));
        self::assertSame($retiredBinding, $this->read('var/imperium/mission/occupancy/binding-123.json'));
        self::assertSame('COMPLETE', $this->read('var/imperium/runtime/delegate-mission-terminal-transitions/terminal-123.json')['checkpoint']);
        self::assertCount(1, glob($this->root.'/var/imperium/offices/garrison/delegate-mission-terminal-returns/*.json') ?: []);
    }

    public function testChangedAuthoritativeInputCannotReplayExistingTransition(): void
    {
        $priorCustody = $this->writeState('var/imperium/offices/garrison/custody/custody-123.json', ['custody_id' => 'custody-123', 'custody_state' => 'DEPLOYED', 'available' => false]);
        $restoredCustody = $this->seal(['custody_id' => 'custody-123', 'custody_state' => 'ADMITTED_HELD', 'available' => true]);
        $priorBinding = $this->writeState('var/imperium/mission/occupancy/binding-123.json', ['binding_id' => 'binding-123', 'status' => 'BOUND', 'seat_bound' => true]);
        $retiredBinding = $this->seal(['binding_id' => 'binding-123', 'status' => 'RETIRED', 'seat_bound' => false]);
        $terminal = ['terminal_id' => 'terminal-123', 'status' => 'TERMINAL'];
        $service = new DelegateMissionTerminalTransitionCoordinator($this->root);
        $service->run('authorization-123', 'terminal-123', $terminal, $priorCustody, $restoredCustody, $priorBinding, $retiredBinding);

        $terminal['status'] = 'CONFLICTING_TERMINAL';
        $this->expectExceptionMessage('GA309_DELEGATE_TERMINAL_RETURN_CONFLICT');
        $service->run('authorization-123', 'terminal-123', $terminal, $priorCustody, $restoredCustody, $priorBinding, $retiredBinding);
    }

    private function writeState(string $path, array $record): array
    {
        $record = $this->seal($record);
        $absolute = $this->root.'/'.$path;
        if (!is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0770, true);
        }
        file_put_contents($absolute, json_encode($record, JSON_THROW_ON_ERROR));

        return $record;
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function read(string $path): array
    {
        return json_decode((string) file_get_contents($this->root.'/'.$path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
