<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative, authority-empty single-use grant for one exact issuance cut. */
final class NativeEffectReconciliationIssuanceAuthorityContract
{
    public const string SCHEMA = 'imperium.imperator.native-effect-reconciliation-issuance-authority/v1';
    public const int VERSION = 1;
    public const string PERMITTED_TRANSITION = 'ISSUE_EXACT_RECONCILIATION_AUTHORITY';
    public const array REQUIRED_FIELDS = [
        'schema', 'issuance_authority_id', 'mission_id', 'mission_dossier_identity',
        'mission_authorization_consumption', 'instance_id', 'issuance_decision',
        'issuer', 'holder', 'target', 'effect_admission', 'callback_start',
        'sealed_response', 'source_native_authority', 'source_native_principal',
        'source_native_transition', 'operator_root_act', 'permitted_transition',
        'effective_at', 'expires_at', 'replay_identity', 'authority_single_use',
        'authority_exercisable', 'consumed', 'continuing_authority', 'sealed',
        'record_digest',
    ];
    public const array EXACT_TARGET_FIELDS = [
        'authority_id', 'authority_schema', 'authority_digest',
        'deterministic_receipt_id', 'effective_at', 'expires_at',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'schema', 'digest'];
    public const array REQUIRED_INVARIANTS = [
        'single_purpose' => true,
        'single_use' => true,
        'exact_holder_required' => true,
        'exact_issuer_required' => true,
        'exact_decision_required' => true,
        'exact_admission_and_lineage_required' => true,
        'exact_validity_window_required' => true,
        'exact_replay_identity_required' => true,
        'continuing_authority' => false,
    ];

    private function __construct() {}
}
