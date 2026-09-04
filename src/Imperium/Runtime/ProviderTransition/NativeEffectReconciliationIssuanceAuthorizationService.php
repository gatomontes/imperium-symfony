<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Publishes one finite Root/native-provenanced decision and its separate issuance authority. */
final readonly class NativeEffectReconciliationIssuanceAuthorizationService
{
    public const string DECISIONS = 'var/imperium/runtime/canonical-native-effect-reconciliation-issuance-decisions';
    public const string AUTHORITIES = 'var/imperium/runtime/canonical-native-effect-reconciliation-issuance-authorities';
    public const string ISSUER_ID = 'imperator.native-effect-reconciliation-issuance-authorizer';
    public const string HOLDER = 'imperator.native-effect-reconciliation-authority-issuer';
    public const string COMPETENCE = 'ISSUE_EXACT_RECONCILIATION_AUTHORITY';

    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private NativeEffectReconciliationAuthorityIssuanceService $issuer;

    public function __construct(private NativeState $state)
    {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
        $this->issuer = new NativeEffectReconciliationAuthorityIssuanceService($state);
    }

    public function authorize(string $admissionId, int $at, int $expiresAt): array
    {
        $plan = $this->issuer->preview($admissionId, $at, $expiresAt);
        $source = $plan['source'];
        $targetAuthority = $plan['authority'];
        $nativePrincipal = $source['nativePrincipal'];
        $imperator = $this->state->source('principal', $nativePrincipal['source_principal']);
        $issuer = [
            'principal_id' => $imperator['principal_id'],
            'principal_version_id' => $imperator['principal_version_id'],
            'generation' => $imperator['principal_generation'],
            'office' => 'imperator',
            'seat' => self::ISSUER_ID,
            'competence' => self::COMPETENCE,
        ];
        $target = [
            'authority_id' => $targetAuthority['authority_id'],
            'authority_schema' => $targetAuthority['schema'],
            'authority_digest' => $targetAuthority['record_digest'],
            'deterministic_receipt_id' => $targetAuthority['deterministic_receipt_id'],
            'effective_at' => $at,
            'expires_at' => $expiresAt,
        ];
        $replayIdentity = hash('sha256', CanonicalJson::encode([
            $issuer, $target, $source['admission']['record_digest'],
            $source['callback']['record_digest'], $source['response']['record_digest'],
        ]));
        $decisionId = 'native-effect-reconciliation-issuance-decision-'.substr($replayIdentity, 0, 32);
        $rootAct = $nativePrincipal['root_act']['act'];
        $rootReference = [
            'id' => $rootAct['act_id'],
            'schema' => $rootAct['schema'],
            'digest' => hash('sha256', CanonicalJson::encode($nativePrincipal['root_act'])),
        ];
        $decision = NativeState::seal([
            'schema' => NativeEffectReconciliationIssuanceDecisionContract::SCHEMA,
            'decision_id' => $decisionId,
            'instance_id' => $imperator['instance_id'],
            'competent_issuer' => $issuer,
            'competent_issuer_provenance' => NativeState::ref($imperator, 'principal_version_id'),
            'target' => $target,
            'holder' => self::HOLDER,
            'effect_admission' => NativeState::ref($source['admission'], 'admission_id'),
            'callback_start' => NativeState::ref($source['callback'], 'callback_start_id'),
            'sealed_response' => NativeState::ref($source['response'], 'response_id'),
            'source_native_authority' => NativeState::ref($source['nativeAuthority']['authority'], 'authority_id'),
            'source_native_principal' => NativeState::ref($nativePrincipal, 'principal_version_id'),
            'source_native_transition' => NativeState::ref($source['commit'], 'root'),
            'operator_root_act' => $rootReference,
            'act' => NativeEffectReconciliationIssuanceDecisionContract::ACT,
            'disposition' => 'AUTHORIZED',
            'effective_at' => $at,
            'expires_at' => $expiresAt,
            'replay_identity' => $replayIdentity,
            'single_purpose' => true,
            'single_use' => true,
            'continuing_authority' => false,
            'sealed' => true,
        ]);
        $issuanceAuthorityId = 'native-effect-reconciliation-issuance-authority-'.substr(hash('sha256', $decision['record_digest']), 0, 32);
        $issuanceAuthority = NativeState::seal([
            'schema' => NativeEffectReconciliationIssuanceAuthorityContract::SCHEMA,
            'issuance_authority_id' => $issuanceAuthorityId,
            'instance_id' => $imperator['instance_id'],
            'issuance_decision' => NativeState::ref($decision, 'decision_id'),
            'issuer' => $issuer,
            'holder' => self::HOLDER,
            'target' => $target,
            'effect_admission' => $decision['effect_admission'],
            'callback_start' => $decision['callback_start'],
            'sealed_response' => $decision['sealed_response'],
            'source_native_authority' => $decision['source_native_authority'],
            'source_native_principal' => $decision['source_native_principal'],
            'source_native_transition' => $decision['source_native_transition'],
            'operator_root_act' => $rootReference,
            'permitted_transition' => NativeEffectReconciliationIssuanceAuthorityContract::PERMITTED_TRANSITION,
            'effective_at' => $at,
            'expires_at' => $expiresAt,
            'replay_identity' => $replayIdentity,
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'consumed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);

        return $this->atomic->run(
            'reconciliation-issuance-root:'.hash('sha256', $targetAuthority['authority_id']),
            function () use ($decision, $decisionId, $issuanceAuthority, $issuanceAuthorityId): array {
                $storedDecision = $this->records->put(self::DECISIONS, $decisionId, $decision);
                $storedAuthority = $this->records->put(self::AUTHORITIES, $issuanceAuthorityId, $issuanceAuthority);
                return ['decision' => $storedDecision, 'issuance_authority' => $storedAuthority];
            },
        );
    }
}
