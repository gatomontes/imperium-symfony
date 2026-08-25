<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Conscription\DelegateMissionOperationalTransitionCoordinator;
use App\Imperium\Runtime\Conscription\OperationalTransitionFaultInjector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DelegateMissionOperationalTransitionCoordinatorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-operational-transition-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public static function interruptionPoints(): iterable
    {
        foreach ([
            'BEFORE_QUALIFICATION_INDEXED',
            'QUALIFICATION_INDEXED',
            'BEFORE_ASSEMBLY_INDEXED',
            'ASSEMBLY_INDEXED',
            'BEFORE_BINDING_INDEXED',
            'BINDING_INDEXED',
        ] as $checkpoint) {
            yield $checkpoint => [$checkpoint];
        }
    }

    #[DataProvider('interruptionPoints')]
    public function testEveryInterruptedCheckpointResumesForward(string $checkpoint): void
    {
        $qualification = $this->qualification();
        $assembly = $this->assembly($qualification);
        $binding = $this->binding($assembly);
        $fault = new class($checkpoint) implements OperationalTransitionFaultInjector {
            public function __construct(private string $checkpoint)
            {
            }

            public function at(string $checkpoint): void
            {
                if ($this->checkpoint === $checkpoint) {
                    throw new \RuntimeException('INJECTED_OPERATIONAL_TRANSITION_FAILURE');
                }
            }
        };
        $faulting = new DelegateMissionOperationalTransitionCoordinator($this->root, faults: $fault);
        $clean = new DelegateMissionOperationalTransitionCoordinator($this->root);

        foreach ([
            ['recordQualification', $qualification, 'QUALIFICATION'],
            ['recordAssembly', $assembly, 'ASSEMBLY'],
            ['recordBinding', $binding, 'BINDING'],
        ] as [$method, $record, $stage]) {
            if (str_contains($checkpoint, $stage)) {
                try {
                    $faulting->{$method}($record);
                    self::fail('The selected checkpoint must interrupt the first attempt.');
                } catch (\RuntimeException $exception) {
                    self::assertSame('INJECTED_OPERATIONAL_TRANSITION_FAILURE', $exception->getMessage());
                }
            }
            self::assertSame($record, $clean->{$method}($record));
        }

        $codex = json_decode((string) file_get_contents($this->root.'/var/imperium/codex-imperii.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(3, $codex['generation']);
        self::assertSame('DELEGATE_MISSION_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION', $codex['current_checkpoint']);
        self::assertSame([
            $qualification['qualification_id'],
            $assembly['assembly_id'],
            $binding['binding_id'],
        ], array_column($codex['folia'], 'folium_id'));
    }

    public function testChangedPredecessorFailsStopped(): void
    {
        $qualification = $this->qualification();
        $assembly = $this->assembly($qualification);
        $service = new DelegateMissionOperationalTransitionCoordinator($this->root);
        $service->recordQualification($qualification);
        $assembly['source_qualification']['digest'] = str_repeat('f', 64);
        $assembly = $this->seal($assembly);

        $this->expectExceptionMessage('CDI123_OPERATIONAL_PREDECESSOR_INVALID');
        $service->recordAssembly($assembly);
    }

    private function qualification(): array
    {
        return $this->seal([
            'schema' => 'imperium.conscription-delegate-mission-operational-profile-qualification/v1',
            'qualification_id' => 'delegate-mission-operational-profile-qualification-'.str_repeat('a', 20),
            'instance_id' => 'imperium-test',
            'status' => 'DELEGATE_MISSION_PROFILE_OPERATIONALLY_QUALIFIED_PENDING_MANIFESTATION_ASSEMBLY',
        ]);
    }

    private function assembly(array $qualification): array
    {
        return $this->seal([
            'schema' => 'imperium.conscription-delegate-mission-operational-manifestation-assembly/v1',
            'assembly_id' => 'delegate-mission-operational-manifestation-assembly-'.str_repeat('b', 20),
            'instance_id' => 'imperium-test',
            'source_qualification' => ['id' => $qualification['qualification_id'], 'digest' => $qualification['record_digest']],
            'status' => 'DELEGATE_MISSION_OPERATIONAL_MANIFESTATION_ASSEMBLED_PENDING_MISSION_SEAT_BINDING',
        ]);
    }

    private function binding(array $assembly): array
    {
        return $this->seal([
            'schema' => 'imperium.delegate-mission-operational-manifestation-seat-binding/v1',
            'binding_id' => 'delegate-mission-operational-seat-binding-'.str_repeat('c', 20),
            'instance_id' => 'imperium-test',
            'source_assembly' => ['id' => $assembly['assembly_id'], 'digest' => $assembly['record_digest']],
            'status' => 'DELEGATE_MISSION_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION',
        ]);
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
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
