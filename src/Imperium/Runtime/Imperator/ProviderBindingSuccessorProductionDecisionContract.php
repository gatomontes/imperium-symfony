<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorProductionDecisionContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-production-decision/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'future-competent-imperator-successor-production-decision';
    public const array CONSUMER_POSTURES = [
        'imperator.future-successor-creation-authority-issuance',
        'la-cortine.future-atomic-successor-creation',
        'imperium.audit.provider-binding-successor-production-decision',
    ];
    public const string TARGET_KIND =
        'exact_operation_scoped_provider_binding_lifecycle_successor';
    public const string PERMITTED_TRANSITION =
        'CREATE_EXACT_RECONCILED_PROVIDER_BINDING_SUCCESSOR';
    public const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED'];
    public const array REQUIRED_FIELDS = [
        'schema',
        'decision_id',
        'instance_id',
        'competent_actor',
        'source_decision_authority',
        'reconciled_target',
        'reconciled_decision_input',
        'requested_transition',
        'disposition',
        'limitations',
        'validity',
        'successor_creation_authority',
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
        'decision_scope',
    ];
    public const array REQUIRED_VALIDITY_FIELDS = [
        'effective_at',
        'expires_at',
        'revocation_reference',
    ];
    public const array REQUIRED_AUTHORITY_REFERENCE_FIELDS = [
        'id',
        'digest',
        'schema',
    ];
    public const array NON_AUTHORITIES = [
        'has_producer' => false,
        'validates_decision' => false,
        'issues_successor_creation_authority' => false,
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
