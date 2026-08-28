<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionModelSelectionDecisionService
{
    private string $recommendations;
    private string $occupancy;
    private string $decisions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->recommendations = $root.'/var/imperium/offices/oracle/model-recommendations';
        $this->occupancy = $root.'/var/imperium/offices/curia/occupancy';
        $this->decisions = $root.'/var/imperium/offices/curia/delegate-mission-model-selection-decisions';
    }

    public function decide(string $recommendationId, string $authorityId, string $bindingId, string $disposition, ?string $model, array $configuration, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^oracle-model-recommendation-[a-f0-9]{20}$/', $recommendationId)) {
            throw new \InvalidArgumentException('C300_DELEGATE_MODEL_RECOMMENDATION_ID_INVALID');
        }

        $disposition = strtoupper(trim($disposition));
        $rationale = trim($rationale);
        if (!in_array($disposition, ['SELECT_ELIGIBLE_MODEL', 'REJECT_ALL_MODELS', 'RETURN_NEW_COMMISSION'], true) || '' === $rationale) {
            throw new \InvalidArgumentException('C301_DELEGATE_MODEL_SELECTION_DISPOSITION_INVALID');
        }

        return DelegateMissionModelGovernanceAuthorityTransition::run(
            $this->decisions,
            $authorityId,
            fn (): array => $this->decideLocked($recommendationId, $authorityId, $bindingId, $disposition, $model, $configuration, $rationale, $decidedAt),
        );
    }

    private function decideLocked(string $recommendationId, string $authorityId, string $bindingId, string $disposition, ?string $model, array $configuration, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        $recommendation = $this->read($this->recommendations.'/'.$recommendationId.'.json', 'C302_DELEGATE_MODEL_RECOMMENDATION_ABSENT');
        $seneschal = $this->read($this->occupancy.'/'.$bindingId.'.json', 'C303_DELEGATE_MODEL_SENESCHAL_ABSENT');
        foreach (glob($this->decisions.'/*.json') ?: [] as $path) {
            $existing = $this->read($path, 'C309_DELEGATE_MODEL_SELECTION_CONFLICT');
            if (($existing['source_recommendation']['id'] ?? null) === $recommendationId) {
                if (!$this->validDigest($existing) || !DelegateMissionModelGovernanceAuthorityTransition::isExactOrHistorical($existing)) {
                    throw new \RuntimeException('C309_DELEGATE_MODEL_SELECTION_CONFLICT');
                }
                return $existing;
            }
        }

        $authority = $recommendation['curia_selection_decision_authority'] ?? [];
        $eligible = $authority['eligible_models'] ?? [];
        $runtimeBindings = $recommendation['runtime_bindings'] ?? [];
        $selected = 'SELECT_ELIGIBLE_MODEL' === $disposition;
        if (!$this->validDigest($recommendation) || !$this->validDigest($seneschal)
            || 'imperium.oracle-model-recommendation/v1' !== ($recommendation['schema'] ?? null)
            || 'ORACLE_MODEL_RECOMMENDATION_SEALED_PENDING_CURIA_SELECTION_DECISION' !== ($recommendation['status'] ?? null)
            || $authorityId !== ($authority['authority_id'] ?? null)
            || true !== ($authority['selection_decision_authority'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || !in_array($disposition, $authority['permitted_decisions'] ?? [], true)
            || $eligible !== ($recommendation['eligible_models'] ?? null)
            || array_keys($runtimeBindings) !== $eligible
            || true === ($recommendation['selection_authority'] ?? null)
            || 'imperium.curia-seneschal-occupancy/v1' !== ($seneschal['schema'] ?? null)
            || $bindingId !== ($seneschal['binding_id'] ?? null) || 'ACTIVE' !== ($seneschal['status'] ?? null)
            || true !== ($seneschal['delegate_mission_model_selection_decision_authority'] ?? null)
            || true === ($seneschal['execution_authority'] ?? null)
            || ($selected && (!is_string($model) || !in_array($model, $eligible, true)))
            || ($selected && !$this->validRuntimeBinding($runtimeBindings[$model] ?? null))
            || (!$selected && (null !== $model || [] !== $configuration))) {
            throw new \RuntimeException('C304_DELEGATE_MODEL_SELECTION_CHAIN_INVALID');
        }

        $actor = ['seat' => 'curia.seneschal', 'binding_id' => $bindingId, 'binding_digest' => $seneschal['record_digest'], 'manifestation_id' => $seneschal['manifestation_id'], 'occupancy_generation' => $seneschal['occupancy_generation']];
        $decisionId = 'delegate-mission-model-selection-decision-'.substr(hash('sha256', CanonicalJson::encode([$recommendationId, $recommendation['record_digest'], $authorityId, $actor, $disposition, $model, $configuration, $rationale])), 0, 20);
        $sealAuthorityId = $selected ? 'delegate-mission-model-binding-sealing-authority-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $model, $configuration, $recommendation['commission']])), 0, 20) : null;

        return DelegateMissionModelGovernanceAuthorityTransition::put($this->decisions, $decisionId, [
            'schema' => 'imperium.curia-delegate-mission-model-selection-decision/v1', 'decision_id' => $decisionId, 'instance_id' => $seneschal['instance_id'], 'decision_maker' => $actor,
            'source_recommendation' => ['id' => $recommendationId, 'digest' => $recommendation['record_digest']], 'source_commission' => $recommendation['commission'], 'source_comparative_assessment' => $recommendation['comparative_assessment'],
            'eligible_models' => $eligible, 'runtime_bindings' => $runtimeBindings, 'recommended_model' => $recommendation['recommended_model'], 'disposition' => $disposition, 'selected_model' => $selected ? $model : null, 'selected_runtime_binding' => $selected ? $runtimeBindings[$model] : null, 'configuration' => $selected ? $configuration : [], 'rationale' => $rationale,
            'selection_authority' => ['id' => $authorityId, 'consumed' => true, 'continuing_authority' => false], 'decided_at' => $decidedAt->format(DATE_ATOM),
            'status' => $selected ? 'DELEGATE_MISSION_MODEL_SELECTED_PENDING_CONSCRIPTION_BINDING_SEAL' : 'DELEGATE_MISSION_MODEL_NOT_SELECTED', 'model_selected' => $selected,
            'model_binding_sealing_authority' => $selected ? ['authority_id' => $sealAuthorityId, 'authority_single_use' => true, 'authority_exercisable' => true, 'holder' => 'conscription.recruiter', 'purpose' => 'SEAL_EXACT_SELECTED_MODEL_TO_DELEGATE_MISSION_TURN_ONE', 'consumed' => false, 'continuing_authority' => false] : null,
            'model_assignment_authority' => false, 'profile_mutation_authority' => false, 'credential_release_authority' => false, 'provider_invocation_authority' => false, 'resource_authority' => false, 'external_action_authority' => false, 'execution_authority' => false, 'sealed' => true,
        ], self::class, 'C305_DELEGATE_MODEL_SELECTION_WRITE_FAILED', 'C309_DELEGATE_MODEL_SELECTION_CONFLICT');
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function validDigest(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function validRuntimeBinding(mixed $binding): bool
    {
        return is_array($binding)
            && array_keys($binding) === ['provider', 'platform_service', 'runtime_model']
            && is_string($binding['provider']) && '' !== trim($binding['provider'])
            && is_string($binding['platform_service']) && str_starts_with($binding['platform_service'], 'ai.platform.')
            && is_string($binding['runtime_model']) && '' !== trim($binding['runtime_model']);
    }

}
