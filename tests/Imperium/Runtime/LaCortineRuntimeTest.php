<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\CredentialCapability;
use App\Imperium\Runtime\LaCortine\IronGate;
use App\Imperium\Runtime\LaCortine\Lazaretto;
use App\Imperium\Runtime\LaCortine\OutboundExecutionMode;
use App\Imperium\Runtime\LaCortine\OutboundRequest;
use App\Imperium\Runtime\LaCortine\RawExternalPayload;
use App\Imperium\Runtime\LaCortine\SortieLifecycle;
use PHPUnit\Framework\TestCase;

final class LaCortineRuntimeTest extends TestCase
{
    public function testDeterministicExecutionCreatesNoSortie(): void
    {
        $now = new \DateTimeImmutable('2026-08-10T10:00:00-04:00');
        $dispatch = (new IronGate())->dispatch($this->request(OutboundExecutionMode::Deterministic, $now), $now);

        self::assertSame(OutboundExecutionMode::Deterministic, $dispatch->mode);
        self::assertNull($dispatch->sortie);
    }

    public function testExternalCognitionCreatesDisposableLeastAuthorityManifest(): void
    {
        $now = new \DateTimeImmutable('2026-08-10T10:00:00-04:00');
        $request = $this->request(OutboundExecutionMode::Sortie, $now);
        $dispatch = (new IronGate())->dispatch($request, $now);

        self::assertNotNull($dispatch->sortie);
        self::assertSame($request->commissionId, $dispatch->sortie->commissionId);
        self::assertSame($request->authorizationId, $dispatch->sortie->authorizationId);
        self::assertSame($request->toolIds, $dispatch->sortie->toolIds);
        self::assertSame($request->capabilityIds, $dispatch->sortie->capabilityIds);
    }

    public function testSortieCannotBeReusedAfterRetirement(): void
    {
        $now = new \DateTimeImmutable('2026-08-10T10:00:00-04:00');
        $manifest = (new IronGate())->dispatch($this->request(OutboundExecutionMode::Sortie, $now), $now)->sortie;
        self::assertNotNull($manifest);

        $lifecycle = new SortieLifecycle();
        $lifecycle->register($manifest);
        $lifecycle->deploy($manifest, $now);
        $lifecycle->markReturned($manifest);
        $lifecycle->retire($manifest);
        self::assertSame('retired', $lifecycle->state($manifest));

        $this->expectException(\RuntimeException::class);
        $lifecycle->deploy($manifest, $now);
    }

    public function testLazarettoPreservesExactSortieLineage(): void
    {
        $now = new \DateTimeImmutable('2026-08-10T10:00:00-04:00');
        $request = $this->request(OutboundExecutionMode::Sortie, $now);
        $dispatch = (new IronGate())->dispatch($request, $now);
        self::assertNotNull($dispatch->sortie);

        $content = '{"finding":"external evidence"}';
        $payload = new RawExternalPayload(
            'payload-1',
            $dispatch->executionId,
            $request->commissionId,
            $request->authorizationId,
            $dispatch->sortie->sortieId,
            $dispatch->sortie->manifestationId,
            $content,
            hash('sha256', $content),
            ['source-1'],
            $request->toolIds,
            $request->capabilityIds,
            $now,
            $now->modify('+1 second'),
        );

        $artifact = (new Lazaretto())->admit($payload, $dispatch, $now->modify('+2 seconds'));

        self::assertSame($payload->payloadId, $artifact->rawPayloadId);
        self::assertSame($payload->contentDigest, $artifact->rawPayloadDigest);
        self::assertSame($dispatch->sortie->sortieId, $artifact->provenance['sortie_id']);
        self::assertSame($dispatch->sortie->manifestationId, $artifact->provenance['manifestation_id']);
    }

    public function testLazarettoRejectsMismatchedExecutionLineage(): void
    {
        $now = new \DateTimeImmutable('2026-08-10T10:00:00-04:00');
        $request = $this->request(OutboundExecutionMode::Deterministic, $now);
        $dispatch = (new IronGate())->dispatch($request, $now);
        $content = 'raw';
        $payload = new RawExternalPayload(
            'payload-2',
            'wrong-execution',
            $request->commissionId,
            $request->authorizationId,
            null,
            null,
            $content,
            hash('sha256', $content),
            ['provider-1'],
            [],
            $request->capabilityIds,
            $now,
            $now,
        );

        $this->expectException(\RuntimeException::class);
        (new Lazaretto())->admit($payload, $dispatch, $now);
    }

    public function testCredentialCapabilityContainsNoSecretMaterial(): void
    {
        $capability = new CredentialCapability(
            'cap-1',
            'clavium://email/provider-account',
            'commission-1',
            'email.send',
            new \DateTimeImmutable('+1 minute'),
        );

        $metadata = $capability->metadata();
        self::assertArrayNotHasKey('secret', $metadata);
        self::assertArrayNotHasKey('token', $metadata);
        self::assertSame('clavium://email/provider-account', $metadata['credential_ref']);
    }

    private function request(OutboundExecutionMode $mode, \DateTimeImmutable $now): OutboundRequest
    {
        return new OutboundRequest(
            'request-1',
            'authorization-1',
            str_repeat('a', 64),
            'commission-1',
            'external.lookup',
            'Collect exact authorized external material',
            $mode,
            ['https://example.test'],
            ['http.get'],
            ['capability-1'],
            str_repeat('b', 64),
            'return-contract/v1',
            $now->modify('+5 minutes'),
        );
    }
}
