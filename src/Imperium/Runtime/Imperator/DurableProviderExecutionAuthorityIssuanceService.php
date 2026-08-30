<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderExecutionBoundaryContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingService;
use App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationContract;
use App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationIssuanceService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DurableProviderExecutionAuthorityIssuanceService
{
    public const string DECISIONS = 'var/imperium/imperator/durable-provider-execution-authority-decisions';
    public const string AUTHORITIES = 'var/imperium/imperator/durable-provider-execution-authorities';
    public const string ISSUANCES = 'var/imperium/imperator/durable-provider-execution-authority-issuances';

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

    public function issue(
        string $decisionId,
        array $candidate,
        \DateTimeImmutable $at,
    ): array {
        $contract = DurableProviderExecutionAuthorityIssuanceContract::class;
        $decision = $this->references->read(
            $this->root.'/'.self::DECISIONS.'/'.$decisionId.'.json',
            'PEB500_EXECUTION_AUTHORITY_DECISION_ABSENT',
        );
        $this->contracts->assertDecision($decision, $contract, $at);
        if ($decisionId !== ($decision['decision_id'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)) {
            throw new \RuntimeException('PEB501_EXECUTION_AUTHORITY_DECISION_NOT_ISSUABLE');
        }

        $boundary = $this->resolve(
            ProviderExecutionBoundaryRedesignInertIssuanceService::BOUNDARIES,
            $candidate['execution_boundary'] ?? [],
            'boundary_id',
            'PEB502_EXECUTION_BOUNDARY_ABSENT',
            'PEB503_EXECUTION_BOUNDARY_MISMATCH',
        );
        $principal = $this->resolve(
            ProviderExecutionBoundaryRedesignInertIssuanceService::PRINCIPAL_ATTESTATIONS,
            $candidate['executor_principal'] ?? [],
            'principal_attestation_id',
            'PEB504_EXECUTOR_PRINCIPAL_ABSENT',
            'PEB505_EXECUTOR_PRINCIPAL_MISMATCH',
        );
        $activation = $this->resolve(
            SingleOperationProviderBindingActivationIssuanceService::ACTIVATIONS,
            $candidate['provider_binding_activation'] ?? [],
            'activation_id',
            'PEB506_PROVIDER_ACTIVATION_ABSENT',
            'PEB507_PROVIDER_ACTIVATION_MISMATCH',
        );
        $binding = $this->resolve(
            ProviderImplementationBindingService::BINDINGS,
            $candidate['provider_binding'] ?? [],
            'binding_id',
            'PEB508_PROVIDER_BINDING_ABSENT',
            'PEB509_PROVIDER_BINDING_MISMATCH',
        );
        $this->assertBasis(
            $decision,
            $candidate,
            $boundary,
            $principal,
            $activation,
            $binding,
            $at,
        );

        $candidateDigest = hash('sha256', CanonicalJson::encode($candidate));
        $authority = [
            'schema' => DurableProviderExecutionAuthorityContract::SCHEMA,
            'authority_id' => $decision['target']['id'],
            'instance_id' => $decision['instance_id'],
            'source_decision' => [
                'id' => $decisionId,
                'digest' => $decision['record_digest'],
                'schema' => $decision['schema'],
            ],
            'execution_boundary' => $candidate['execution_boundary'],
            'executor_principal' => $candidate['executor_principal'],
            'tool_authority' => $candidate['tool_authority'],
            'effect_authorization' => $candidate['effect_authorization'],
            'provider_binding_activation' => $candidate['provider_binding_activation'],
            'provider_binding' => $candidate['provider_binding'],
            'request' => $candidate['request'],
            'destination_policy' => $candidate['destination_policy'],
            'assurance_profile' => $candidate['assurance_profile'],
            'scope' => $candidate['scope'],
            'validity' => $candidate['validity'],
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'consumed' => false,
            'continuing_authority' => false,
            'issued_at' => $at->format(DATE_ATOM),
            'sealed' => true,
        ];
        $authority['record_digest'] = hash('sha256', CanonicalJson::encode($authority));
        $this->assertAuthority($authority, $decision, $candidateDigest);

        return $this->commit($decision, $authority, $contract, $at);
    }

    private function commit(
        array $decision,
        array $authority,
        string $contract,
        \DateTimeImmutable $at,
    ): array {
        $issuanceAuthority = $decision['issuance_authority'];
        $issuanceId = str_replace('-decision-', '-issuance-', $decision['decision_id']);

        return $this->atomic->run(
            'durable-provider-execution-authority-issuance:'
                .$issuanceAuthority['authority_id'],
            function () use (
                $decision,
                $authority,
                $contract,
                $at,
                $issuanceAuthority,
                $issuanceId,
            ): array {
                $consumption = $this->consumptions->consume(
                    $issuanceAuthority['authority_id'],
                    $decision['decision_id'],
                    $decision['record_digest'],
                    self::class,
                    $at,
                );
                $stored = $this->records->put(
                    self::AUTHORITIES,
                    $authority['authority_id'],
                    $authority,
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
                        'id' => $issuanceAuthority['authority_id'],
                        'digest' => $decision['record_digest'],
                        'schema' => $decision['schema'],
                        'consumed_at' => $consumption['consumed_at'],
                        'consumed' => true,
                        'continuing_authority' => false,
                    ],
                    'issued_artifact' => [
                        'id' => $stored['authority_id'],
                        'digest' => $stored['record_digest'],
                        'schema' => $stored['schema'],
                    ],
                    'issuer' => $decision['actor'],
                    'issued_at' => $at->format(DATE_ATOM),
                    'execution_authority_issued' => true,
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
        array $activation,
        array $binding,
        \DateTimeImmutable $at,
    ): void {
        foreach ([
            'execution_boundary',
            'executor_principal',
            'provider_binding_activation',
            'provider_binding',
            'tool_authority',
            'effect_authorization',
            'request_reference',
            'request',
            'destination_policy',
            'assurance_profile',
            'scope',
            'validity',
        ] as $field) {
            if (!array_key_exists($field, $candidate)) {
                throw new \RuntimeException('PEB510_EXECUTION_AUTHORITY_BASIS_INVALID');
            }
        }

        $requestProjection = $candidate['request'];
        unset($requestProjection['commission_id']);
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
                $activation,
                SingleOperationProviderBindingActivationContract::REQUIRED_FIELDS,
                SingleOperationProviderBindingActivationContract::SCHEMA,
            )
            || 'ACTIVATED_UNCONSUMED' !== ($activation['status'] ?? null)
            || true !== ($activation['single_operation'] ?? null)
            || !$this->intact(
                $binding,
                ProviderImplementationBindingContract::REQUIRED_FIELDS,
                ProviderImplementationBindingContract::SCHEMA,
            )
            || 'BOUND_INACTIVE' !== ($binding['status'] ?? null)
            || $decision['instance_id'] !== $boundary['instance_id']
            || $decision['instance_id'] !== $principal['instance_id']
            || $decision['instance_id'] !== $activation['instance_id']
            || $decision['instance_id'] !== $binding['instance_id']
            || $activation['execution_boundary'] !== $candidate['execution_boundary']
            || $activation['executor_principal'] !== $candidate['executor_principal']
            || $activation['provider_binding'] !== $candidate['provider_binding']
            || $activation['tool_authority'] !== $candidate['tool_authority']
            || $activation['effect_authorization'] !== $candidate['effect_authorization']
            || $activation['request'] !== $requestProjection
            || $activation['destination_policy'] !== $candidate['destination_policy']
            || $activation['assurance_profile'] !== $candidate['assurance_profile']
            || $decision['basis']['execution_boundary'] !== $candidate['execution_boundary']
            || $decision['basis']['executor_principal'] !== $candidate['executor_principal']
            || $decision['basis']['provider_binding_activation']
                !== $candidate['provider_binding_activation']
            || $decision['basis']['provider_binding'] !== $candidate['provider_binding']
            || $decision['basis']['tool_authority'] !== $candidate['tool_authority']
            || $decision['basis']['effect_authorization'] !== $candidate['effect_authorization']
            || $decision['basis']['request'] !== $candidate['request_reference']
            || $decision['basis']['destination_policy'] !== $candidate['destination_policy']
            || $decision['basis']['assurance_profile'] !== $candidate['assurance_profile']
            || DurableProviderExecutionAuthorityContract::REQUIRED_REQUEST_FIELDS
                !== array_keys($candidate['request'] ?? [])
            || DurableProviderExecutionAuthorityContract::REQUIRED_SCOPE_FIELDS
                !== array_keys($candidate['scope'] ?? [])
            || DurableProviderExecutionAuthorityContract::REQUIRED_VALIDITY_FIELDS
                !== array_keys($candidate['validity'] ?? [])
            || $candidate['request_reference']['id'] !== $candidate['request']['request_id']
            || $candidate['request']['operation'] !== $activation['request']['operation']
            || $candidate['scope']['execution_id'] !== $activation['scope']['execution_id']
            || $candidate['scope']['provider_id'] !== $activation['scope']['provider_id']
            || $candidate['scope']['adapter_id'] !== $activation['scope']['adapter_id']
            || $candidate['scope']['credential_family']
                !== $principal['competence']['credential_family']
            || $candidate['scope']['credential_family']
                !== $binding['credential_family']['family_id']
            || false !== ($candidate['scope']['provider_substitution_permitted'] ?? null)
            || false !== ($candidate['scope']['payload_substitution_permitted'] ?? null)
            || false !== ($candidate['scope']['destination_substitution_permitted'] ?? null)
            || !$this->validWindow(
                $candidate['validity'],
                $at,
                $decision['expires_at'],
                $principal['validity']['expires_at'],
                $activation['expires_at'],
                $binding['validity']['expires_at'],
            )) {
            throw new \RuntimeException('PEB510_EXECUTION_AUTHORITY_BASIS_INVALID');
        }
    }

    private function assertAuthority(
        array $authority,
        array $decision,
        string $candidateDigest,
    ): void {
        if (DurableProviderExecutionAuthorityContract::REQUIRED_FIELDS
                !== array_keys($authority)
            || DurableProviderExecutionAuthorityContract::SCHEMA
                !== ($authority['schema'] ?? null)
            || $decision['target'] !== [
                'kind' => DurableProviderExecutionAuthorityIssuanceContract::TARGET_KIND,
                'id' => $authority['authority_id'],
                'digest' => $candidateDigest,
                'schema' => $authority['schema'],
            ]
            || true !== $authority['authority_single_use']
            || true !== $authority['authority_exercisable']
            || false !== $authority['consumed']
            || false !== $authority['continuing_authority']) {
            throw new \RuntimeException('PEB511_EXECUTION_AUTHORITY_INVALID');
        }
    }

    private function resolve(
        string $directory,
        array $reference,
        string $idField,
        string $absent,
        string $mismatch,
    ): array {
        return $this->references->resolve(
            $this->root.'/'.$directory,
            $reference,
            $absent,
            $mismatch,
            $idField,
        );
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

    private function validWindow(
        array $validity,
        \DateTimeImmutable $at,
        string ...$limits,
    ): bool {
        try {
            $effective = new \DateTimeImmutable((string) ($validity['effective_at'] ?? ''));
            $expires = new \DateTimeImmutable((string) ($validity['expires_at'] ?? ''));
            foreach ($limits as $limit) {
                if ($expires > new \DateTimeImmutable($limit)) {
                    return false;
                }
            }
        } catch (\Exception) {
            return false;
        }

        return $effective <= $at
            && $at < $expires
            && null === ($validity['revocation_reference'] ?? null);
    }
}
