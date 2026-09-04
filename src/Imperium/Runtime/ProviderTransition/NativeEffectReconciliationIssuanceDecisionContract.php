<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative, authority-empty decision for one exact reconciliation issuance. */
final class NativeEffectReconciliationIssuanceDecisionContract
{
    public const string SCHEMA = 'imperium.imperator.native-effect-reconciliation-issuance-decision/v1';
    public const int VERSION = 1;
    public const string ACT = 'DECIDE_EXACT_RECONCILIATION_AUTHORITY_ISSUANCE';
    public const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED'];
    public const array REQUIRED_FIELDS = [
        'schema', 'decision_id', 'instance_id', 'competent_issuer',
        'competent_issuer_provenance', 'target', 'holder', 'effect_admission',
        'callback_start', 'sealed_response', 'source_native_authority',
        'source_native_principal', 'source_native_transition', 'operator_root_act',
        'act', 'disposition', 'effective_at', 'expires_at', 'replay_identity',
        'single_purpose', 'single_use', 'continuing_authority', 'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'schema', 'digest'];
    public const array REQUIRED_ISSUER_FIELDS = [
        'principal_id', 'principal_version_id', 'generation', 'office', 'seat',
        'competence',
    ];
    public const array COMPETENCE_RULES = [
        'issuer_is_separately_provenanced' => true,
        'issuer_is_current_and_active_at_decision' => true,
        'issuer_has_exact_reconciliation_issuance_competence' => true,
        'decision_is_not_self_issuing' => true,
        'decision_is_not_the_issuance_authority' => true,
        'decision_grants_continuing_authority' => false,
    ];
    public const array NON_AUTHORITIES = [
        'source_provenance', 'issuer_service_possession', 'historical_approval',
        'deterministic_output', 'consumed_native_transition_authority',
    ];

    private function __construct() {}
}
