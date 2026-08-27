<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use App\Imperium\Runtime\Governance\InternalCognitionLeaseControls;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalCognitionLeaseService
{
    private const string DECISIONS = 'var/imperium/imperator/operational-provider-resource-decisions';
    private const string REQUESTS = 'var/imperium/offices/curia/operational-cognition-requests';
    private const string OCCUPANCY = 'var/imperium/offices/clavium/occupancy';
    private const string LEASES = 'var/imperium/offices/clavium/operational-cognition-leases';
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

    public function issue(
        string $decisionId,
        string $authorityId,
        string $locksmithBindingId,
        \DateTimeImmutable $expiresAt,
        \DateTimeImmutable $issuedAt,
    ): array {
        if (!preg_match('/^operational-provider-resource-decision-[a-f0-9]{20}$/', $decisionId)
            || !preg_match('/^operational-clavium-lease-activation-authority-[a-f0-9]{20}$/', $authorityId)
            || !preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $locksmithBindingId)) {
            throw new \InvalidArgumentException('OCA300_OPERATIONAL_LEASE_INPUT_INVALID');
        }
        if ($expiresAt <= $issuedAt || $expiresAt > $issuedAt->modify('+5 minutes')) {
            throw new \InvalidArgumentException('OCA301_OPERATIONAL_LEASE_EXPIRY_INVALID');
        }

        $decision = $this->validator->read($this->root.'/'.self::DECISIONS.'/'.$decisionId.'.json', 'OCA302_PROVIDER_RESOURCE_DECISION_ABSENT');
        $requestRef = $decision['source_cognition_request'] ?? [];
        $request = $this->validator->resolve($this->root.'/'.self::REQUESTS, $requestRef, 'OCA303_OPERATIONAL_COGNITION_REQUEST_ABSENT', 'OCA304_OPERATIONAL_LEASE_CHAIN_INVALID', 'request_id');
        $locksmith = $this->validator->read($this->root.'/'.self::OCCUPANCY.'/'.$locksmithBindingId.'.json', 'OCA305_LOCKSMITH_OCCUPANCY_ABSENT');
        $this->validate($decisionId, $decision, $request, $authorityId, $locksmithBindingId, $locksmith, $expiresAt, $issuedAt);

        $issuer = [
            'seat' => 'clavium.locksmith',
            'binding_id' => $locksmithBindingId,
            'binding_digest' => $locksmith['record_digest'],
            'manifestation_id' => $locksmith['manifestation_id'],
            'occupancy_generation' => $locksmith['occupancy_generation'],
        ];
        $leaseId = 'operational-cognition-lease-'.substr(hash('sha256', CanonicalJson::encode([
            $decisionId,
            $decision['record_digest'],
            $request['request_id'],
            $request['record_digest'],
            $authorityId,
            $issuer,
            $issuedAt->format(DATE_ATOM),
            $expiresAt->format(DATE_ATOM),
        ])), 0, 20);
        $record = [
            'schema' => 'imperium.clavium-operational-cognition-lease/v1',
            'lease_id' => $leaseId,
            'instance_id' => $decision['instance_id'],
            'case_id' => $decision['case_id'],
            'case_digest' => $decision['case_digest'],
            'source_provider_resource_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']],
            'source_cognition_request' => ['id' => $request['request_id'], 'digest' => $request['record_digest']],
            'activation_authority' => ['id' => $authorityId, 'consumed' => true, 'continuing_authority' => false],
            'issuer' => $issuer,
            'target' => $request['target'],
            'provider' => $decision['provider'],
            'model' => $decision['model'],
            'model_configuration' => $decision['model_configuration'],
            'resource_ceiling' => $decision['resource_ceiling'],
            'input_digest' => $request['input_digest'],
            'profile_model_requirements_digest' => $request['profile_model_requirements_digest'],
            'iteration' => $request['iteration'],
            'issued_at' => $issuedAt->format(DATE_ATOM),
            'expires_at' => $expiresAt->format(DATE_ATOM),
            'continuous_governance_controls' => InternalCognitionLeaseControls::operational($decision, $request, $issuedAt->format(DATE_ATOM), $expiresAt->format(DATE_ATOM)),
            'status' => 'OPERATIONAL_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM',
            'opaque' => true,
            'lease_single_use' => true,
            'lease_consumed' => false,
            'credential_reference_disclosed' => false,
            'credential_material_present' => false,
            'credential_possession_transferred' => false,
            'credential_use_authority' => false,
            'network_access_authority' => false,
            'provider_invocation_authority' => false,
            'execution_continuation_authority' => false,
            'sealed' => true,
        ];

        return $this->atomic->run('oca-lease-source:'.$decisionId, function () use ($decisionId, $leaseId, $record): array {
            foreach (glob($this->root.'/'.self::LEASES.'/*.json') ?: [] as $path) {
                $prior = $this->validator->read($path, 'OCA307_OPERATIONAL_LEASE_CONFLICT');
                if (($prior['source_provider_resource_decision']['id'] ?? null) !== $decisionId) {
                    continue;
                }
                if (($prior['lease_id'] ?? null) !== $leaseId) {
                    throw new \RuntimeException('OCA307_OPERATIONAL_LEASE_CONFLICT');
                }

                return $prior;
            }

            return $this->records->put(self::LEASES, $leaseId, $record);
        });
    }

    private function validate(
        string $decisionId,
        array $decision,
        array $request,
        string $authorityId,
        string $locksmithBindingId,
        array $locksmith,
        \DateTimeImmutable $expiresAt,
        \DateTimeImmutable $issuedAt,
    ): void {
        $authority = $decision['clavium_lease_activation_authority'] ?? [];
        if (!$this->validator->isIntact($decision)
            || !$this->validator->isIntact($request)
            || !$this->validator->isIntact($locksmith)
            || 'imperium.imperator-operational-provider-resource-decision/v1' !== ($decision['schema'] ?? null)
            || $decisionId !== ($decision['decision_id'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || 'OPERATIONAL_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE' !== ($decision['status'] ?? null)
            || $authorityId !== ($authority['authority_id'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || new \DateTimeImmutable((string) ($decision['expires_at'] ?? '1970-01-01')) <= $issuedAt
            || $expiresAt > new \DateTimeImmutable($decision['expires_at'])
            || ($decision['target'] ?? null) !== ($request['target'] ?? null)
            || ($decision['input_digest'] ?? null) !== ($request['input_digest'] ?? null)
            || ($decision['profile_model_requirements_digest'] ?? null) !== ($request['profile_model_requirements_digest'] ?? null)
            || ($decision['iteration'] ?? null) !== ($request['iteration'] ?? null)
            || ($request['model_requirements']['provider'] ?? null) !== ($decision['provider'] ?? null)
            || ($request['model_requirements']['model'] ?? null) !== ($decision['model'] ?? null)
            || $locksmithBindingId !== ($locksmith['binding_id'] ?? null)
            || ($decision['instance_id'] ?? null) !== ($locksmith['instance_id'] ?? null)
            || 'imperium.clavium-locksmith-occupancy/v1' !== ($locksmith['schema'] ?? null)
            || 'clavium.locksmith' !== ($locksmith['seat'] ?? null)
            || 'ACTIVE' !== ($locksmith['status'] ?? null)
            || true !== ($locksmith['operational_cognition_lease_issuance_authority'] ?? null)
            || true === ($locksmith['credential_disclosure_authority'] ?? null)
            || true === ($locksmith['execution_authority'] ?? null)) {
            throw new \RuntimeException('OCA304_OPERATIONAL_LEASE_CHAIN_INVALID');
        }
        foreach (glob($this->root.'/'.self::DECISIONS.'/*.json') ?: [] as $path) {
            $other = $this->validator->read($path, 'OCA306_PROVIDER_RESOURCE_DECISION_CONFLICT');
            if (($other['source_cognition_request']['id'] ?? null) === $request['request_id'] && ($other['decision_id'] ?? null) !== $decisionId) {
                throw new \RuntimeException('OCA306_PROVIDER_RESOURCE_DECISION_CONFLICT');
            }
        }
    }
}
