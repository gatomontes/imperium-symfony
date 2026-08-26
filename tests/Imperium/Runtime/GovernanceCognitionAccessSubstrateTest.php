<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\GovernanceCognitionInvocationClaimService;
use App\Imperium\Runtime\Clavium\GovernanceCognitionLeaseService;
use App\Imperium\Runtime\Clavium\ProviderInvocationJournalService;
use App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityRegistry;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityResolver;
use App\Imperium\Runtime\Cognition\GovernanceCognitionRequestService;
use App\Imperium\Runtime\Imperator\GovernanceProviderResourceDecisionService;
use PHPUnit\Framework\TestCase;

final class GovernanceCognitionAccessSubstrateTest extends TestCase
{
    private string $root;
    private TestGovernanceAuthorityResolver $resolver;
    private GovernanceCognitionAuthorityRegistry $registry;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-governance-access-'.bin2hex(random_bytes(5));
        $this->resolver = new TestGovernanceAuthorityResolver($this->authority());
        $this->registry = new GovernanceCognitionAuthorityRegistry([$this->resolver]);
        $this->locksmith();
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testExactAuthorityBecomesOpaqueLeaseAndDurableClaimWithoutCredentialAuthority(): void
    {
        [$request, $decision, $lease, $claim] = $this->lifecycle();

        self::assertSame($this->resolver->authority['source'], $request['source_governance_authority']);
        self::assertSame('AUTHORIZED', $decision['disposition']);
        self::assertTrue($lease['opaque']);
        self::assertFalse($lease['credential_reference_disclosed']);
        self::assertSame('GOVERNANCE_INVOCATION_CLAIMED_DURABLE_PRE_IO', $claim['status']);
        self::assertTrue($claim['lease_consumption']['consumed']);
        self::assertTrue($claim['governance_authority_consumption']['consumed']);
        self::assertFalse($claim['provider_request']['external_io_started']);
        self::assertFalse($claim['recovery']['automatic_replay_permitted']);
        $journal = new ProviderInvocationJournalService($this->root);
        self::assertSame('INVOCATION_RESERVED_PRE_IO', $journal->reserveGovernance($claim, new \DateTimeImmutable('2026-08-26T18:03:10+00:00'))['status']);
        self::assertSame('INVOCATION_IN_FLIGHT', $journal->startReservedGovernance($claim, new \DateTimeImmutable('2026-08-26T18:03:20+00:00'))['status']);
        $response = '{"bounded":"result"}';
        $envelope = (new ProviderResponseEnvelopeService($this->root))->seal($claim, $response, new \DateTimeImmutable('2026-08-26T18:03:30+00:00'));
        self::assertSame($response, $envelope['response']);
        self::assertSame('PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING', $journal->sealResponse($claim, $response, new \DateTimeImmutable('2026-08-26T18:03:30+00:00'))['status']);
        self::assertSame($request, $this->request());
        self::assertSame($claim, (new GovernanceCognitionInvocationClaimService($this->root, $this->registry))->claim($lease['lease_id'], $this->resolver->authority['authority_id'], new \DateTimeImmutable('2026-08-26T18:05:00+00:00')));
        $serialized = CanonicalJson::encode([$request, $decision, $lease, $claim]);
        foreach (['DEEPSEEK_API_KEY', 'Bearer ', 'test-secret', '"credential_reference":'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $serialized);
        }
    }

    public function testRefusalOpensNoClaviumAuthority(): void
    {
        $request = $this->request();
        $decision = (new GovernanceProviderResourceDecisionService($this->root))->decide(
            $request['request_id'], 'REFUSED', [], $this->ceiling(), 'Expenditure refused.',
            new \DateTimeImmutable('2026-08-26T18:08:00+00:00'), new \DateTimeImmutable('2026-08-26T18:01:00+00:00'),
        );
        self::assertNull($decision['clavium_lease_activation_authority']);
        self::assertSame('GOVERNANCE_PROVIDER_RESOURCE_REFUSED_NO_AUTHORITY', $decision['status']);
    }

    public function testAbsentAndCrossClusterResolverFailBeforeARequestCanExist(): void
    {
        try {
            (new GovernanceCognitionRequestService($this->root, new GovernanceCognitionAuthorityRegistry()))->request(
                'foundry', 'persona-specification', 'authority-test', 'foundry.artificer', 'specify-persona', str_repeat('a', 64),
                ['provider' => 'deepseek', 'model' => 'deepseek-v4-flash'], new \DateTimeImmutable('2026-08-26T18:10:00+00:00'), new \DateTimeImmutable('2026-08-26T18:00:00+00:00'),
            );
            self::fail('Expected absent resolver failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('GCA100_GOVERNANCE_AUTHORITY_RESOLVER_UNAVAILABLE', $exception->getMessage());
        }
        $this->expectExceptionMessage('GCA100_GOVERNANCE_AUTHORITY_RESOLVER_UNAVAILABLE');
        (new GovernanceCognitionRequestService($this->root, $this->registry))->request(
            'senate-profile-examination', 'persona-specification', 'authority-test', 'foundry.artificer', 'specify-persona', str_repeat('a', 64),
            ['provider' => 'deepseek', 'model' => 'deepseek-v4-flash'], new \DateTimeImmutable('2026-08-26T18:10:00+00:00'), new \DateTimeImmutable('2026-08-26T18:00:00+00:00'),
        );
    }

    public function testAmbiguousResolverOwnershipFailsStopped(): void
    {
        $registry = new GovernanceCognitionAuthorityRegistry([$this->resolver, new TestGovernanceAuthorityResolver($this->authority())]);
        $this->expectExceptionMessage('GCA100_GOVERNANCE_AUTHORITY_RESOLVER_UNAVAILABLE');
        (new GovernanceCognitionRequestService($this->root, $registry))->request(
            'foundry', 'persona-specification', 'authority-test', 'foundry.artificer', 'specify-persona', str_repeat('a', 64),
            ['provider' => 'deepseek', 'model' => 'deepseek-v4-flash'], new \DateTimeImmutable('2026-08-26T18:10:00+00:00'), new \DateTimeImmutable('2026-08-26T18:00:00+00:00'),
        );
    }

    public function testExpiredMismatchedAndConsumedAuthorityFailStopped(): void
    {
        foreach (['expired', 'mismatched', 'consumed'] as $case) {
            $authority = $this->authority();
            if ('expired' === $case) { $authority['expires_at'] = '2026-08-26T18:00:00+00:00'; }
            if ('mismatched' === $case) { $authority['input_digest'] = str_repeat('9', 64); }
            if ('consumed' === $case) { $authority['consumed'] = true; }
            $root = $this->root.'/'.$case;
            $service = new GovernanceCognitionRequestService($root, new GovernanceCognitionAuthorityRegistry([new TestGovernanceAuthorityResolver($authority)]));
            try {
                $service->request('foundry', 'persona-specification', 'authority-test', 'foundry.artificer', 'specify-persona', str_repeat('a', 64), ['provider' => 'deepseek', 'model' => 'deepseek-v4-flash'], new \DateTimeImmutable('2026-08-26T18:10:00+00:00'), new \DateTimeImmutable('2026-08-26T18:00:00+00:00'));
                self::fail('Expected '.$case.' authority failure.');
            } catch (\RuntimeException $exception) {
                self::assertSame('GCA102_GOVERNANCE_AUTHORITY_INVALID', $exception->getMessage());
            }
        }
    }

    public function testDivergentReplayAndPartialClaimConsumptionFailStopped(): void
    {
        [$request, , $lease] = $this->lifecycle(false);
        $this->resolver->authority['purpose'] = 'review-persona';
        try {
            (new GovernanceCognitionRequestService($this->root, $this->registry))->request(
                'foundry', 'persona-specification', 'authority-test', 'foundry.artificer', 'review-persona', str_repeat('a', 64),
                ['provider' => 'deepseek', 'model' => 'deepseek-v4-flash'], new \DateTimeImmutable('2026-08-26T18:10:00+00:00'), new \DateTimeImmutable('2026-08-26T18:00:00+00:00'),
            );
            self::fail('Expected divergent request conflict.');
        } catch (\RuntimeException $exception) {
            self::assertSame('GCA103_GOVERNANCE_COGNITION_REQUEST_CONFLICT', $exception->getMessage());
        }
        $this->resolver->authority['purpose'] = 'specify-persona';

        $claimId = 'governance-cognition-invocation-claim-'.str_repeat('9', 20);
        $this->persist('var/imperium/runtime/governance-cognition-invocation-claims/'.$claimId.'.json', [
            'schema' => 'imperium.clavium-governance-cognition-invocation-claim/v1', 'claim_id' => $claimId,
            'lease_consumption' => ['lease_id' => $lease['lease_id'], 'consumed' => true],
            'governance_authority_consumption' => ['authority_id' => 'other-authority', 'consumed' => false], 'sealed' => true,
        ]);
        $this->expectExceptionMessage('GCA404_GOVERNANCE_INVOCATION_CLAIM_CONFLICT');
        (new GovernanceCognitionInvocationClaimService($this->root, $this->registry))->claim($lease['lease_id'], $request['authority_identity'], new \DateTimeImmutable('2026-08-26T18:04:00+00:00'));
    }

    private function lifecycle(bool $claim = true): array
    {
        $request = $this->request();
        $decision = (new GovernanceProviderResourceDecisionService($this->root))->decide($request['request_id'], 'AUTHORIZED', ['temperature' => 0.2], $this->ceiling(), 'Exact expenditure authorized.', new \DateTimeImmutable('2026-08-26T18:08:00+00:00'), new \DateTimeImmutable('2026-08-26T18:01:00+00:00'));
        $lease = (new GovernanceCognitionLeaseService($this->root))->issue($decision['decision_id'], $decision['clavium_lease_activation_authority']['authority_id'], 'locksmith-binding-test', new \DateTimeImmutable('2026-08-26T18:06:00+00:00'), new \DateTimeImmutable('2026-08-26T18:02:00+00:00'));
        $invocation = $claim ? (new GovernanceCognitionInvocationClaimService($this->root, $this->registry))->claim($lease['lease_id'], $this->resolver->authority['authority_id'], new \DateTimeImmutable('2026-08-26T18:03:00+00:00')) : null;
        return [$request, $decision, $lease, $invocation];
    }

    private function request(): array
    {
        return (new GovernanceCognitionRequestService($this->root, $this->registry))->request('foundry', 'persona-specification', 'authority-test', 'foundry.artificer', 'specify-persona', str_repeat('a', 64), ['provider' => 'deepseek', 'model' => 'deepseek-v4-flash'], new \DateTimeImmutable('2026-08-26T18:10:00+00:00'), new \DateTimeImmutable('2026-08-26T18:00:00+00:00'));
    }

    private function authority(): array
    {
        return ['cluster' => 'foundry', 'authority_type' => 'persona-specification', 'authority_id' => 'authority-test', 'source' => ['id' => 'foundry-authority-record-test', 'digest' => str_repeat('b', 64)], 'instance_id' => 'imperium-test', 'case_id' => 'case-test', 'case_digest' => str_repeat('c', 64), 'seat' => 'foundry.artificer', 'purpose' => 'specify-persona', 'input_digest' => str_repeat('a', 64), 'single_use' => true, 'exercisable' => true, 'consumed' => false, 'expires_at' => '2026-08-26T18:12:00+00:00'];
    }

    private function ceiling(): array { return ['maximum_input_tokens' => 4096, 'maximum_output_tokens' => 1024, 'maximum_cost_microusd' => 250000]; }

    private function locksmith(): void
    {
        $this->persist('var/imperium/offices/clavium/occupancy/locksmith-binding-test.json', ['schema' => 'imperium.clavium-locksmith-occupancy/v1', 'binding_id' => 'locksmith-binding-test', 'instance_id' => 'imperium-test', 'seat' => 'clavium.locksmith', 'manifestation_id' => 'locksmith-test', 'occupancy_generation' => 1, 'status' => 'ACTIVE', 'governance_cognition_lease_issuance_authority' => true, 'credential_disclosure_authority' => false, 'execution_authority' => false, 'sealed' => true]);
    }

    private function persist(string $relative, array $record): void
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->root.'/'.$relative;
        if (!is_dir(dirname($path))) { mkdir(dirname($path), 0770, true); }
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) { return; }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->remove($child) : unlink($child); }
        rmdir($path);
    }
}

final class TestGovernanceAuthorityResolver implements GovernanceCognitionAuthorityResolver
{
    public function __construct(public array $authority) {}
    public function supports(string $cluster, string $authorityType): bool { return $cluster === $this->authority['cluster'] && $authorityType === $this->authority['authority_type']; }
    public function resolve(string $cluster, string $authorityType, string $authorityId): array { return $this->authority; }
}
