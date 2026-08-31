<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingActivationReconciledDecisionInputContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-activation-reconciled-decision-input/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'caller-supplied-authority-empty-reconciliation-decision-input';
    public const array CONSUMER_POSTURES = [
        'imperator.future-competent-provider-binding-successor-decision',
        'la-cortine.provider-binding-successor-validation',
    ];
    public const string TARGET_KIND =
        'operation_scoped_provider_binding_lifecycle_successor';
    public const string PERMITTED_TRANSITION =
        'CREATE_EXACT_OPERATION_SCOPED_PROVIDER_BINDING_SUCCESSOR';
    public const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED'];
    public const array REQUIRED_FIELDS = [
        'schema',
        'decision_input_id',
        'instance_id',
        'actor',
        'successor_target',
        'basis',
        'requested_transition',
        'disposition',
        'activation_authority',
        'limitations',
        'decided_at',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_ACTOR_FIELDS = [
        'principal_id',
        'office',
        'seat',
        'binding_id',
        'generation',
    ];
    public const array REQUIRED_BASIS_FIELDS = [
        'active_principal_activation',
        'provider_binding_descriptor',
        'provider_assurance_admission',
        'execution_boundary',
        'operation_scope',
        'replay_contention_root',
    ];
    public const array REQUIRED_AUTHORITY_FIELDS = [
        'authority_id',
        'authority_single_use',
        'authority_exercisable',
        'permitted_transition',
        'target_digest',
        'effective_at',
        'expires_at',
        'revocation_reference',
        'consumed',
        'continuing_authority',
    ];
    public const array AUTHORITY_INVARIANTS = [
        'authority_single_use' => true,
        'authority_exercisable' => true,
        'consumed' => false,
        'continuing_authority' => false,
    ];
    public const array NON_AUTHORITIES = [
        'is_production_decision' => false,
        'creates_successor' => false,
        'activates_original_binding' => false,
        'issues_activation_authority' => false,
        'consumes_activation_authority' => false,
        'issues_execution_authority' => false,
        'handles_credential_capability' => false,
        'resolves_credentials' => false,
        'invokes_provider' => false,
        'starts_effect' => false,
        'starts_external_io' => false,
        'authorizes_retry' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
