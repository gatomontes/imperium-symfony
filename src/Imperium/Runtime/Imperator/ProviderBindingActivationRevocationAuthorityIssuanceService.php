<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\ProviderTransition\{NativeBindingReader, NativeState};
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationRevocationContract;
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

final readonly class ProviderBindingActivationRevocationAuthorityIssuanceService
{
    public const string DECISIONS =
        'var/imperium/imperator/provider-binding-activation-revocation-authority-decisions';
    public const string AUTHORITIES =
        'var/imperium/imperator/provider-binding-activation-revocation-authorities';
    public const string ISSUANCES =
        'var/imperium/imperator/provider-binding-activation-revocation-authority-issuances';

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
        $this->references = new RecordReferenceValidator($root, new NativeBindingReader(new NativeState($root)));
        $this->contracts = new ProviderExecutionBoundaryRedesignIssuanceContractValidator();
    }

    public function issue(string $decisionId, array $candidate, \DateTimeImmutable $at): array
    {
        $reader = new NativeBindingReader(new NativeState($this->root));
        return $reader->legacy(function () use ($reader, $decisionId, $candidate, $at): array {
            $reader->assertLegacyRecord($candidate);
            return $this->legacyIssue($decisionId, $candidate, $at);
        });
    }

    private function legacyIssue(string $decisionId, array $candidate, \DateTimeImmutable $at): array
    {
        $contract = ProviderBindingActivationRevocationAuthorityIssuanceContract::class;
        $decision = $this->references->read(
            $this->root.'/'.self::DECISIONS.'/'.$decisionId.'.json',
            'PEB750_REVOCATION_AUTHORITY_DECISION_ABSENT',
        );
        $this->contracts->assertDecision($decision, $contract, $at);
        if ($decisionId !== ($decision['decision_id'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || [
                'provider_binding_activation', 'execution_boundary',
                'executor_principal', 'provider_binding',
                'allowed_reason_codes', 'validity',
            ] !== array_keys($candidate)) {
            throw new \RuntimeException('PEB751_REVOCATION_AUTHORITY_DECISION_NOT_ISSUABLE');
        }

        $activation = $this->references->resolve(
            $this->root.'/'.SingleOperationProviderBindingActivationIssuanceService::ACTIVATIONS,
            $candidate['provider_binding_activation'],
            'PEB752_PROVIDER_ACTIVATION_ABSENT',
            'PEB753_PROVIDER_ACTIVATION_MISMATCH',
            'activation_id',
        );
        $principal = $this->references->resolve(
            $this->root.'/'.ProviderExecutionBoundaryRedesignInertIssuanceService::PRINCIPAL_ATTESTATIONS,
            $candidate['executor_principal'],
            'PEB754_EXECUTOR_PRINCIPAL_ABSENT',
            'PEB755_EXECUTOR_PRINCIPAL_MISMATCH',
            'principal_attestation_id',
        );
        $binding = $this->references->resolve(
            $this->root.'/'.ProviderImplementationBindingService::BINDINGS,
            $candidate['provider_binding'],
            'PEB756_PROVIDER_BINDING_ABSENT',
            'PEB757_PROVIDER_BINDING_MISMATCH',
            'binding_id',
        );
        $this->assertBasis($decision, $candidate, $activation, $principal, $binding, $at);

        $authority = [
            'schema' => ProviderBindingActivationRevocationAuthorityContract::SCHEMA,
            'authority_id' => $decision['target']['id'],
            'instance_id' => $decision['instance_id'],
            'source_decision' => [
                'id' => $decisionId,
                'digest' => $decision['record_digest'],
                'schema' => $decision['schema'],
            ],
            'provider_binding_activation' => $candidate['provider_binding_activation'],
            'execution_boundary' => $candidate['execution_boundary'],
            'executor_principal' => $candidate['executor_principal'],
            'provider_binding' => $candidate['provider_binding'],
            'allowed_reason_codes' => $candidate['allowed_reason_codes'],
            'validity' => $candidate['validity'],
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'consumed' => false,
            'continuing_authority' => false,
            'issued_at' => $at->format(DATE_ATOM),
            'sealed' => true,
        ];
        $authority['record_digest'] = hash('sha256', CanonicalJson::encode($authority));
        $this->assertAuthority($authority, $decision, $candidate);

        return $this->commit($decision, $authority, $contract, $at);
    }

    private function commit(
        array $decision,
        array $authority,
        string $contract,
        \DateTimeImmutable $at,
    ): array {
        $permission = $decision['issuance_authority'];
        $issuanceId = str_replace('-decision-', '-issuance-', $decision['decision_id']);

        return $this->atomic->run(
            'provider-activation-revocation-authority-issuance:'
                .$permission['authority_id'],
            function () use (
                $decision,
                $authority,
                $contract,
                $at,
                $permission,
                $issuanceId,
            ): array {
                $consumption = $this->consumptions->consume(
                    $permission['authority_id'],
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
                        'id' => $permission['authority_id'],
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
                    'revocation_authority_issued' => true,
                    'principal_installed' => false,
                    'provider_binding_activated' => false,
                    'credential_capability_issued' => false,
                    'credential_resolved' => false,
                    'external_action_performed' => false,
                    'sealed' => true,
                ];
                $issuance['record_digest'] = hash(
                    'sha256',
                    CanonicalJson::encode($issuance),
                );
                $this->contracts->assertIssuance($issuance, $decision, $contract);
                if (true !== $issuance['revocation_authority_issued']) {
                    throw new \RuntimeException('PEB759_REVOCATION_AUTHORITY_ISSUANCE_INVALID');
                }

                return $this->records->put(self::ISSUANCES, $issuanceId, $issuance);
            },
        );
    }

    private function assertBasis(
        array $decision,
        array $candidate,
        array $activation,
        array $principal,
        array $binding,
        \DateTimeImmutable $at,
    ): void {
        $validity = $candidate['validity'];
        $reasons = $candidate['allowed_reason_codes'];
        $candidateDigest = hash('sha256', CanonicalJson::encode($candidate));
        if ($decision['basis'] !== [
                'provider_binding_activation' => $candidate['provider_binding_activation'],
                'execution_boundary' => $candidate['execution_boundary'],
                'executor_principal' => $candidate['executor_principal'],
                'provider_binding' => $candidate['provider_binding'],
            ]
            || $decision['target']['digest'] !== $candidateDigest
            || !$this->intact(
                $activation,
                SingleOperationProviderBindingActivationContract::REQUIRED_FIELDS,
                SingleOperationProviderBindingActivationContract::SCHEMA,
            )
            || 'ACTIVATED_UNCONSUMED' !== $activation['status']
            || !$this->intact(
                $principal,
                ProviderExecutorPrincipalContract::REQUIRED_FIELDS,
                ProviderExecutorPrincipalContract::SCHEMA,
            )
            || !$this->intact(
                $binding,
                ProviderImplementationBindingContract::REQUIRED_FIELDS,
                ProviderImplementationBindingContract::SCHEMA,
            )
            || $activation['execution_boundary'] !== $candidate['execution_boundary']
            || $activation['executor_principal'] !== $candidate['executor_principal']
            || $activation['provider_binding'] !== $candidate['provider_binding']
            || $decision['instance_id'] !== $activation['instance_id']
            || $decision['instance_id'] !== $principal['instance_id']
            || $decision['instance_id'] !== $binding['instance_id']
            || !is_array($reasons)
            || [] === $reasons
            || $reasons !== array_values(array_unique($reasons))
            || [] !== array_diff($reasons, ProviderBindingActivationRevocationContract::REASON_CODES)
            || ProviderBindingActivationRevocationAuthorityContract::REQUIRED_VALIDITY_FIELDS
                !== array_keys($validity)
            || new \DateTimeImmutable($validity['effective_at']) > $at
            || new \DateTimeImmutable($validity['expires_at']) <= $at
            || new \DateTimeImmutable($validity['expires_at'])
                > new \DateTimeImmutable($decision['expires_at'])
            || new \DateTimeImmutable($validity['expires_at'])
                > new \DateTimeImmutable($activation['expires_at'])
            || new \DateTimeImmutable($validity['expires_at'])
                > new \DateTimeImmutable($principal['validity']['expires_at'])
            || new \DateTimeImmutable($validity['expires_at'])
                > new \DateTimeImmutable($binding['validity']['expires_at'])
            || null !== $validity['revocation_reference']) {
            throw new \RuntimeException('PEB758_REVOCATION_AUTHORITY_BASIS_INVALID');
        }
    }

    private function assertAuthority(
        array $authority,
        array $decision,
        array $candidate,
    ): void {
        $plain = $authority;
        $digest = $plain['record_digest'];
        unset($plain['record_digest']);
        if (ProviderBindingActivationRevocationAuthorityContract::REQUIRED_FIELDS
                !== array_keys($authority)
            || $decision['target']['id'] !== $authority['authority_id']
            || $decision['target']['schema'] !== $authority['schema']
            || $decision['target']['digest']
                !== hash('sha256', CanonicalJson::encode($candidate))
            || true !== $authority['authority_single_use']
            || true !== $authority['authority_exercisable']
            || false !== $authority['consumed']
            || false !== $authority['continuing_authority']
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException('PEB759_REVOCATION_AUTHORITY_ISSUANCE_INVALID');
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
}
