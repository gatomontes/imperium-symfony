<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Publishes an authority-empty decision and exact issuance grant under native exclusion. */
final readonly class NativeEffectReconciliationIssuanceAuthorizationService
{
    public const string DECISIONS = 'var/imperium/runtime/canonical-native-effect-reconciliation-issuance-decisions';
    public const string AUTHORITIES = 'var/imperium/runtime/canonical-native-effect-reconciliation-issuance-authorities';
    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private NativeEffectReconciliationAuthoritySourceResolver $sources;

    public function __construct(private NativeState $state, private ?\Closure $checkpoint = null)
    {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
        $this->sources = new NativeEffectReconciliationAuthoritySourceResolver($state);
    }

    public function authorize(string $admissionId, int $at, int $expiresAt): array
    {
        return $this->state->locked(function () use ($admissionId, $at, $expiresAt): array {
            $source = $this->sources->resolve($admissionId, $at);
            $targetAuthority = NativeEffectReconciliationAuthorityFactory::build($source, $at, $expiresAt);
            $issuer = [
                'principal_id' => $source['nativePrincipal']['principal_id'],
                'principal_version_id' => $source['nativePrincipal']['principal_version_id'],
                'generation' => $source['nativePrincipal']['principal_generation'],
                'office' => 'imperator',
                'seat' => 'native-transition',
                'competence' => NativeEffectReconciliationIssuanceDecisionContract::ACT,
            ];
            $rootAct = NativeState::seal($source['nativePrincipal']['root_act']['act']);
            $target = [
                'authority_id' => $targetAuthority['authority_id'],
                'authority_schema' => $targetAuthority['schema'],
                'authority_digest' => $targetAuthority['record_digest'],
                'deterministic_receipt_id' => $targetAuthority['deterministic_receipt_id'],
                'effective_at' => $at,
                'expires_at' => $expiresAt,
            ];
            $replay = hash('sha256', implode("\0", [$admissionId, (string) $at, (string) $expiresAt, $target['authority_id']]));
            $decisionId = 'native-effect-reconciliation-issuance-decision-'.$replay;
            $decision = NativeState::seal([
                'schema' => NativeEffectReconciliationIssuanceDecisionContract::SCHEMA,
                'decision_id' => $decisionId,
                'instance_id' => $source['nativePrincipal']['instance_id'],
                'competent_issuer' => $issuer,
                'competent_issuer_provenance' => NativeState::ref($source['nativePrincipal'], 'principal_version_id'),
                'target' => $target,
                'holder' => NativeEffectReconciliationAuthorityV2Contract::HOLDER,
                'effect_admission' => $targetAuthority['effect_admission'],
                'callback_start' => $targetAuthority['callback_start'],
                'sealed_response' => $targetAuthority['sealed_response'],
                'source_native_authority' => $targetAuthority['source_native_authority'],
                'source_native_principal' => $targetAuthority['source_native_principal'],
                'source_native_transition' => $targetAuthority['source_native_transition'],
                'operator_root_act' => NativeState::ref($rootAct, 'act_id'),
                'act' => NativeEffectReconciliationIssuanceDecisionContract::ACT,
                'disposition' => 'AUTHORIZED',
                'effective_at' => $at,
                'expires_at' => $expiresAt,
                'replay_identity' => $replay,
                'single_purpose' => true,
                'single_use' => true,
                'continuing_authority' => false,
                'sealed' => true,
            ]);
            $issuanceAuthorityId = 'native-effect-reconciliation-issuance-authority-'.hash('sha256', $decisionId);
            $issuanceAuthority = NativeState::seal([
                'schema' => NativeEffectReconciliationIssuanceAuthorityContract::SCHEMA,
                'issuance_authority_id' => $issuanceAuthorityId,
                'instance_id' => $source['nativePrincipal']['instance_id'],
                'issuance_decision' => NativeState::ref($decision, 'decision_id'),
                'issuer' => $issuer,
                'holder' => NativeEffectReconciliationAuthorityV2Contract::HOLDER,
                'target' => $target,
                'effect_admission' => $targetAuthority['effect_admission'],
                'callback_start' => $targetAuthority['callback_start'],
                'sealed_response' => $targetAuthority['sealed_response'],
                'source_native_authority' => $targetAuthority['source_native_authority'],
                'source_native_principal' => $targetAuthority['source_native_principal'],
                'source_native_transition' => $targetAuthority['source_native_transition'],
                'operator_root_act' => NativeState::ref($rootAct, 'act_id'),
                'permitted_transition' => NativeEffectReconciliationIssuanceAuthorityContract::PERMITTED_TRANSITION,
                'effective_at' => $at,
                'expires_at' => $expiresAt,
                'replay_identity' => $replay,
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'consumed' => false,
                'continuing_authority' => false,
                'sealed' => true,
            ]);
            $this->cut('currentness.passed');
            return $this->atomic->run('reconciliation-issuance-root:'.hash('sha256', $issuanceAuthorityId), function () use ($decision, $decisionId, $issuanceAuthority, $issuanceAuthorityId): array {
                $storedDecision = $this->records->put(self::DECISIONS, $decisionId, $decision);
                $this->cut('decision.published');
                $storedAuthority = $this->records->put(self::AUTHORITIES, $issuanceAuthorityId, $issuanceAuthority);
                $this->cut('issuance_authority.published');
                return ['decision' => $storedDecision, 'issuance_authority' => $storedAuthority];
            });
        });
    }

    private function cut(string $cut): void
    {
        if (null !== $this->checkpoint) { ($this->checkpoint)($cut); }
    }
}
