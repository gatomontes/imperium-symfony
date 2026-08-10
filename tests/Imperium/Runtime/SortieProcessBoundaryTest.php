<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\IronGate;
use App\Imperium\Runtime\LaCortine\Lazaretto;
use App\Imperium\Runtime\LaCortine\OutboundExecutionMode;
use App\Imperium\Runtime\LaCortine\OutboundRequest;
use App\Imperium\Runtime\LaCortine\SortieLifecycle;
use App\Imperium\Runtime\LaCortine\SortieManifest;
use App\Imperium\Runtime\Sortie\OneShotSortieRunner;
use App\Imperium\Runtime\Sortie\SortieCognitionGateway;
use App\Imperium\Runtime\Sortie\SortieCognitionResult;
use App\Imperium\Runtime\Sortie\SortieManifestCodec;
use App\Imperium\Runtime\Sortie\UnavailableSortieCognitionGateway;
use PHPUnit\Framework\TestCase;

final class SortieProcessBoundaryTest extends TestCase
{
    public function testOneShotSortieReturnsPayloadBoundToExactIronGateDispatchAndLazarettoAdmitsIt(): void
    {
        $now = new \DateTimeImmutable('2026-08-10T13:00:00-04:00');
        $request = $this->request($now);
        $dispatch = (new IronGate())->dispatch($request, $now);
        self::assertNotNull($dispatch->sortie);

        $codec = new SortieManifestCodec();
        $envelope = $codec->seal($dispatch->sortie);
        $decoded = $codec->decode($codec->encode($envelope), $envelope->manifestDigest);

        $gateway = new class($now) implements SortieCognitionGateway {
            public function __construct(private readonly \DateTimeImmutable $now) {}

            public function execute(SortieManifest $manifest): SortieCognitionResult
            {
                return new SortieCognitionResult(
                    '{"finding":"bounded external cognition"}',
                    ['https://example.test/source'],
                    $manifest->toolIds,
                    $manifest->capabilityIds,
                    $this->now->modify('+1 second'),
                );
            }
        };

        $lifecycle = new SortieLifecycle();
        $payload = (new OneShotSortieRunner($gateway, $lifecycle))->run($decoded, $now);

        self::assertSame($dispatch->executionId, $payload->executionId);
        self::assertSame($dispatch->sortie->sortieId, $payload->sortieId);
        self::assertSame('retired', $lifecycle->state($dispatch->sortie));

        $artifact = (new Lazaretto())->admit($payload, $dispatch, $now->modify('+3 seconds'));
        self::assertSame($payload->payloadId, $artifact->rawPayloadId);
        self::assertSame($dispatch->executionId, $artifact->provenance['execution_id']);
    }

    public function testManifestTamperingIsRejectedBeforeCognition(): void
    {
        $now = new \DateTimeImmutable('2026-08-10T13:00:00-04:00');
        $dispatch = (new IronGate())->dispatch($this->request($now), $now);
        self::assertNotNull($dispatch->sortie);

        $codec = new SortieManifestCodec();
        $envelope = $codec->seal($dispatch->sortie);
        $encoded = $codec->encode($envelope);
        $tampered = str_replace('Investigate exact external evidence', 'Investigate anything', $encoded);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('SORTIE_MANIFEST_DIGEST_MISMATCH');
        $codec->decode($tampered, $envelope->manifestDigest);
    }

    public function testUnboundCognitionFailsClosedAndSortieStillRetires(): void
    {
        $now = new \DateTimeImmutable('2026-08-10T13:00:00-04:00');
        $dispatch = (new IronGate())->dispatch($this->request($now), $now);
        self::assertNotNull($dispatch->sortie);

        $lifecycle = new SortieLifecycle();
        $runner = new OneShotSortieRunner(new UnavailableSortieCognitionGateway(), $lifecycle);
        $envelope = (new SortieManifestCodec())->seal($dispatch->sortie);

        try {
            $runner->run($envelope, $now);
            self::fail('Unbound sortie cognition must refuse execution.');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('SORTIE_COGNITION_UNAVAILABLE', $exception->getMessage());
        }

        self::assertSame('retired', $lifecycle->state($dispatch->sortie));
    }

    private function request(\DateTimeImmutable $now): OutboundRequest
    {
        return new OutboundRequest(
            'sortie-request-1',
            'authorization-1',
            str_repeat('a', 64),
            'commission-1',
            'external.research',
            'Investigate exact external evidence',
            OutboundExecutionMode::Sortie,
            ['https://example.test'],
            ['web.read'],
            ['capability-web-1'],
            str_repeat('b', 64),
            'research-return/v1',
            $now->modify('+5 minutes'),
        );
    }
}
