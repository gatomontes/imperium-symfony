<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderExecutionBoundaryRedesignInertIssuanceService;
use App\Imperium\Runtime\Imperator\ProviderExecutionBoundaryRedesignIssuanceContractValidator;
use App\Imperium\Runtime\Imperator\SingleOperationProviderBindingActivationIssuanceContract;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SingleOperationProviderBindingActivationIssuanceService
{
    public const string DECISIONS = 'var/imperium/imperator/single-operation-provider-binding-activation-decisions';
    public const string ACTIVATIONS = 'var/imperium/offices/la-cortine/single-operation-provider-binding-activations';
    public const string ISSUANCES = 'var/imperium/imperator/single-operation-provider-binding-activation-issuances';

    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private AuthorityConsumptionStore $consumptions;
    private RecordReferenceValidator $references;
    private ProviderExecutionBoundaryRedesignIssuanceContractValidator $contracts;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
        $this->consumptions = new AuthorityConsumptionStore($this->records, $this->atomic);
        $this->references = new RecordReferenceValidator($root);
        $this->contracts = new ProviderExecutionBoundaryRedesignIssuanceContractValidator();
    }

    public function activate(
        string $decisionId,
        array $candidate,
        \DateTimeImmutable $at,
    ): array {
        $contract = SingleOperationProviderBindingActivationIssuanceContract::class;
        $decision = $this->references->read(
            $this->root.'/'.self::DECISIONS.'/'.$decisionId.'.json',
            'PEB400_ACTIVATION_DECISION_ABSENT',
        );
        $this->contracts->assertDecision($decision, $contract, $at);
        if ($decisionId !== ($decision['decision_id'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)) {
            throw new \RuntimeException('PEB401_ACTIVATION_DECISION_NOT_ISSUABLE');
        }

        $boundaryReference = $candidate['execution_boundary'] ?? [];
        $principalReference = $candidate['executor_principal'] ?? [];
        $bindingReference = $candidate['provider_binding'] ?? [];
        $boundary = $this->references->resolve(
            $this->root.'/'.ProviderExecutionBoundaryRedesignInertIssuanceService::BOUNDARIES,
            $boundaryReference,
            'PEB402_EXECUTION_BOUNDARY_ABSENT',
            'PEB403_EXECUTION_BOUNDARY_MISMATCH',
            'boundary_id',
        );
        $principal = $this->references->resolve(
            $this->root.'/'.ProviderExecutionBoundaryRedesignInertIssuanceService::PRINCIPAL_ATTESTATIONS,
            $principalReference,
            'PEB404_EXECUTOR_PRINCIPAL_ABSENT',
            'PEB405_EXECUTOR_PRINCIPAL_MISMATCH',
            'principal_attestation_id',
        );
        $binding = $this->references->resolve(
            $this->root.'/'.ProviderImplementationBindingService::BINDINGS,
            $bindingReference,
            'PEB406_PROVIDER_BINDING_ABSENT',
            'PEB407_PROVIDER_BINDING_MISMATCH',
            'binding_id',
        );
        $this->assertBasis(
            $decision,
            $candidate,
            $boundary,
            $principal,
            $binding,
            $at,
        );

        $candidateDigest = hash('sha256', CanonicalJson::encode($candidate));
        $expiresAt = $this->minimumExpiry(
            $decision['expires_at'],
            $principal['validity']['expires_at'],
            $binding['validity']['expires_at'],
        );
        $authority = $decision['issuance_authority'];
        $activation = [
            'schema' => SingleOperationProviderBindingActivationContract::SCHEMA,
            'activation_id' => $decision['target']['id'],
            'instance_id' => $decision['instance_id'],
            'source_activation_authority' => [
                'id' => $authority['authority_id'],
                'digest' => $decision['record_digest'],
                'schema' => $decision['schema'],
            ],
            'execution_boundary' => $boundaryReference,
            'executor_principal' => $principalReference,
            'provider_binding' => $bindingReference,
            'tool_authority' => $candidate['tool_authority'],
            'effect_authorization' => $candidate['effect_authorization'],
            'request' => $candidate['request'],
            'assurance_profile' => $candidate['assurance_profile'],
            'destination_policy' => $candidate['destination_policy'],
            'scope' => $candidate['scope'],
            'activation_authority_consumption' => [
                'authority_id' => $authority['authority_id'],
                'authority_digest' => $decision['record_digest'],
                'consumed_at' => $at->format(DATE_ATOM),
                'consumed' => true,
                'continuing_authority' => false,
            ],
            'status' => 'ACTIVATED_UNCONSUMED',
            'activated_at' => $at->format(DATE_ATOM),
            'expires_at' => $expiresAt,
            'single_operation' => true,
            'sealed' => true,
        ];
        $activation['record_digest'] = hash('sha256', CanonicalJson::encode($activation));
        $this->assertActivation($activation, $decision, $candidateDigest);

        return $this->commit($decision, $activation, $contract, $at);
    }

    private function commit(
        array $decision,
        array $activation,
        string $contract,
        \DateTimeImmutable $at,
    ): array {
        $authority = $decision['issuance_authority'];
        $issuanceId = str_replace('-decision-', '-issuance-', $decision['decision_id']);

        return $this->atomic->run(
            'single-operation-binding-activation:'.$authority['authority_id'],
            function () use (
                $decision,
                $activation,
                $contract,
                $at,
                $authority,
                $issuanceId,
            ): array {
                $consumption = $this->consumptions->consume(
                    $authority['authority_id'],
                    $decision['decision_id'],
                    $decision['record_digest'],
                    self::class,
                    $at,
                );
                $stored = $this->records->put(
                    self::ACTIVATIONS,
                    $activation['activation_id'],
                    $activation,
                );
                $issuance = [
                    'schema' => $contract::ISSUANCE_SCHEMA,
                    'issuance_id' => $issuanceId,
                    'instance_id' => $decision['instance_id'],
                    'source_decision' => [
                        'id' => $decision['decision_id'],
                        'digest' => $decision['record_digest'],
                        'schema' => $decision['schema'],
                    ],
                    'consumed_issuance_authority' => [
                        'id' => $authority['authority_id'],
                        'digest' => $decision['record_digest'],
                        'schema' => $decision['schema'],
                        'consumed_at' => $consumption['consumed_at'],
                        'consumed' => true,
                        'continuing_authority' => false,
                    ],
                    'issued_artifact' => [
                        'id' => $stored['activation_id'],
                        'digest' => $stored['record_digest'],
                        'schema' => $stored['schema'],
                    ],
                    'issuer' => $decision['actor'],
                    'issued_at' => $at->format(DATE_ATOM),
                    'binding_activation_issued' => true,
                    'principal_installed' => false,
                    'provider_binding_activated' => false,
                    'credential_capability_issued' => false,
                    'credential_resolved' => false,
                    'external_action_performed' => false,
                    'sealed' => true,
                ];
                $issuance['record_digest'] = hash('sha256', CanonicalJson::encode($issuance));
                $this->contracts->assertIssuance($issuance, $decision, $contract);

                return $this->records->put(self::ISSUANCES, $issuanceId, $issuance);
            },
        );
    }

    private function assertBasis(
        array $decision,
        array $candidate,
        array $boundary,
        array $principal,
        array $binding,
        \DateTimeImmutable $at,
    ): void {
        foreach ([
            'execution_boundary',
            'executor_principal',
            'provider_binding',
            'tool_authority',
            'effect_authorization',
            'request_reference',
            'destination_policy',
            'assurance_profile',
            'request',
            'scope',
        ] as $field) {
            if (!array_key_exists($field, $candidate)) {
                throw new \RuntimeException('PEB408_ACTIVATION_BASIS_INVALID');
            }
        }

        if (!$this->intact(
            $boundary,
            ProviderExecutionBoundaryContract::REQUIRED_FIELDS,
            ProviderExecutionBoundaryContract::SCHEMA,
        )
            || 'DEFINED_INERT' !== ($boundary['status'] ?? null)
            || !$this->intact(
                $principal,
                ProviderExecutorPrincipalContract::REQUIRED_FIELDS,
                ProviderExecutorPrincipalContract::SCHEMA,
            )
            || 'ATTESTED_INERT' !== ($principal['status'] ?? null)
            || !$this->intact(
                $binding,
                ProviderImplementationBindingContract::REQUIRED_FIELDS,
                ProviderImplementationBindingContract::SCHEMA,
            )
            || 'BOUND_INACTIVE' !== ($binding['status'] ?? null)
            || $decision['instance_id'] !== $boundary['instance_id']
            || $decision['instance_id'] !== $principal['instance_id']
            || $decision['instance_id'] !== $binding['instance_id']
            || $principal['execution_boundary'] !== $candidate['execution_boundary']
            || $principal['principal']['process_boundary_id'] !== $boundary['boundary_id']
            || true !== ($principal['competence']['same_process_execution_required'] ?? null)
            || new \DateTimeImmutable($principal['validity']['effective_at']) > $at
            || new \DateTimeImmutable($principal['validity']['expires_at']) <= $at
            || null !== ($principal['validity']['revocation_reference'] ?? null)
            || new \DateTimeImmutable($binding['validity']['effective_at']) > $at
            || new \DateTimeImmutable($binding['validity']['expires_at']) <= $at
            || $decision['basis']['execution_boundary'] !== $candidate['execution_boundary']
            || $decision['basis']['executor_principal'] !== $candidate['executor_principal']
            || $decision['basis']['provider_binding'] !== $candidate['provider_binding']
            || $decision['basis']['tool_authority'] !== $candidate['tool_authority']
            || $decision['basis']['effect_authorization'] !== $candidate['effect_authorization']
            || $decision['basis']['request'] !== $candidate['request_reference']
            || $decision['basis']['destination_policy'] !== $candidate['destination_policy']
            || $decision['basis']['assurance_profile'] !== $candidate['assurance_profile']
            || $binding['tool_operation'] !== $candidate['tool_authority']
            || $binding['assurance_profile'] !== $candidate['assurance_profile']
            || $candidate['destination_policy'] !== [
                'id' => $binding['destination_policy']['policy_id'] ?? null,
                'digest' => $binding['destination_policy']['policy_digest'] ?? null,
                'schema' => 'imperium.la-cortine.destination-policy/v1',
            ]
            || ProviderImplementationBindingContract::REQUIRED_PROVIDER_IMPLEMENTATION_FIELDS
                !== array_keys($binding['provider_implementation'] ?? [])
            || ProviderImplementationBindingContract::REQUIRED_VALIDITY_FIELDS
                !== array_keys($binding['validity'] ?? [])
            || ProviderImplementationBindingContract::REQUIRED_SCOPE_FIELDS
                !== array_keys($binding['scope'] ?? [])
            || ProviderExecutorPrincipalContract::REQUIRED_COMPETENCE_FIELDS
                !== array_keys($principal['competence'] ?? [])
            || SingleOperationProviderBindingActivationContract::REQUIRED_REQUEST_FIELDS
                !== array_keys($candidate['request'] ?? [])
            || SingleOperationProviderBindingActivationContract::REQUIRED_SCOPE_FIELDS
                !== array_keys($candidate['scope'] ?? [])
            || $candidate['request']['operation'] !== $binding['scope']['operation']
            || $candidate['request']['operation'] !== $principal['competence']['operation']
            || $candidate['scope']['provider_id']
                !== $binding['provider_implementation']['provider_id']
            || $candidate['scope']['provider_id'] !== $principal['competence']['provider_id']
            || $candidate['scope']['adapter_id']
                !== $binding['provider_implementation']['adapter_id']
            || $candidate['scope']['adapter_id'] !== $principal['competence']['adapter_id']
            || false !== ($candidate['scope']['provider_substitution_permitted'] ?? null)
            || false !== ($candidate['scope']['request_substitution_permitted'] ?? null)
            || !is_string($candidate['request']['exact_destination'] ?? null)
            || '' === trim($candidate['request']['exact_destination'])
            || !is_string($candidate['request']['payload_digest'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/', $candidate['request']['payload_digest'])
            || !is_string($candidate['request']['request_fingerprint'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/', $candidate['request']['request_fingerprint'])) {
            throw new \RuntimeException('PEB408_ACTIVATION_BASIS_INVALID');
        }
    }

    private function assertActivation(
        array $activation,
        array $decision,
        string $candidateDigest,
    ): void
    {
        if (SingleOperationProviderBindingActivationContract::REQUIRED_FIELDS
                !== array_keys($activation)
            || SingleOperationProviderBindingActivationContract::SCHEMA
                !== ($activation['schema'] ?? null)
            || $decision['target'] !== [
                'kind' => SingleOperationProviderBindingActivationIssuanceContract::TARGET_KIND,
                'id' => $activation['activation_id'],
                'digest' => $candidateDigest,
                'schema' => $activation['schema'],
            ]
            || SingleOperationProviderBindingActivationContract::REQUIRED_REFERENCE_FIELDS
                !== array_keys($activation['source_activation_authority'] ?? [])
            || SingleOperationProviderBindingActivationContract::REQUIRED_REFERENCE_FIELDS
                !== array_keys($activation['execution_boundary'] ?? [])
            || SingleOperationProviderBindingActivationContract::REQUIRED_REFERENCE_FIELDS
                !== array_keys($activation['executor_principal'] ?? [])
            || SingleOperationProviderBindingActivationContract::REQUIRED_REFERENCE_FIELDS
                !== array_keys($activation['provider_binding'] ?? [])
            || SingleOperationProviderBindingActivationContract::REQUIRED_REQUEST_FIELDS
                !== array_keys($activation['request'] ?? [])
            || SingleOperationProviderBindingActivationContract::REQUIRED_SCOPE_FIELDS
                !== array_keys($activation['scope'] ?? [])
            || SingleOperationProviderBindingActivationContract::REQUIRED_CONSUMPTION_FIELDS
                !== array_keys($activation['activation_authority_consumption'] ?? [])
            || 'ACTIVATED_UNCONSUMED' !== $activation['status']
            || true !== $activation['single_operation']
            || true !== $activation['activation_authority_consumption']['consumed']
            || false !== $activation['activation_authority_consumption']['continuing_authority']) {
            throw new \RuntimeException('PEB409_ACTIVATION_INVALID');
        }
    }

    private function intact(array $record, array $fields, string $schema): bool
    {
        $digest = $record['record_digest'] ?? null;
        $plain = $record;
        unset($plain['record_digest']);

        return $fields === array_keys($record)
            && $schema === ($record['schema'] ?? null)
            && true === ($record['sealed'] ?? null)
            && is_string($digest)
            && hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)));
    }

    private function minimumExpiry(string ...$values): string
    {
        $dates = array_map(
            static fn (string $value): \DateTimeImmutable => new \DateTimeImmutable($value),
            $values,
        );
        usort(
            $dates,
            static fn (\DateTimeImmutable $left, \DateTimeImmutable $right): int
                => $left <=> $right,
        );

        return $dates[0]->format(DATE_ATOM);
    }
}
