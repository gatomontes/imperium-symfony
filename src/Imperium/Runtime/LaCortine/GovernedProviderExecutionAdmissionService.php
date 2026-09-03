<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\ProviderTransition\{NativeBindingReader, NativeState};
use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityContract;
use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceService;
use App\Imperium\Runtime\Imperator\ProviderExecutionBoundaryRedesignInertIssuanceService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GovernedProviderExecutionAdmissionService
{
    public const string ADMISSIONS = 'var/imperium/offices/la-cortine/governed-provider-execution-admissions';

    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private RecordReferenceValidator $references;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
        $this->references = new RecordReferenceValidator($root, new NativeBindingReader(new NativeState($root)));
    }

    public function admit(
        string $authorityId,
        \DateTimeImmutable $at,
    ): array
    {
        $reader = new NativeBindingReader(new NativeState($this->root));
        return $reader->legacy(function () use ($reader, $authorityId, $at): array {
            return $this->legacyAdmit($authorityId, $at);
        });
    }

    private function legacyAdmit(
        string $authorityId,
        \DateTimeImmutable $at,
    ): array
    {
        if (!preg_match(
            '/^durable-provider-execution-authority-[a-z0-9]{1,80}$/',
            $authorityId,
        )) {
            throw new \InvalidArgumentException('PEB600_EXECUTION_AUTHORITY_ID_INVALID');
        }

        $authority = $this->references->read(
            $this->root.'/'.DurableProviderExecutionAuthorityIssuanceService::AUTHORITIES
                .'/'.$authorityId.'.json',
            'PEB601_EXECUTION_AUTHORITY_ABSENT',
        );
        $admissionId = 'governed-provider-execution-admission-'.substr(
            hash('sha256', $authorityId.'|'.($authority['record_digest'] ?? '')),
            0,
            20,
        );

        return $this->atomic->run(
            'governed-provider-execution-admission:'.$authorityId,
            function () use ($authority, $authorityId, $admissionId, $at): array {
                try {
                    $existing = $this->records->read(self::ADMISSIONS, $admissionId);
                    (new NativeBindingReader(new NativeState($this->root)))->assertLegacyRecord($existing);
                    $this->assertExisting($existing, $authority, $authorityId, $admissionId);

                    return $existing;
                } catch (\RuntimeException $exception) {
                    if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $exception->getMessage()) {
                        throw $exception;
                    }
                }

                [$boundary, $principal, $activation, $binding] =
                    $this->resolveAndValidateLineage($authority, $authorityId, $at);
                $authorityReference = [
                    'id' => $authorityId,
                    'digest' => $authority['record_digest'],
                    'schema' => $authority['schema'],
                ];
                $admission = [
                    'schema' => GovernedProviderExecutionAdmissionContract::SCHEMA,
                    'admission_id' => $admissionId,
                    'instance_id' => $authority['instance_id'],
                    'execution_boundary' => $authority['execution_boundary'],
                    'executor_principal' => $authority['executor_principal'],
                    'provider_binding_activation' => $authority['provider_binding_activation'],
                    'provider_binding' => $authority['provider_binding'],
                    'execution_authority' => $authorityReference,
                    'request' => $authority['request'],
                    'authority_consumption' => [
                        'authority_id' => $authorityId,
                        'authority_digest' => $authority['record_digest'],
                        'single_use' => true,
                        'consumed' => true,
                        'continuing_authority' => false,
                        'winner_scope' => 'single-authoritative-root:'.$authorityId,
                    ],
                    'effect_start' => [
                        'checkpoint' => GovernedProviderExecutionAdmissionContract::CHECKPOINT,
                        'local_effect_start_committed' => true,
                        'credential_resolution_permitted_after_checkpoint' => true,
                        'credential_resolved' => false,
                        'external_io_started' => false,
                        'provider_invoked' => false,
                        'automatic_replay_permitted' => false,
                        'exact_admission_continuation_permitted' => true,
                        'outcome' => 'NOT_ATTEMPTED',
                    ],
                    'status' => GovernedProviderExecutionAdmissionContract::STATUS,
                    'admitted_at' => $at->format(DATE_ATOM),
                    'expires_at' => $this->minimumExpiry(
                        $authority['validity']['expires_at'],
                        $principal['validity']['expires_at'],
                        $activation['expires_at'],
                        $binding['validity']['expires_at'],
                    ),
                    'sealed' => true,
                ];
                $admission['record_digest'] = hash(
                    'sha256',
                    CanonicalJson::encode($admission),
                );
                $this->assertAdmission(
                    $admission,
                    $authority,
                    $boundary,
                    $principal,
                    $activation,
                    $binding,
                );

                return $this->records->put(self::ADMISSIONS, $admissionId, $admission);
            },
        );
    }

    private function resolveAndValidateLineage(
        array $authority,
        string $authorityId,
        \DateTimeImmutable $at,
    ): array {
        if (!$this->intact(
            $authority,
            DurableProviderExecutionAuthorityContract::REQUIRED_FIELDS,
            DurableProviderExecutionAuthorityContract::SCHEMA,
        )
            || $authorityId !== ($authority['authority_id'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || false !== ($authority['continuing_authority'] ?? null)
            || new \DateTimeImmutable($authority['validity']['effective_at']) > $at
            || new \DateTimeImmutable($authority['validity']['expires_at']) <= $at
            || null !== ($authority['validity']['revocation_reference'] ?? null)) {
            throw new \RuntimeException('PEB602_EXECUTION_AUTHORITY_INVALID');
        }

        $boundary = $this->resolve(
            ProviderExecutionBoundaryRedesignInertIssuanceService::BOUNDARIES,
            $authority['execution_boundary'],
            'boundary_id',
            'PEB603_EXECUTION_BOUNDARY_ABSENT',
            'PEB604_EXECUTION_BOUNDARY_MISMATCH',
        );
        $principal = $this->resolve(
            ProviderExecutionBoundaryRedesignInertIssuanceService::PRINCIPAL_ATTESTATIONS,
            $authority['executor_principal'],
            'principal_attestation_id',
            'PEB605_EXECUTOR_PRINCIPAL_ABSENT',
            'PEB606_EXECUTOR_PRINCIPAL_MISMATCH',
        );
        $activation = $this->resolve(
            SingleOperationProviderBindingActivationIssuanceService::ACTIVATIONS,
            $authority['provider_binding_activation'],
            'activation_id',
            'PEB607_PROVIDER_ACTIVATION_ABSENT',
            'PEB608_PROVIDER_ACTIVATION_MISMATCH',
        );
        $binding = $this->resolve(
            ProviderImplementationBindingService::BINDINGS,
            $authority['provider_binding'],
            'binding_id',
            'PEB609_PROVIDER_BINDING_ABSENT',
            'PEB610_PROVIDER_BINDING_MISMATCH',
        );

        if (!$this->intact(
            $boundary,
            ProviderExecutionBoundaryContract::REQUIRED_FIELDS,
            ProviderExecutionBoundaryContract::SCHEMA,
        )
            || 'DEFINED_INERT' !== ($boundary['status'] ?? null)
            || [true, true, true, true]
                !== array_values($boundary['admission_ordering'] ?? [])
            || !$this->intact(
                $principal,
                ProviderExecutorPrincipalContract::REQUIRED_FIELDS,
                ProviderExecutorPrincipalContract::SCHEMA,
            )
            || 'ATTESTED_INERT' !== ($principal['status'] ?? null)
            || new \DateTimeImmutable($principal['validity']['effective_at']) > $at
            || new \DateTimeImmutable($principal['validity']['expires_at']) <= $at
            || null !== ($principal['validity']['revocation_reference'] ?? null)
            || !$this->intact(
                $activation,
                SingleOperationProviderBindingActivationContract::REQUIRED_FIELDS,
                SingleOperationProviderBindingActivationContract::SCHEMA,
            )
            || 'ACTIVATED_UNCONSUMED' !== ($activation['status'] ?? null)
            || true !== ($activation['single_operation'] ?? null)
            || new \DateTimeImmutable($activation['expires_at']) <= $at
            || !$this->intact(
                $binding,
                ProviderImplementationBindingContract::REQUIRED_FIELDS,
                ProviderImplementationBindingContract::SCHEMA,
            )
            || 'BOUND_INACTIVE' !== ($binding['status'] ?? null)
            || new \DateTimeImmutable($binding['validity']['effective_at']) > $at
            || new \DateTimeImmutable($binding['validity']['expires_at']) <= $at
            || $authority['instance_id'] !== $boundary['instance_id']
            || $authority['instance_id'] !== $principal['instance_id']
            || $authority['instance_id'] !== $activation['instance_id']
            || $authority['instance_id'] !== $binding['instance_id']
            || $activation['execution_boundary'] !== $authority['execution_boundary']
            || $activation['executor_principal'] !== $authority['executor_principal']
            || $activation['provider_binding'] !== $authority['provider_binding']
            || $activation['tool_authority'] !== $authority['tool_authority']
            || $activation['effect_authorization'] !== $authority['effect_authorization']
            || $activation['destination_policy'] !== $authority['destination_policy']
            || $activation['assurance_profile'] !== $authority['assurance_profile']
            || $activation['scope']['execution_id'] !== $authority['scope']['execution_id']
            || $activation['scope']['provider_id'] !== $authority['scope']['provider_id']
            || $activation['scope']['adapter_id'] !== $authority['scope']['adapter_id']
            || $activation['request'] !== [
                'request_id' => $authority['request']['request_id'],
                'operation' => $authority['request']['operation'],
                'exact_destination' => $authority['request']['exact_destination'],
                'payload_digest' => $authority['request']['payload_digest'],
                'request_fingerprint' => $authority['request']['request_fingerprint'],
            ]
            || true !== ($principal['competence']['same_process_execution_required'] ?? null)
            || $principal['competence']['credential_family']
                !== $authority['scope']['credential_family']
            || $binding['credential_family']['family_id']
                !== $authority['scope']['credential_family']
            || false !== ($authority['scope']['provider_substitution_permitted'] ?? null)
            || false !== ($authority['scope']['payload_substitution_permitted'] ?? null)
            || false !== ($authority['scope']['destination_substitution_permitted'] ?? null)) {
            throw new \RuntimeException('PEB611_EXECUTION_LINEAGE_INVALID');
        }

        return [$boundary, $principal, $activation, $binding];
    }

    private function assertAdmission(
        array $admission,
        array $authority,
        array $boundary,
        array $principal,
        array $activation,
        array $binding,
    ): void {
        if (GovernedProviderExecutionAdmissionContract::REQUIRED_FIELDS
                !== array_keys($admission)
            || GovernedProviderExecutionAdmissionContract::SCHEMA
                !== ($admission['schema'] ?? null)
            || GovernedProviderExecutionAdmissionContract::REQUIRED_REFERENCE_FIELDS
                !== array_keys($admission['execution_authority'] ?? [])
            || GovernedProviderExecutionAdmissionContract::REQUIRED_REQUEST_FIELDS
                !== array_keys($admission['request'] ?? [])
            || GovernedProviderExecutionAdmissionContract::REQUIRED_AUTHORITY_CONSUMPTION_FIELDS
                !== array_keys($admission['authority_consumption'] ?? [])
            || GovernedProviderExecutionAdmissionContract::REQUIRED_EFFECT_START_FIELDS
                !== array_keys($admission['effect_start'] ?? [])
            || GovernedProviderExecutionAdmissionContract::STATUS
                !== ($admission['status'] ?? null)
            || GovernedProviderExecutionAdmissionContract::CHECKPOINT
                !== ($admission['effect_start']['checkpoint'] ?? null)
            || true !== ($admission['authority_consumption']['consumed'] ?? null)
            || false !== ($admission['authority_consumption']['continuing_authority'] ?? null)
            || true !== ($admission['effect_start']['local_effect_start_committed'] ?? null)
            || true !== ($admission['effect_start']['credential_resolution_permitted_after_checkpoint'] ?? null)
            || false !== ($admission['effect_start']['credential_resolved'] ?? null)
            || false !== ($admission['effect_start']['external_io_started'] ?? null)
            || false !== ($admission['effect_start']['provider_invoked'] ?? null)
            || false !== ($admission['effect_start']['automatic_replay_permitted'] ?? null)
            || true !== ($admission['effect_start']['exact_admission_continuation_permitted'] ?? null)
            || 'NOT_ATTEMPTED' !== ($admission['effect_start']['outcome'] ?? null)
            || $admission['execution_boundary'] !== $authority['execution_boundary']
            || $admission['executor_principal'] !== $authority['executor_principal']
            || $admission['provider_binding_activation']
                !== $authority['provider_binding_activation']
            || $admission['provider_binding'] !== $authority['provider_binding']
            || $admission['request'] !== $authority['request']
            || $authority['instance_id'] !== $boundary['instance_id']
            || $authority['instance_id'] !== $principal['instance_id']
            || $authority['instance_id'] !== $activation['instance_id']
            || $authority['instance_id'] !== $binding['instance_id']) {
            throw new \RuntimeException('PEB612_EXECUTION_ADMISSION_INVALID');
        }
    }

    private function assertExisting(
        array $admission,
        array $authority,
        string $authorityId,
        string $admissionId,
    ): void {
        if (!$this->intact(
            $admission,
            GovernedProviderExecutionAdmissionContract::REQUIRED_FIELDS,
            GovernedProviderExecutionAdmissionContract::SCHEMA,
        )
            || $admissionId !== ($admission['admission_id'] ?? null)
            || $authorityId !== ($admission['execution_authority']['id'] ?? null)
            || $authority['record_digest']
                !== ($admission['execution_authority']['digest'] ?? null)
            || true !== ($admission['authority_consumption']['consumed'] ?? null)
            || GovernedProviderExecutionAdmissionContract::CHECKPOINT
                !== ($admission['effect_start']['checkpoint'] ?? null)) {
            throw new \RuntimeException('PEB613_EXECUTION_ADMISSION_CONFLICT');
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
