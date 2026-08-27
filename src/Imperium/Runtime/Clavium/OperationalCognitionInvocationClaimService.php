<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\ReplayFingerprint;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use App\Imperium\Runtime\Governance\InternalCognitionLeaseControls;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalCognitionInvocationClaimService
{
    private const string LEASES = 'var/imperium/offices/clavium/operational-cognition-leases';
    private const string DECISIONS = 'var/imperium/imperator/operational-provider-resource-decisions';
    private const string REQUESTS = 'var/imperium/offices/curia/operational-cognition-requests';
    private const string CLAIMS = 'var/imperium/runtime/operational-cognition-invocation-claims';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $root,
        ?RecordReferenceValidator $validator = null,
        ?ImmutableRecordStore $records = null,
        ?AtomicTransition $atomic = null,
    ) {
        $this->validator = $validator ?? new RecordReferenceValidator($root);
        $this->atomic = $atomic ?? new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $this->atomic);
    }

    public function claim(string $leaseId, string $cognitionAuthorityId, \DateTimeImmutable $claimedAt): array
    {
        if (!preg_match('/^operational-cognition-lease-[a-f0-9]{20}$/', $leaseId)
            || !preg_match('/^operational-cognition-authority-[a-f0-9]{20}$/', $cognitionAuthorityId)) {
            throw new \InvalidArgumentException('OCA400_OPERATIONAL_INVOCATION_CLAIM_INPUT_INVALID');
        }

        return $this->atomic->run(
            'oca-cognition-authority:'.hash('sha256', $cognitionAuthorityId),
            fn (): array => $this->atomic->run(
                'oca-lease:'.hash('sha256', $leaseId),
                fn (): array => $this->claimWhileLocked($leaseId, $cognitionAuthorityId, $claimedAt),
            ),
        );
    }

    private function claimWhileLocked(string $leaseId, string $cognitionAuthorityId, \DateTimeImmutable $claimedAt): array
    {
        $lease = $this->validator->read($this->root.'/'.self::LEASES.'/'.$leaseId.'.json', 'OCA401_OPERATIONAL_COGNITION_LEASE_ABSENT');
        $decision = $this->validator->resolve($this->root.'/'.self::DECISIONS, $lease['source_provider_resource_decision'] ?? [], 'OCA402_PROVIDER_RESOURCE_DECISION_ABSENT', 'OCA403_OPERATIONAL_INVOCATION_CLAIM_CHAIN_INVALID', 'decision_id');
        $request = $this->validator->resolve($this->root.'/'.self::REQUESTS, $lease['source_cognition_request'] ?? [], 'OCA404_OPERATIONAL_COGNITION_REQUEST_ABSENT', 'OCA403_OPERATIONAL_INVOCATION_CLAIM_CHAIN_INVALID', 'request_id');
        $this->validate($leaseId, $lease, $decision, $request, $cognitionAuthorityId, $claimedAt);

        $authoritativeInputs = [
            'lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']],
            'decision' => ['id' => $decision['decision_id'], 'digest' => $decision['record_digest']],
            'request' => ['id' => $request['request_id'], 'digest' => $request['record_digest']],
            'cognition_authority_id' => $cognitionAuthorityId,
            'target' => $request['target'],
            'provider' => $lease['provider'],
            'model' => $lease['model'],
            'model_configuration' => $lease['model_configuration'],
            'input_digest' => $lease['input_digest'],
            'iteration' => $lease['iteration'],
        ];
        $fingerprint = ReplayFingerprint::of($authoritativeInputs);
        $claimId = 'operational-cognition-invocation-claim-'.substr(hash('sha256', $fingerprint), 0, 20);

        foreach (glob($this->root.'/'.self::CLAIMS.'/*.json') ?: [] as $path) {
            $prior = $this->validator->read($path, 'OCA405_OPERATIONAL_INVOCATION_CLAIM_CONFLICT');
            if (!$this->validator->isIntact($prior)) {
                throw new \RuntimeException('OCA405_OPERATIONAL_INVOCATION_CLAIM_CONFLICT');
            }
            $sameLease = ($prior['lease_consumption']['lease_id'] ?? null) === $leaseId;
            $sameAuthority = ($prior['cognition_authority_consumption']['authority_id'] ?? null) === $cognitionAuthorityId;
            if (!$sameLease && !$sameAuthority) {
                continue;
            }
            if (!$sameLease || !$sameAuthority
                || true !== ($prior['lease_consumption']['consumed'] ?? null)
                || true !== ($prior['cognition_authority_consumption']['consumed'] ?? null)) {
                throw new \RuntimeException('OCA406_PARTIAL_AUTHORITY_CONSUMPTION_DETECTED');
            }
            ReplayFingerprint::requireMatch($prior['claim_fingerprint'] ?? null, $authoritativeInputs, 'OCA405_OPERATIONAL_INVOCATION_CLAIM_CONFLICT');

            return $prior;
        }

        $idempotencyIdentity = 'imperium-'.$claimId;
        return $this->records->put(self::CLAIMS, $claimId, [
            'schema' => 'imperium.clavium-operational-cognition-invocation-claim/v1',
            'claim_id' => $claimId,
            'claim_fingerprint' => $fingerprint,
            'instance_id' => $lease['instance_id'],
            'case_id' => $lease['case_id'],
            'case_digest' => $lease['case_digest'],
            'source_lease' => ['id' => $leaseId, 'digest' => $lease['record_digest']],
            'source_provider_resource_decision' => ['id' => $decision['decision_id'], 'digest' => $decision['record_digest']],
            'source_cognition_request' => ['id' => $request['request_id'], 'digest' => $request['record_digest']],
            'target' => $request['target'],
            'provider' => $lease['provider'],
            'model' => $lease['model'],
            'model_configuration' => $lease['model_configuration'],
            'resource_ceiling' => $lease['resource_ceiling'],
            'input_digest' => $lease['input_digest'],
            'profile_model_requirements_digest' => $lease['profile_model_requirements_digest'],
            'iteration' => $lease['iteration'],
            'lease_consumption' => ['lease_id' => $leaseId, 'consumed' => true, 'consumed_at' => $claimedAt->format(DATE_ATOM), 'expires_at' => $lease['expires_at'], 'continuing_authority' => false],
            'cognition_authority_consumption' => ['authority_id' => $cognitionAuthorityId, 'consumed' => true, 'consumed_at' => $claimedAt->format(DATE_ATOM), 'continuing_authority' => false],
            'provider_request' => ['idempotency_identity' => $idempotencyIdentity, 'external_io_started' => false, 'provider_response_identity' => null],
            'recovery' => ['automatic_replay_permitted' => false, 'unknown_outcome_requires_governed_resolution' => true],
            'claimed_at' => $claimedAt->format(DATE_ATOM),
            'status' => 'OPERATIONAL_INVOCATION_CLAIMED_DURABLE_PRE_IO',
            'provider_invoked' => false,
            'credential_resolved' => false,
            'credential_material_present' => false,
            'network_access_performed' => false,
            'execution_continuation_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validate(string $leaseId, array $lease, array $decision, array $request, string $cognitionAuthorityId, \DateTimeImmutable $claimedAt): void
    {
        $controls = $lease['continuous_governance_controls'] ?? null;
        $controlsInvalid = null !== $controls && !InternalCognitionLeaseControls::isExactOperational($controls, $decision, $request, (string) ($lease['issued_at'] ?? ''), (string) ($lease['expires_at'] ?? ''));
        if (!$this->validator->isIntact($lease)
            || 'imperium.clavium-operational-cognition-lease/v1' !== ($lease['schema'] ?? null)
            || $leaseId !== ($lease['lease_id'] ?? null)
            || 'OPERATIONAL_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM' !== ($lease['status'] ?? null)
            || true !== ($lease['opaque'] ?? null)
            || true !== ($lease['lease_single_use'] ?? null)
            || false !== ($lease['lease_consumed'] ?? null)
            || $controlsInvalid
            || new \DateTimeImmutable((string) ($lease['expires_at'] ?? '1970-01-01')) <= $claimedAt
            || true === ($lease['credential_reference_disclosed'] ?? null)
            || true === ($lease['credential_material_present'] ?? null)
            || true === ($lease['credential_use_authority'] ?? null)
            || true === ($lease['network_access_authority'] ?? null)
            || true === ($lease['provider_invocation_authority'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || 'OPERATIONAL_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE' !== ($decision['status'] ?? null)
            || new \DateTimeImmutable((string) ($decision['expires_at'] ?? '1970-01-01')) <= $claimedAt
            || ($decision['target'] ?? null) !== ($request['target'] ?? null)
            || ($decision['provider'] ?? null) !== ($lease['provider'] ?? null)
            || ($decision['model'] ?? null) !== ($lease['model'] ?? null)
            || ($decision['model_configuration'] ?? null) !== ($lease['model_configuration'] ?? null)
            || ($decision['resource_ceiling'] ?? null) !== ($lease['resource_ceiling'] ?? null)
            || ($request['input_digest'] ?? null) !== ($lease['input_digest'] ?? null)
            || ($request['profile_model_requirements_digest'] ?? null) !== ($lease['profile_model_requirements_digest'] ?? null)
            || ($request['iteration'] ?? null) !== ($lease['iteration'] ?? null)
            || $cognitionAuthorityId !== ($request['cognition_authority_id'] ?? null)
            || 'OPERATIONAL_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION' !== ($request['status'] ?? null)
            || new \DateTimeImmutable((string) ($request['expires_at'] ?? '1970-01-01')) <= $claimedAt
            || true !== ($request['cognition_authority'] ?? null)
            || true !== ($request['cognition_authority_single_use'] ?? null)
            || false !== ($request['cognition_authority_consumed'] ?? null)) {
            throw new \RuntimeException('OCA403_OPERATIONAL_INVOCATION_CLAIM_CHAIN_INVALID');
        }
    }
}
