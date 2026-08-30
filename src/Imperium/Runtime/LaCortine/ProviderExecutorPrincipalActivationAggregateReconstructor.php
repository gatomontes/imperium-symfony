<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderExecutorPrincipalActivationAggregateReconstructor
{
    public const array CLASSIFICATIONS = [
        'ELIGIBLE_OFFLINE_EVIDENCE',
        'INCOMPLETE',
        'CONFLICTED',
        'REFUSED',
    ];

    private ImmutableRecordStore $records;
    private ProviderExecutorPrincipalActivationContractValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->validator = new ProviderExecutorPrincipalActivationContractValidator();
    }

    public function reconstruct(
        string $decisionId,
        string $activationId,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        if (!$this->identifier($decisionId) || !$this->identifier($activationId)) {
            return $this->result('REFUSED', [], ['IDENTIFIER_INVALID']);
        }

        $decisionRead = $this->read(
            ProviderExecutorPrincipalActivationFixtureStore::DECISIONS,
            $decisionId,
        );
        if (null !== $decisionRead['classification']) {
            return $this->result(
                $decisionRead['classification'],
                [],
                $decisionRead['reasons'],
            );
        }
        $decision = $decisionRead['record'];

        $decidedAt = \DateTimeImmutable::createFromFormat(
            DATE_ATOM,
            (string) ($decision['decided_at'] ?? ''),
        );
        if (false === $decidedAt
            || $decidedAt->format(DATE_ATOM) !== ($decision['decided_at'] ?? null)) {
            return $this->result('REFUSED', [], ['PEA700_ACTIVATION_DECISION_INVALID']);
        }

        try {
            $this->validator->assertDecision(
                $decision,
                $attestation,
                $assurance,
                $boundary,
                $decidedAt,
            );
        } catch (\RuntimeException $exception) {
            return $this->result('REFUSED', [], [$exception->getMessage()]);
        }

        if ('AUTHORIZED' !== $decision['disposition']) {
            return $this->result(
                'REFUSED',
                ['decision' => $this->reference($decision, 'decision_id')],
                ['ACTIVATION_DECISION_REFUSED'],
            );
        }

        $activationRead = $this->read(
            ProviderExecutorPrincipalActivationFixtureStore::ACTIVATIONS,
            $activationId,
        );
        if (null !== $activationRead['classification']) {
            return $this->result(
                $activationRead['classification'],
                ['decision' => $this->reference($decision, 'decision_id')],
                $activationRead['reasons'],
            );
        }
        $activation = $activationRead['record'];

        try {
            $this->validator->assertActivation(
                $activation,
                $decision,
                $attestation,
                $assurance,
                $boundary,
                $at,
            );
        } catch (\RuntimeException $exception) {
            return $this->result('REFUSED', [], [$exception->getMessage()]);
        }

        if ('ACTIVE' !== $activation['status']) {
            return $this->result(
                'REFUSED',
                [
                    'decision' => $this->reference($decision, 'decision_id'),
                    'activation' => $this->reference(
                        $activation,
                        'principal_activation_id',
                    ),
                ],
                ['PRINCIPAL_ACTIVATION_NOT_ACTIVE'],
            );
        }

        return $this->result(
            'ELIGIBLE_OFFLINE_EVIDENCE',
            [
                'decision' => $this->reference($decision, 'decision_id'),
                'activation' => $this->reference(
                    $activation,
                    'principal_activation_id',
                ),
                'principal_attestation' => $activation['principal_attestation'],
                'provider_assurance_admission' =>
                    $activation['provider_assurance_admission'],
                'execution_boundary' => $activation['execution_boundary'],
            ],
            [],
        );
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
            'read_only' => true,
            'fixture_created' => false,
            'fixture_repaired' => false,
            'principal_activated' => false,
            'principal_reactivated' => false,
            'activation_authority_created' => false,
            'activation_authority_consumed' => false,
            'execution_authority_created' => false,
            'execution_authority_consumed' => false,
            'provider_binding_activated' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_io_performed' => false,
            'retry_authorized' => false,
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
