<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderBindingSuccessorProductionAdoptionAggregateReconstructor
{
    public const array CLASSIFICATIONS = [
        'ELIGIBLE_OFFLINE_PRODUCTION_ADOPTION_EVIDENCE',
        'INCOMPLETE',
        'CONFLICTED',
        'REFUSED',
    ];

    private ImmutableRecordStore $records;
    private ProviderBindingSuccessorProductionAdoptionContractValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->validator = new ProviderBindingSuccessorProductionAdoptionContractValidator();
    }

    public function reconstruct(
        string $rootId,
        array $decisionAuthority,
        array $reconciledTarget,
        array $reconciledInput,
        array $completedSuccessor,
        array $principal,
        array $binding,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        if (!$this->identifier($rootId)) {
            return $this->result('REFUSED', [], ['ROOT_IDENTIFIER_INVALID']);
        }

        $decisionRead = $this->read(
            ProviderBindingSuccessorProductionAdoptionFixtureStore::DECISIONS,
            $rootId,
        );
        if (null !== $decisionRead['classification']) {
            return $this->result($decisionRead['classification'], [], $decisionRead['reasons']);
        }
        $decision = $decisionRead['record'];
        try {
            $this->validator->assertDecision(
                $decision, $decisionAuthority, $reconciledTarget, $reconciledInput,
                $principal, $binding, $assurance, $boundary, $at,
            );
        } catch (\RuntimeException $exception) {
            return $this->result('REFUSED', [], [$exception->getMessage()]);
        }

        $authorityRead = $this->read(
            ProviderBindingSuccessorProductionAdoptionFixtureStore::AUTHORITIES,
            $rootId,
        );
        if (null !== $authorityRead['classification']) {
            return $this->result($authorityRead['classification'], [], $authorityRead['reasons']);
        }
        $authority = $authorityRead['record'];
        try {
            $this->validator->assertAuthority(
                $authority, $decision, $decisionAuthority, $reconciledTarget,
                $reconciledInput, $principal, $binding, $assurance, $boundary, $at,
            );
        } catch (\RuntimeException $exception) {
            return $this->result('REFUSED', [], [$exception->getMessage()]);
        }

        $adoptionRead = $this->read(
            ProviderBindingSuccessorProductionAdoptionFixtureStore::ADOPTION_TARGETS,
            $rootId,
        );
        if (null !== $adoptionRead['classification']) {
            return $this->result($adoptionRead['classification'], [], $adoptionRead['reasons']);
        }
        $adoption = $adoptionRead['record'];
        try {
            $this->validator->assertAdoptionTarget(
                $adoption, $completedSuccessor, $reconciledInput, $reconciledTarget,
                $principal, $binding, $assurance, $boundary, $at,
            );
        } catch (\RuntimeException $exception) {
            return $this->result('REFUSED', [], [$exception->getMessage()]);
        }

        return $this->result(
            'ELIGIBLE_OFFLINE_PRODUCTION_ADOPTION_EVIDENCE',
            [
                'decision_authority' => $this->reference($decisionAuthority, 'authority_id'),
                'reconciled_target' => $this->reference($reconciledTarget, 'target_id'),
                'reconciled_decision_input' => $this->reference($reconciledInput, 'decision_input_id'),
                'completed_successor' => $this->reference($completedSuccessor, 'successor_id'),
                'production_decision' => $this->reference($decision, 'decision_id'),
                'successor_creation_authority' => $this->reference($authority, 'authority_id'),
                'adoption_target' => $this->reference($adoption, 'adoption_target_id'),
                'operation_scope' => $completedSuccessor['operation_scope'],
                'replay_contention_root' => $completedSuccessor['replay_contention_root'],
                'required_admission_contract' => $adoption['required_admission_contract'],
            ],
            [],
        );
    }

    private function read(string $directory, string $rootId): array
    {
        try {
            return [
                'record' => $this->records->read($directory, $rootId),
                'classification' => null,
                'reasons' => [],
            ];
        } catch (\RuntimeException $exception) {
            if ('PST112_IMMUTABLE_RECORD_ABSENT' === $exception->getMessage()) {
                return ['record' => null, 'classification' => 'INCOMPLETE', 'reasons' => ['FIXTURE_ABSENT']];
            }

            return ['record' => null, 'classification' => 'CONFLICTED', 'reasons' => [$exception->getMessage()]];
        }
    }

    private function result(string $classification, array $chain, array $reasons): array
    {
        $proof = ['classification' => $classification, 'chain' => $chain, 'reasons' => $reasons];

        return [
            ...$proof,
            'proof_digest' => hash('sha256', CanonicalJson::encode($proof)),
            'read_only' => true,
            'fixture_created' => false,
            'fixture_repaired' => false,
            'artifact_replaced' => false,
            'artifact_promoted' => false,
            'production_decision_created' => false,
            'successor_creation_authority_issued' => false,
            'successor_creation_authority_consumed' => false,
            'successor_created' => false,
            'adoption_decided' => false,
            'live_adoption_performed' => false,
            'execution_admission_changed' => false,
            'provider_binding_activated' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_io_started' => false,
            'provider_effect_started' => false,
            'retry_authority_created' => false,
            'continuing_authority' => false,
        ];
    }

    private function reference(array $record, string $idField): array
    {
        return ['id' => $record[$idField], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }

    private function identifier(string $value): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9._:\\/-]{2,220}$/', $value);
    }
}
