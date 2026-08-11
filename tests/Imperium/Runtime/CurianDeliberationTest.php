<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Curia\CurianDeliberation;
use App\Imperium\Runtime\Curia\ProceedingStore;
use App\Imperium\Runtime\Curia\SeneschalCognitionGateway;
use PHPUnit\Framework\TestCase;

final class CurianDeliberationTest extends TestCase
{
    public function testAppendsAndIdempotentlyReplaysImmutableTurn(): void
    {
        $root = sys_get_temp_dir().DIRECTORY_SEPARATOR.'imperium-deliberation-'.bin2hex(random_bytes(6));
        mkdir($root, 0700, true);
        $state = new StateStore($root);
        $state->locked(static fn () => $state->write([
            'state' => 'CURIA_READY',
            'binding' => ['instance_id' => 'imperium-test', 'manifest_id' => str_repeat('a', 64)],
        ]));
        $store = new ProceedingStore($root);
        $store->persist([
            'proceeding_id' => 'proceeding-test-0001',
            'instance_id' => 'imperium-test',
            'manifest_id' => str_repeat('a', 64),
            'imperator_request' => ['content' => 'Prepare a cybersecurity assessment mission.'],
        ]);
        $calls = (object) ['count' => 0];
        $service = new CurianDeliberation($state, $store, $this->seneschal($calls));

        try {
            $first = $service->respond('proceeding-test-0001', 'Assess the public web application first.', 'response-test-0001');
            $replay = $service->respond('proceeding-test-0001', 'Assess the public web application first.', 'response-test-0001');

            self::assertSame($first, $replay);
            self::assertSame(1, $first['sequence']);
            self::assertSame(1, $calls->count);
            self::assertSame('CLARIFICATION_REQUIRED', $first['seneschal']['disposition']);
            self::assertSame('RESPONSE_RECORDED', $first['secretary']['disposition']);
            self::assertCount(1, $store->turns('proceeding-test-0001'));
        } finally {
            $this->removeTree($root);
        }
    }

    private function seneschal(object $calls): SeneschalCognitionGateway
    {
        return new class($calls) implements SeneschalCognitionGateway {
            public function __construct(private object $calls)
            {
            }

            public function decide(string $request, array $context): array
            {
                throw new \LogicException('Deliberation test does not open proceedings.');
            }

            public function advance(array $proceeding, array $priorTurns, string $imperatorResponse, array $context): array
            {
                ++$this->calls->count;

                return [
                    'disposition' => 'CLARIFICATION_REQUIRED',
                    'decision' => 'The authorized assessment boundary remains incomplete.',
                    'question' => 'Which hostname is in scope?',
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
