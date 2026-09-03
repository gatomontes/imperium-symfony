<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3Contract as V3;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3ContractValidator as Validator;

/** Builds the selected La Cortine result. Only its complete native publication is effective. */
final readonly class NativeAdmission
{
    public function __construct(private NativeState $state) {}

    public function records(string $authorityId, int $at): array
    {
        $chain = (new NativeAuthority($this->state))->load($authorityId, $at);
        if (null === $chain['decision']['successor']) { throw new \RuntimeException('NIR_EXACT_SUCCESSOR_REQUIRED'); }
        $s = (new NativeSuccessor($this->state))->load($chain['decision']['successor']['id'], $at);
        $successor = $s['successor']; $root = $chain['authority']['issuance_target']['root'];
        if ($chain['decision']['successor'] !== NativeState::ref($successor, 'successor_id')
            || $chain['decision']['creation_winner'] !== NativeState::ref($s['creation_winner'], 'winner_boundary_id')
            || $root !== $successor['replay_contention_root']['root_id']) { throw new \RuntimeException('NIR_ADMISSION_SOURCE_JOIN'); }
        $consumption = NativeState::seal(['schema' => 'imperium.la-cortine.native-transition-consumption/v1', 'consumption_id' => 'consumption-'.$root,
            'authority' => NativeState::ref($chain['authority'], 'authority_id'), 'root' => $root,
            'consumer' => TransitionContract::CONSUMER, 'consumed' => true, 'at' => $at, 'continuing_authority' => false]);
        $target = NativeState::seal(['schema' => 'imperium.la-cortine.native-successor-adoption-target/v1', 'adoption_target_id' => 'adoption-'.$root,
            'successor' => NativeState::ref($successor, 'successor_id'), 'authority_id' => $authorityId,
            'replay_contention_root' => $root, 'operation_scope' => $successor['operation_scope']]);
        $admission = NativeState::seal(['schema' => V3::SCHEMA, 'admission_boundary_id' => 'admission-'.$root,
            'instance_id' => $successor['instance_id'], 'completed_successor' => NativeState::ref($successor, 'successor_id'),
            'atomic_creation_winner' => NativeState::ref($s['creation_winner'], 'winner_boundary_id'),
            'adoption_target' => NativeState::ref($target, 'adoption_target_id'), 'executor_principal' => $successor['active_principal_activation'],
            'execution_boundary' => $successor['execution_boundary'], 'operation_scope' => $successor['operation_scope'],
            'replay_contention_root' => $root, 'legacy_activation_substitution_permitted' => false, 'successor_synthesis_permitted' => false,
            'original_binding_mutation_permitted' => false, 'credential_resolution_permitted' => false, 'provider_invocation_permitted' => false,
            'external_io_permitted' => false, 'effect_start_permitted' => false, 'execution_admitted' => true,
            'live_adoption_performed' => true, 'continuing_authority' => false, 'status' => V3::RESULT_STATUS, 'sealed' => true]);
        (new Validator())->assertResult($admission);
        $adoption = NativeState::seal(['schema' => 'imperium.la-cortine.native-successor-adoption/v1', 'adoption_id' => 'adopted-'.$root,
            'target' => $target, 'consumption' => NativeState::ref($consumption, 'consumption_id'),
            'admission' => NativeState::ref($admission, 'admission_boundary_id'), 'status' => 'ADOPTED_PRE_EFFECT']);
        $source = NativeState::seal(['schema' => 'imperium.la-cortine.native-source-binding-transition/v1', 'binding' => $successor['provider_binding_descriptor'],
            'root' => $root, 'operation' => $successor['operation_scope']['operation'], 'descriptor_status' => 'BOUND_INACTIVE', 'original_binding_mutated' => false]);
        $activation = NativeState::seal(['schema' => 'imperium.la-cortine.native-successor-binding-activation/v1', 'successor' => NativeState::ref($successor, 'successor_id'),
            'root' => $root, 'operation' => $successor['operation_scope']['operation'], 'status' => 'BOUND_ACTIVE_FOR_EXACT_OPERATION', 'provider_effect_started' => false]);
        $records = ['authority_consumption' => $consumption, 'v3_admission' => $admission, 'adoption_join' => $adoption,
            'source_binding_transition' => $source, 'successor_binding_activation' => $activation];
        $winner = NativeState::seal(['schema' => 'imperium.la-cortine.native-transition-winner/v1', 'winner_id' => 'winner-'.$root,
            'root' => $root, 'records_digest' => TransitionContract::digest($records), 'at' => $at]);
        $receipt = NativeState::seal(['schema' => 'imperium.la-cortine.native-transition-receipt/v1', 'receipt_id' => 'receipt-'.$root,
            'root' => $root, 'winner' => NativeState::ref($winner, 'winner_id'), 'authority_id' => $authorityId,
            'successor' => NativeState::ref($successor, 'successor_id'), 'operation' => $successor['operation_scope']['operation'],
            'at' => $at, 'outcome' => 'COMMITTED_PRE_EFFECT', 'provider_invoked' => false, 'external_io_started' => false, 'retry_authorized' => false]);
        return [...$records, 'winner_target' => $winner, 'receipt_target' => $receipt];
    }
}
