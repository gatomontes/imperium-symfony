<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Curia\CurianAudience;
use App\Imperium\Runtime\Curia\ProceedingStore;
use App\Imperium\Runtime\Curia\SeneschalCognitionGateway;
use PHPUnit\Framework\TestCase;

final class CurianAudienceTest extends TestCase
{
    public function testOpensAndReplaysDurableProceedingFromReadyCuria(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imperium-curia-'.bin2hex(random_bytes(6));
        mkdir($root, 0700, true);
        $bootstrap = new StateStore($root);
        $bootstrap->write($this->readyState());
        $calls = (object) ['count' => 0];
        $audience = new CurianAudience($bootstrap, new ProceedingStore($root), $this->seneschal($calls));

        try {
            $first = $audience->open('Prepare a cybersecurity assessment mission.');
            $replay = $audience->open('Prepare a cybersecurity assessment mission.');

            self::assertSame($first, $replay);
            self::assertSame(1, $calls->count, 'A durable replay must not invoke Seneschal cognition twice.');
            self::assertSame('ADMITTED_FOR_PLANNING', $first['status']);
            self::assertSame('PROCEEDING_OPENED', $first['chamberlain']['disposition']);
            self::assertSame('REQUEST_RECORDED', $first['secretary']['disposition']);
            self::assertSame('ADMITTED_FOR_PLANNING', $first['seneschal']['disposition']);
            self::assertFalse($first['authorization_required']);
            self::assertFileExists($root.'/var/imperium/curia/proceedings/'.$first['proceeding_id'].'.json');
        } finally {
            $this->removeTree($root);
        }
    }

    public function testRefusesAudienceBeforeCuriaReady(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imperium-curia-'.bin2hex(random_bytes(6));
        mkdir($root, 0700, true);
        $bootstrap = new StateStore($root);
        $bootstrap->write(['state' => 'ROUTES_VERIFIED']);
        $calls = (object) ['count' => 0];
        $audience = new CurianAudience($bootstrap, new ProceedingStore($root), $this->seneschal($calls));

        try {
            $this->expectExceptionMessage('C01_CURIA_NOT_READY');
            $audience->open('Prepare a mission.');
        } finally {
            $this->removeTree($root);
        }
    }

    private function readyState(): array
    {
        $occupants = [];
        foreach (['seneschal', 'chamberlain', 'secretary'] as $role) {
            $occupants[$role] = [
                'manifestation_id' => 'imperium-test.officer.'.$role.'.1',
                'seat' => 'curia.'.$role,
                'occupancy_generation' => 1,
                'status' => 'active',
            ];
        }

        return [
            'state' => 'CURIA_READY',
            'binding' => ['instance_id' => 'imperium-test', 'manifest_id' => str_repeat('a', 64)],
            'events' => [[
                'transition' => 'T10',
                'result' => 'SUCCESS',
                'output' => [
                    'runtime' => [
                        'runtime_id' => 'imperium-test.office.curia',
                        'addressable' => true,
                        'occupants' => $occupants,
                    ],
                ],
            ]],
        ];
    }

    private function seneschal(object $calls): SeneschalCognitionGateway
    {
        return new class($calls) implements SeneschalCognitionGateway {
            public function __construct(private object $calls)
            {
            }

            public function decide(string $request, array $context): array
            {
                ++$this->calls->count;
                return [
                    'disposition' => 'ADMITTED_FOR_PLANNING',
                    'decision' => 'Develop a bounded Mission Plan for Imperator review.',
                    'question' => null,
                    'resource_demands' => [],
                    'authorization_required' => false,
                ];
            }
        };
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $entry) {
            if ('.' === $entry || '..' === $entry) {
                continue;
            }
            $child = $path.DIRECTORY_SEPARATOR.$entry;
            is_dir($child) ? $this->removeTree($child) : @unlink($child);
        }
        @rmdir($path);
    }
}
