<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Curia\CurianAudience;
use App\Imperium\Runtime\Curia\CurianCognitionAuthorityService;
use App\Imperium\Runtime\Curia\ProceedingStore;
use App\Imperium\Runtime\Curia\SeneschalCognitionGateway;
use PHPUnit\Framework\TestCase;

final class CurianAudienceTest extends TestCase
{
    public function testFreshAudienceRefusesBeforeCognitionEvenWhenCuriaReady(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imperium-curia-'.bin2hex(random_bytes(6));
        mkdir($root, 0700, true);
        $bootstrap = new StateStore($root);
        $this->seedBootstrap($bootstrap, $this->readyState());
        $calls = (object) ['count' => 0];
        $audience = new CurianAudience($bootstrap, new ProceedingStore($root), $this->seneschal($calls), new CurianCognitionAuthorityService($root));

        try {
            $this->expectExceptionMessage('CMF112_NEW_REQUEST_USES_CITADEL_INTAKE');
            $audience->open('Prepare a cybersecurity assessment mission.');
        } finally {
            self::assertSame(0, $calls->count);
            $this->removeTree($root);
        }
    }

    public function testRefusesAudienceBeforeCuriaReady(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imperium-curia-'.bin2hex(random_bytes(6));
        mkdir($root, 0700, true);
        $bootstrap = new StateStore($root);
        $this->seedBootstrap($bootstrap, ['state' => 'ROUTES_VERIFIED']);
        $calls = (object) ['count' => 0];
        $audience = new CurianAudience($bootstrap, new ProceedingStore($root), $this->seneschal($calls), new CurianCognitionAuthorityService($root));

        try {
            $this->expectExceptionMessage('C01_CURIA_NOT_READY');
            $audience->open('Prepare a mission.');
        } finally {
            self::assertSame(0, $calls->count);
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

    private function seedBootstrap(StateStore $store, array $state): void
    {
        $store->locked(static function () use ($store, $state): void {
            $store->write($state);
        });
    }

    private function seneschal(object $calls): SeneschalCognitionGateway
    {
        return new class($calls) implements SeneschalCognitionGateway {
            public function __construct(private object $calls)
            {
            }

            public function decide(string $authorityId, string $request, array $context): array
            {
                ++$this->calls->count;
                TestCase::assertMatchesRegularExpression('/^curian-cognition-[a-f0-9]{20}$/', $authorityId);
                return [
                    'disposition' => 'ADMITTED_FOR_PLANNING',
                    'decision' => 'Develop a bounded Mission Plan for Imperator review.',
                    'question' => null,
                    'resource_demands' => [],
                    'authorization_required' => false,
                    'mission_plan' => null,
                ];
            }

            public function advance(string $authorityId, array $proceeding, array $priorTurns, string $imperatorResponse, array $context): array
            {
                throw new \LogicException('Audience test does not advance proceedings.');
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
