<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderExecutorPrincipalActivationCanonicalAggregateReconstructor
{
    public const array CLASSIFICATIONS = [
        'READY_OFFLINE_ACTIVATION_INPUT',
        'INCOMPLETE',
        'CONFLICTED',
        'REFUSED',
    ];

    private ImmutableRecordStore $records;
    private ProviderExecutorPrincipalActivationCanonicalContractValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->validator = new ProviderExecutorPrincipalActivationCanonicalContractValidator();
    }

    public function reconstruct(
        string $admissionId,
        string $inputId,
        array $production,
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        if (!$this->identifier($admissionId) || !$this->identifier($inputId)) {
            return $this->result('REFUSED', [], ['IDENTIFIER_INVALID']);
        }

        $admissionRead = $this->read(
            ProviderExecutorPrincipalActivationCanonicalFixtureStore::RESOLUTION_ADMISSIONS,
            $admissionId,
        );
        if (null !== $admissionRead['classification']) {
            return $this->result($admissionRead['classification'], [], $admissionRead['reasons']);
        }
        $admission = $admissionRead['record'];

        try {
            $this->validator->assertResolutionAdmission(
                $admission,
                $production,
                $decision,
                $attestation,
                $assurance,
                $boundary,
                $at,
            );
        } catch (\RuntimeException $exception) {
            return $this->result('REFUSED', [], [$exception->getMessage()]);
        }

        $inputRead = $this->read(
            ProviderExecutorPrincipalActivationCanonicalFixtureStore::ACTIVATION_INPUTS,
            $inputId,
        );
        if (null !== $inputRead['classification']) {
            return $this->result($inputRead['classification'], [], $inputRead['reasons']);
        }
        $input = $inputRead['record'];

        try {
            $this->validator->assertActivationInput(
                $input,
                $admission,
                $production,
                $decision,
                $attestation,
                $assurance,
                $boundary,
                $at,
            );
        } catch (\RuntimeException $exception) {
            return $this->result('REFUSED', [], [$exception->getMessage()]);
        }

        $chain = [
            'production' => $this->reference($production, 'production_id'),
            'decision' => $this->reference($decision, 'decision_id'),
            'principal_attestation' => $this->reference($attestation, 'principal_attestation_id'),
            'provider_assurance_admission' => $this->reference($assurance, 'admission_id'),
            'execution_boundary' => $this->reference($boundary, 'boundary_id'),
            'resolution_admission' => $this->reference($admission, 'resolution_admission_id'),
            'activation_input' => $this->reference($input, 'input_id'),
            'activation_target' => $input['activation_target'],
            'activation_authority' => $input['activation_authority'],
            'replay_contention_root' => $input['replay_contention_root'],
        ];

        return $this->result('READY_OFFLINE_ACTIVATION_INPUT', $chain, []);
    }

    private function read(string $directory, string $id): array
    {
        try {
            return [
                'record' => $this->records->read($directory, $id),
                'classification' => null,
                'reasons' => [],
            ];
        } catch (\RuntimeException $exception) {
            if ('PST112_IMMUTABLE_RECORD_ABSENT' === $exception->getMessage()) {
                return [
                    'record' => null,
                    'classification' => 'INCOMPLETE',
                    'reasons' => ['FIXTURE_ABSENT'],
                ];
            }

            return [
                'record' => null,
                'classification' => 'CONFLICTED',
                'reasons' => [$exception->getMessage()],
            ];
        }
    }

    private function result(string $classification, array $chain, array $reasons): array
    {
        return [
            'classification' => $classification,
            'chain' => $chain,
            'reasons' => $reasons,
            'proof_digest' => hash('sha256', CanonicalJson::encode([
                'classification' => $classification,
                'chain' => $chain,
                'reasons' => $reasons,
            ])),
            'read_only' => true,
            'fixture_created' => false,
            'fixture_repaired' => false,
            'resolution_admission_created' => false,
            'activation_input_created' => false,
            'activation_winner_created' => false,
            'activation_authority_issued' => false,
            'activation_authority_consumed' => false,
            'principal_activated' => false,
            'provider_binding_activated' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_io_started' => false,
            'retry_authority_created' => false,
        ];
    }

    private function reference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function identifier(string $value): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9._:\/-]{2,220}$/', $value);
    }
}
