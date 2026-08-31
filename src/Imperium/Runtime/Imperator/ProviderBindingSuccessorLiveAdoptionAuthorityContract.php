<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorLiveAdoptionAuthorityContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-live-adoption-authority/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'future-decision-bound-single-use-live-adoption-authority-issuer';
    public const string PERMITTED_TRANSITION =
        'ADMIT_AND_ADOPT_EXACT_PROVIDER_BINDING_SUCCESSOR';
    public const array REQUIRED_FIELDS = [
        'schema', 'authority_id', 'instance_id', 'source_decision',
        'source_issuance_target', 'competent_actor', 'completed_successor',
        'atomic_creation_winner', 'adoption_target', 'v3_admission',
        'permitted_transition', 'replay_contention_root',
        'authority_single_use', 'authority_exercisable', 'validity', 'consumed',
        'continuing_authority', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_VALIDITY_FIELDS = [
        'effective_at', 'expires_at', 'revocation_reference',
    ];
    public const array REQUIRED_INVARIANTS = [
        'authority_single_use' => true,
        'authority_exercisable' => true,
        'consumed' => false,
        'continuing_authority' => false,
    ];
    public const array NON_AUTHORITIES = [
        'produces_decision' => false,
        'issues_authority' => false,
        'takes_authority_custody' => false,
        'consumes_authority' => false,
        'admits_execution' => false,
        'adopts_successor' => false,
        'activates_provider_binding' => false,
        'handles_credential_capability' => false,
        'invokes_provider' => false,
        'starts_external_io' => false,
        'starts_provider_effect' => false,
        'authorizes_retry' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
