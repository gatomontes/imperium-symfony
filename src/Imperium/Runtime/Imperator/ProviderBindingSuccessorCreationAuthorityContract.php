<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorCreationAuthorityContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-creation-authority/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'future-production-decision-bound-single-use-authority-issuer';
    public const array CONSUMER_POSTURES = [
        'la-cortine.future-atomic-successor-creation',
        'imperium.audit.provider-binding-successor-authority-consumption',
    ];
    public const string PERMITTED_TRANSITION =
        ProviderBindingSuccessorProductionDecisionContract::PERMITTED_TRANSITION;
    public const array REQUIRED_FIELDS = [
        'schema',
        'authority_id',
        'instance_id',
        'source_decision',
        'competent_actor',
        'successor_target',
        'permitted_transition',
        'replay_contention_root',
        'authority_single_use',
        'authority_exercisable',
        'validity',
        'consumed',
        'continuing_authority',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_ROOT_FIELDS = [
        'root_id',
        'instance_id',
        'successor_target_id',
        'principal_activation_id',
        'binding_id',
        'provider_id',
        'operation',
    ];
    public const array REQUIRED_VALIDITY_FIELDS = [
        'effective_at',
        'expires_at',
        'revocation_reference',
    ];
    public const array REQUIRED_INVARIANTS = [
        'authority_single_use' => true,
        'authority_exercisable' => true,
        'consumed' => false,
        'continuing_authority' => false,
    ];
    public const array NON_AUTHORITIES = [
        'has_producer' => false,
        'validates_authority' => false,
        'persists_authority' => false,
        'consumes_authority' => false,
        'creates_successor' => false,
        'activates_original_binding' => false,
        'adopts_successor' => false,
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
