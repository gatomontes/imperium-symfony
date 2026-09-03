<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\ProviderTransition\{NativeBindingReader, NativeState};
use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityContract;
use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceService;
use App\Imperium\Runtime\Imperator\ProviderExecutionBoundaryRedesignInertIssuanceService;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionContract;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionService;
use App\Imperium\Runtime\LaCortine\ProviderExecutionBoundaryContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingService;
use App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationContract;
use App\Imperium\Runtime\LaCortine\SingleOperationProviderBindingActivationIssuanceService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GovernedStationaryCredentialResolutionV2Service
{
    public const string PROOFS = 'var/imperium/offices/clavium/stationary-credential-resolution-v2-proofs';

    private const array ENVIRONMENT_CREDENTIALS = [
        'agentmail|agentmail-api-token' => 'AGENTMAIL_API_KEY',
    ];

    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private RecordReferenceValidator $references;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
        $this->references = new RecordReferenceValidator($root, new NativeBindingReader(new NativeState($root)));
    }

    public function prove(
        string $admissionId,
        \DateTimeImmutable $at,
    ): array
    {
        $reader = new NativeBindingReader(new NativeState($this->root));
        return $reader->legacy(function () use ($reader, $admissionId, $at): array {
            return $this->legacyProve($admissionId, $at);
        });
    }

    private function legacyProve(
        string $admissionId,
        \DateTimeImmutable $at,
    ): array
    {
        if (!preg_match(
            '/^governed-provider-execution-combined-admission-[a-f0-9]{20}$/',
            $admissionId,
        )) {
            throw new \InvalidArgumentException('PEB700_EXECUTION_ADMISSION_ID_INVALID');
        }

        $admission = $this->references->read(
            $this->root.'/'.GovernedProviderExecutionCombinedAdmissionService::ADMISSIONS
                .'/'.$admissionId.'.json',
            'PEB701_EXECUTION_ADMISSION_ABSENT',
        );
        $proofId = 'stationary-credential-resolution-proof-'.substr(
            hash('sha256', $admissionId.'|'.($admission['record_digest'] ?? '')),
            0,
            20,
        );

        return $this->atomic->run(
            'stationary-credential-resolution-v2:'.$admissionId,
            function () use ($admission, $admissionId, $proofId, $at): array {
                try {
                    $existing = $this->records->read(self::PROOFS, $proofId);
                    (new NativeBindingReader(new NativeState($this->root)))->assertLegacyRecord($existing);
                    $this->assertExisting($existing, $admission, $admissionId, $proofId);

                    return $existing;
                } catch (\RuntimeException $exception) {
                    if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $exception->getMessage()) {
                        throw $exception;
                    }
                }

                [$authority, $principal, $binding, $activation] =
                    $this->resolveAndValidate($admission, $admissionId, $at);
                $provider = $authority['scope']['provider_id'];
                $family = $authority['scope']['credential_family'];
                $key = $provider.'|'.$family;
                $environmentName = self::ENVIRONMENT_CREDENTIALS[$key] ?? null;
                if (!is_string($environmentName)) {
                    throw new \RuntimeException('PEB710_STATIONARY_CREDENTIAL_SCOPE_UNSUPPORTED');
                }
                $secret = $_SERVER[$environmentName]
                    ?? $_ENV[$environmentName]
                    ?? getenv($environmentName);
                if (!is_string($secret) || '' === $secret) {
                    throw new \RuntimeException('PEB711_STATIONARY_CREDENTIAL_UNAVAILABLE');
                }

                $callbackReached = false;
                $resolved = (static function (string $authentication) use (&$callbackReached): bool {
                    $callbackReached = true;

                    return '' !== $authentication;
                })($secret);
                unset($secret);
                if (!$callbackReached || !$resolved) {
                    throw new \RuntimeException('PEB712_CALLBACK_LOCAL_RESOLUTION_FAILED');
                }

                $proof = [
                    'schema' => StationaryCredentialResolutionProofContract::SCHEMA,
                    'proof_id' => $proofId,
                    'instance_id' => $admission['instance_id'],
                    'provider_execution_admission' => [
                        'id' => $admissionId,
                        'digest' => $admission['record_digest'],
                        'schema' => $admission['schema'],
                    ],
                    'execution_authority' => $admission['execution_authority'],
                    'executor_principal' => $admission['executor_principal'],
                    'provider_binding' => $admission['provider_binding'],
                    'credential_scope' => [
                        'provider_id' => $provider,
                        'credential_family' => $family,
                        'stationary_possession' => true,
                        'same_process_resolution' => true,
                    ],
                    'resolution' => [
                        'checkpoint' => StationaryCredentialResolutionProofContract::CHECKPOINT,
                        'credential_resolved' => true,
                        'callback_local' => true,
                        'secret_exposed_to_caller' => false,
                        'credential_reference_persisted' => false,
                        'credential_secret_persisted' => false,
                        'credential_capability_issued' => false,
                        'credential_capability_reconstructed' => false,
                    ],
                    'effect' => [
                        'provider_invoked' => false,
                        'external_io_started' => false,
                        'outbound_byte_sent' => false,
                        'provider_outcome_claimed' => false,
                    ],
                    'resolved_at' => $at->format(DATE_ATOM),
                    'expires_at' => $this->minimumExpiry(
                        $admission['expires_at'],
                        $authority['validity']['expires_at'],
                        $principal['validity']['expires_at'],
                        $binding['validity']['expires_at'],
                        $activation['expires_at'],
                    ),
                    'sealed' => true,
                ];
                $proof['record_digest'] = hash('sha256', CanonicalJson::encode($proof));
                $this->assertProof($proof, $admission, $authority, $principal, $binding);

                return $this->records->put(self::PROOFS, $proofId, $proof);
            },
        );
    }

    private function resolveAndValidate(
        array $admission,
        string $admissionId,
        \DateTimeImmutable $at,
    ): array {
        if (!$this->intact(
            $admission,
            GovernedProviderExecutionCombinedAdmissionContract::REQUIRED_FIELDS,
            GovernedProviderExecutionCombinedAdmissionContract::SCHEMA,
        )
            || $admissionId !== ($admission['admission_id'] ?? null)
            || GovernedProviderExecutionCombinedAdmissionContract::STATUS
                !== ($admission['status'] ?? null)
            || GovernedProviderExecutionCombinedAdmissionContract::CHECKPOINT
                !== ($admission['effect_start']['checkpoint'] ?? null)
            || true !== ($admission['activation_consumption']['single_operation'] ?? null)
            || true !== ($admission['activation_consumption']['consumed'] ?? null)
            || false !== ($admission['activation_consumption']['continuing_authority'] ?? null)
            || GovernedProviderExecutionCombinedAdmissionContract::REVOCATION_STATUS
                !== ($admission['activation_consumption']['revocation_status'] ?? null)
            || true !== ($admission['authority_consumption']['consumed'] ?? null)
            || true !== ($admission['effect_start']['local_effect_start_committed'] ?? null)
            || true !== ($admission['effect_start']['credential_resolution_permitted_after_checkpoint'] ?? null)
            || false !== ($admission['effect_start']['credential_resolved'] ?? null)
            || false !== ($admission['effect_start']['external_io_started'] ?? null)
            || false !== ($admission['effect_start']['provider_invoked'] ?? null)
            || new \DateTimeImmutable($admission['expires_at']) <= $at) {
            throw new \RuntimeException('PEB702_EXECUTION_ADMISSION_INVALID');
        }

        $authority = $this->resolve(
            DurableProviderExecutionAuthorityIssuanceService::AUTHORITIES,
            $admission['execution_authority'],
            'authority_id',
            'PEB703_EXECUTION_AUTHORITY_ABSENT',
            'PEB704_EXECUTION_AUTHORITY_MISMATCH',
        );
        $activation = $this->resolve(
            SingleOperationProviderBindingActivationIssuanceService::ACTIVATIONS,
            $admission['provider_binding_activation'],
            'activation_id',
            'PEB715_PROVIDER_ACTIVATION_ABSENT',
            'PEB716_PROVIDER_ACTIVATION_MISMATCH',
        );
        $principal = $this->resolve(
            ProviderExecutionBoundaryRedesignInertIssuanceService::PRINCIPAL_ATTESTATIONS,
            $admission['executor_principal'],
            'principal_attestation_id',
            'PEB705_EXECUTOR_PRINCIPAL_ABSENT',
            'PEB706_EXECUTOR_PRINCIPAL_MISMATCH',
        );
        $binding = $this->resolve(
            ProviderImplementationBindingService::BINDINGS,
            $admission['provider_binding'],
            'binding_id',
            'PEB707_PROVIDER_BINDING_ABSENT',
            'PEB708_PROVIDER_BINDING_MISMATCH',
        );
        $boundary = $this->resolve(
            ProviderExecutionBoundaryRedesignInertIssuanceService::BOUNDARIES,
            $admission['execution_boundary'],
            'boundary_id',
            'PEB709_EXECUTION_BOUNDARY_ABSENT',
            'PEB709_EXECUTION_BOUNDARY_MISMATCH',
        );

        if (!$this->intact(
            $authority,
            DurableProviderExecutionAuthorityContract::REQUIRED_FIELDS,
            DurableProviderExecutionAuthorityContract::SCHEMA,
        )
            || !$this->intact(
                $activation,
                SingleOperationProviderBindingActivationContract::REQUIRED_FIELDS,
                SingleOperationProviderBindingActivationContract::SCHEMA,
            )
            || 'ACTIVATED_UNCONSUMED' !== ($activation['status'] ?? null)
            || $activation['activation_id']
                !== ($admission['activation_consumption']['activation_id'] ?? null)
            || $activation['record_digest']
                !== ($admission['activation_consumption']['activation_digest'] ?? null)
            || $authority['provider_binding_activation']
                !== $admission['provider_binding_activation']
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
            || !$this->intact(
                $boundary,
                ProviderExecutionBoundaryContract::REQUIRED_FIELDS,
                ProviderExecutionBoundaryContract::SCHEMA,
            )
            || 'DEFINED_INERT' !== ($boundary['status'] ?? null)
            || 'SAME_PROCESS_GOVERNED_EXECUTOR'
                !== ($boundary['deployment_boundary']['boundary_kind'] ?? null)
            || true !== ($boundary['deployment_boundary']['credential_possession_stationary'] ?? null)
            || false !== ($boundary['deployment_boundary']['cross_process_capability_transfer_required'] ?? null)
            || $authority['executor_principal'] !== $admission['executor_principal']
            || $authority['provider_binding'] !== $admission['provider_binding']
            || $authority['execution_boundary'] !== $admission['execution_boundary']
            || $authority['scope']['provider_id']
                !== $binding['provider_implementation']['provider_id']
            || $authority['scope']['credential_family']
                !== $binding['credential_family']['family_id']
            || $authority['scope']['credential_family']
                !== $principal['competence']['credential_family']
            || true !== ($principal['competence']['same_process_execution_required'] ?? null)
            || new \DateTimeImmutable($authority['validity']['expires_at']) <= $at
            || null !== ($authority['validity']['revocation_reference'] ?? null)
            || new \DateTimeImmutable($principal['validity']['expires_at']) <= $at
            || null !== ($principal['validity']['revocation_reference'] ?? null)
            || new \DateTimeImmutable($binding['validity']['expires_at']) <= $at
            || new \DateTimeImmutable($activation['expires_at']) <= $at) {
            throw new \RuntimeException('PEB709_STATIONARY_CREDENTIAL_LINEAGE_INVALID');
        }

        return [$authority, $principal, $binding, $activation];
    }

    private function assertProof(
        array $proof,
        array $admission,
        array $authority,
        array $principal,
        array $binding,
    ): void {
        if (StationaryCredentialResolutionProofContract::REQUIRED_FIELDS
                !== array_keys($proof)
            || StationaryCredentialResolutionProofContract::SCHEMA
                !== ($proof['schema'] ?? null)
            || StationaryCredentialResolutionProofContract::REQUIRED_CREDENTIAL_SCOPE_FIELDS
                !== array_keys($proof['credential_scope'] ?? [])
            || StationaryCredentialResolutionProofContract::REQUIRED_RESOLUTION_FIELDS
                !== array_keys($proof['resolution'] ?? [])
            || StationaryCredentialResolutionProofContract::REQUIRED_EFFECT_FIELDS
                !== array_keys($proof['effect'] ?? [])
            || StationaryCredentialResolutionProofContract::CHECKPOINT
                !== ($proof['resolution']['checkpoint'] ?? null)
            || true !== ($proof['resolution']['credential_resolved'] ?? null)
            || true !== ($proof['resolution']['callback_local'] ?? null)
            || false !== ($proof['resolution']['secret_exposed_to_caller'] ?? null)
            || false !== ($proof['resolution']['credential_reference_persisted'] ?? null)
            || false !== ($proof['resolution']['credential_secret_persisted'] ?? null)
            || false !== ($proof['resolution']['credential_capability_issued'] ?? null)
            || false !== ($proof['resolution']['credential_capability_reconstructed'] ?? null)
            || false !== ($proof['effect']['provider_invoked'] ?? null)
            || false !== ($proof['effect']['external_io_started'] ?? null)
            || false !== ($proof['effect']['outbound_byte_sent'] ?? null)
            || false !== ($proof['effect']['provider_outcome_claimed'] ?? null)
            || $proof['execution_authority'] !== $admission['execution_authority']
            || $proof['executor_principal'] !== $admission['executor_principal']
            || $proof['provider_binding'] !== $admission['provider_binding']
            || $authority['instance_id'] !== $proof['instance_id']
            || $principal['instance_id'] !== $proof['instance_id']
            || $binding['instance_id'] !== $proof['instance_id']) {
            throw new \RuntimeException('PEB713_STATIONARY_CREDENTIAL_PROOF_INVALID');
        }
    }

    private function assertExisting(
        array $proof,
        array $admission,
        string $admissionId,
        string $proofId,
    ): void {
        if (!$this->intact(
            $proof,
            StationaryCredentialResolutionProofContract::REQUIRED_FIELDS,
            StationaryCredentialResolutionProofContract::SCHEMA,
        )
            || $proofId !== ($proof['proof_id'] ?? null)
            || $admissionId !== ($proof['provider_execution_admission']['id'] ?? null)
            || $admission['record_digest']
                !== ($proof['provider_execution_admission']['digest'] ?? null)
            || true !== ($proof['resolution']['credential_resolved'] ?? null)
            || false !== ($proof['effect']['provider_invoked'] ?? null)) {
            throw new \RuntimeException('PEB714_STATIONARY_CREDENTIAL_PROOF_CONFLICT');
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
