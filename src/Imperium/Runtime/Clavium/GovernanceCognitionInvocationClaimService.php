<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityRegistry;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\ReplayFingerprint;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use App\Imperium\Runtime\Persistence\TransactionalAuthorityConsumptionEnvelope;
use App\Imperium\Runtime\Governance\InternalCognitionLeaseControls;
use App\Imperium\Runtime\Governance\GovernanceLeaseInterruptionAdmissionGuard;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GovernanceCognitionInvocationClaimService
{
    private const LEASES = 'var/imperium/offices/clavium/governance-cognition-leases';
    private const REQUESTS = 'var/imperium/runtime/governance-cognition-requests';
    private const CLAIMS = 'var/imperium/runtime/governance-cognition-invocation-claims';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;
    private GovernanceLeaseInterruptionAdmissionGuard $interruptionGuard;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, private GovernanceCognitionAuthorityRegistry $authorities, ?RecordReferenceValidator $validator = null, ?ImmutableRecordStore $records = null, ?AtomicTransition $atomic = null, ?GovernanceLeaseInterruptionAdmissionGuard $interruptionGuard = null)
    {
        $this->validator = $validator ?? new RecordReferenceValidator($root);
        $this->atomic = $atomic ?? new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $this->atomic);
        $this->interruptionGuard = $interruptionGuard ?? new GovernanceLeaseInterruptionAdmissionGuard($root);
    }

    public function claim(string $leaseId, string $authorityId, \DateTimeImmutable $claimedAt): array
    {
        if (!preg_match('/^governance-cognition-lease-[a-f0-9]{20}$/', $leaseId) || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{2,220}$/', $authorityId)) {
            throw new \InvalidArgumentException('GCA400_GOVERNANCE_INVOCATION_CLAIM_INPUT_INVALID');
        }
        return $this->atomic->run('gca-authority:'.hash('sha256', $authorityId), fn (): array => $this->atomic->run('gca-lease:'.hash('sha256', $leaseId), fn (): array => $this->claimLocked($leaseId, $authorityId, $claimedAt)));
    }

    private function claimLocked(string $leaseId, string $authorityId, \DateTimeImmutable $at): array
    {
        $lease = $this->validator->read($this->root.'/'.self::LEASES.'/'.$leaseId.'.json', 'GCA401_GOVERNANCE_LEASE_ABSENT');
        $this->interruptionGuard->assertMayCreateClaim($lease);
        $request = $this->validator->resolve($this->root.'/'.self::REQUESTS, $lease['source_cognition_request'] ?? [], 'GCA402_GOVERNANCE_REQUEST_ABSENT', 'GCA403_GOVERNANCE_INVOCATION_CHAIN_INVALID', 'request_id');
        $decision = $this->validator->resolve($this->root.'/var/imperium/imperator/governance-provider-resource-decisions', $lease['source_provider_resource_decision'] ?? [], 'GCA403_GOVERNANCE_INVOCATION_CHAIN_INVALID', 'GCA403_GOVERNANCE_INVOCATION_CHAIN_INVALID', 'decision_id');
        $authority = $this->authorities->resolve((string) ($request['cluster'] ?? ''), (string) ($request['authority_type'] ?? ''), $authorityId);
        $controls = $lease['continuous_governance_controls'] ?? null;
        $controlsInvalid = null !== $controls && !InternalCognitionLeaseControls::isExactGovernance($controls, $decision, $request, (string) ($lease['issued_at'] ?? ''), (string) ($lease['expires_at'] ?? ''));
        if (!$this->validator->isIntact($lease) || 'imperium.clavium-governance-cognition-lease/v1' !== ($lease['schema'] ?? null)
            || $leaseId !== ($lease['lease_id'] ?? null) || 'GOVERNANCE_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM' !== ($lease['status'] ?? null)
            || true !== ($lease['opaque'] ?? null) || true !== ($lease['lease_single_use'] ?? null) || false !== ($lease['lease_consumed'] ?? null)
            || $controlsInvalid
            || new \DateTimeImmutable((string) ($lease['expires_at'] ?? '1970-01-01')) <= $at || $authorityId !== ($request['authority_identity'] ?? null)
            || ($authority['source'] ?? null) !== ($request['source_governance_authority'] ?? null) || true !== ($authority['single_use'] ?? null)
            || true !== ($authority['exercisable'] ?? null) || false !== ($authority['consumed'] ?? null) || new \DateTimeImmutable((string) ($authority['expires_at'] ?? '1970-01-01')) <= $at
            || ($authority['cluster'] ?? null) !== ($request['cluster'] ?? null) || ($authority['seat'] ?? null) !== ($request['target']['seat'] ?? null)
            || ($authority['purpose'] ?? null) !== ($request['target']['purpose'] ?? null) || ($authority['input_digest'] ?? null) !== ($request['input_digest'] ?? null)) {
            throw new \RuntimeException('GCA403_GOVERNANCE_INVOCATION_CHAIN_INVALID');
        }
        $legacyIdentity = [$leaseId, $lease['record_digest'], $authorityId, $request['source_governance_authority'], $request['target'], $request['input_digest']];
        $claimId = 'governance-cognition-invocation-claim-'.substr(hash('sha256', CanonicalJson::encode($legacyIdentity)), 0, 20);
        $authoritativeInputs = [
            'lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']],
            'decision' => ['id' => $decision['decision_id'], 'digest' => $decision['record_digest']],
            'request' => ['id' => $request['request_id'], 'digest' => $request['record_digest']],
            'governance_authority' => $authority,
            'cluster' => $request['cluster'],
            'authority_type' => $request['authority_type'],
            'target' => $request['target'],
            'provider' => $lease['provider'],
            'model' => $lease['model'],
            'model_configuration' => $lease['model_configuration'],
            'resource_ceiling' => $lease['resource_ceiling'],
            'input_digest' => $lease['input_digest'],
        ];
        $fingerprint = ReplayFingerprint::of($authoritativeInputs);
        $authoritySet = $this->authoritySet($leaseId, $lease, $authorityId, $authority, $request);
        $consumer = [
            'actor' => ['kind' => 'runtime-service', 'id' => self::class],
            'competent_service' => self::class,
            'bounded_act' => 'CLAIM_ONE_GOVERNANCE_COGNITION_INVOCATION_PRE_IO',
        ];
        $lockPlan = [
            ['order' => 1, 'scope' => 'gca-authority:'.hash('sha256', $authorityId), 'authority_id' => $authorityId],
            ['order' => 2, 'scope' => 'gca-lease:'.hash('sha256', $leaseId), 'authority_id' => $leaseId],
        ];
        $immutableResult = ['schema' => 'imperium.clavium-governance-cognition-invocation-claim/v1', 'id' => $claimId, 'embedded' => true];
        foreach (glob($this->root.'/'.self::CLAIMS.'/*.json') ?: [] as $path) {
            $prior = $this->validator->read($path, 'GCA404_GOVERNANCE_INVOCATION_CLAIM_CONFLICT');
            $sameLease = ($prior['lease_consumption']['lease_id'] ?? null) === $leaseId;
            $sameAuthority = ($prior['governance_authority_consumption']['authority_id'] ?? null) === $authorityId;
            if (!$sameLease && !$sameAuthority) { continue; }
            if (!$this->validator->isIntact($prior) || !$sameLease || !$sameAuthority || ($prior['claim_id'] ?? null) !== $claimId
                || true !== ($prior['lease_consumption']['consumed'] ?? null) || true !== ($prior['governance_authority_consumption']['consumed'] ?? null)) {
                throw new \RuntimeException('GCA404_GOVERNANCE_INVOCATION_CLAIM_CONFLICT');
            }
            if (array_key_exists('transactional_consumption', $prior)) {
                if (!is_array($prior['transactional_consumption'])) {
                    throw new \RuntimeException('GCA404_GOVERNANCE_INVOCATION_CLAIM_CONFLICT');
                }
                ReplayFingerprint::requireMatch($prior['claim_fingerprint'] ?? null, $authoritativeInputs, 'GCA404_GOVERNANCE_INVOCATION_CLAIM_CONFLICT');
                try {
                    $recordedAt = new \DateTimeImmutable((string) ($prior['claimed_at'] ?? ''));
                } catch (\Exception) {
                    throw new \RuntimeException('GCA404_GOVERNANCE_INVOCATION_CLAIM_CONFLICT');
                }
                $expected = TransactionalAuthorityConsumptionEnvelope::complete($claimId, (string) $lease['instance_id'], $authoritySet, $authoritativeInputs, $fingerprint, $consumer, $lockPlan, $immutableResult, $recordedAt);
                TransactionalAuthorityConsumptionEnvelope::requireExact($prior['transactional_consumption'], $expected, 'GCA404_GOVERNANCE_INVOCATION_CLAIM_CONFLICT');
            }
            return $prior;
        }
        $transactionalConsumption = TransactionalAuthorityConsumptionEnvelope::complete($claimId, (string) $lease['instance_id'], $authoritySet, $authoritativeInputs, $fingerprint, $consumer, $lockPlan, $immutableResult, $at);
        return $this->records->put(self::CLAIMS, $claimId, [
            'schema' => 'imperium.clavium-governance-cognition-invocation-claim/v1', 'claim_id' => $claimId,
            'claim_fingerprint' => $fingerprint,
            'instance_id' => $lease['instance_id'], 'case_id' => $lease['case_id'], 'case_digest' => $lease['case_digest'],
            'source_lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']], 'source_cognition_request' => $lease['source_cognition_request'], 'source_governance_authority' => $request['source_governance_authority'],
            'cluster' => $request['cluster'], 'target' => $request['target'], 'provider' => $lease['provider'], 'model' => $lease['model'], 'model_configuration' => $lease['model_configuration'], 'resource_ceiling' => $lease['resource_ceiling'], 'input_digest' => $lease['input_digest'],
            'lease_consumption' => ['lease_id' => $leaseId, 'consumed' => true, 'consumed_at' => $at->format(DATE_ATOM), 'expires_at' => $lease['expires_at'], 'continuing_authority' => false],
            'governance_authority_consumption' => ['authority_id' => $authorityId, 'consumed' => true, 'consumed_at' => $at->format(DATE_ATOM), 'continuing_authority' => false],
            'provider_request' => ['idempotency_identity' => 'imperium-'.$claimId, 'external_io_started' => false, 'provider_response_identity' => null],
            'recovery' => ['automatic_replay_permitted' => false, 'unknown_outcome_requires_governed_resolution' => true],
            'transactional_consumption' => $transactionalConsumption,
            'claimed_at' => $at->format(DATE_ATOM), 'status' => 'GOVERNANCE_INVOCATION_CLAIMED_DURABLE_PRE_IO',
            'credential_resolved' => false, 'credential_material_present' => false, 'network_access_performed' => false, 'continuing_authority' => false, 'sealed' => true,
        ]);
    }

    private function authoritySet(string $leaseId, array $lease, string $authorityId, array $authority, array $request): array
    {
        return [
            [
                'authority_id' => $authorityId,
                'authority_schema' => (string) $request['schema'],
                'source' => $authority['source'],
                'issuer' => ['kind' => 'governance-authority-source', 'cluster' => $authority['cluster'], 'authority_type' => $authority['authority_type']],
                'holder' => $request['target'],
                'scope' => ['cluster' => $request['cluster'], 'authority_type' => $request['authority_type'], 'target' => $request['target'], 'input_digest' => $request['input_digest']],
                'expires_at' => $authority['expires_at'],
                'single_use' => true,
                'expected_unconsumed' => true,
            ],
            [
                'authority_id' => $leaseId,
                'authority_schema' => (string) $lease['schema'],
                'source' => ['id' => $leaseId, 'digest' => $lease['record_digest']],
                'issuer' => $lease['issuer'],
                'holder' => $request['target'],
                'scope' => ['cluster' => $request['cluster'], 'target' => $request['target'], 'provider' => $lease['provider'], 'model' => $lease['model'], 'model_configuration' => $lease['model_configuration'], 'input_digest' => $lease['input_digest']],
                'expires_at' => $lease['expires_at'],
                'single_use' => true,
                'expected_unconsumed' => true,
            ],
        ];
    }
}
