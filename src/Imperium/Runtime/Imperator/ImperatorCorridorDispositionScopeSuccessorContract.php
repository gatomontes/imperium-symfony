<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ImperatorCorridorDispositionScopeSuccessorContract
{
    public const string SCHEMA = 'imperium.mastermason.imperator-corridor-disposition-scope-successor/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'future-mastermason.imperator-corridor-scope-successor-committer';
    public const array CONSUMER_POSTURES = [
        'operator-root.imperator-principal-lifecycle-authority',
        'future-imperator.activation-corridor-disposition-caller-authority-issuance-authorizer',
        'future-imperator.corridor-scope-remediation-reconstructor',
    ];
    public const string PERMITTED_TRANSITION = 'COMMIT_EXACT_CORRIDOR_SCOPE_SUCCESSOR';
    public const string INITIAL_STATUS = 'PENDING_ACTIVATION';
    public const array REQUIRED_FIELDS = [
        'schema', 'successor_transition_id', 'instance_id', 'scope_grant',
        'source_principal', 'successor_principal', 'source_generation',
        'successor_generation', 'identity_preserved', 'binding_preserved',
        'scope_delta', 'preserved_scope', 'initial_status', 'activation_required',
        'separate_activation_authority_required', 'transition_winner_required',
        'committed_at', 'grant_consumed', 'source_principal_mutated',
        'source_principal_superseded', 'caller_authority_issued',
        'continuing_authority', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_PRINCIPAL_FIELDS = ['id', 'digest', 'schema', 'generation'];
    public const array REQUIRED_SCOPE_DELTA_FIELDS = ['corridor_disposition_authority'];
    public const array REQUIRED_PRESERVED_SCOPE_FIELDS = [
        'provider_binding_activation_authority', 'outbound_email_authority',
        'credential_authority', 'provider_execution_authority',
    ];
    public const array NON_AUTHORITIES = [
        'chooses_scope' => false,
        'issues_scope_grant' => false,
        'activates_successor' => false,
        'rewrites_source_principal' => false,
        'reinterprets_lifecycle_supersession' => false,
        'issues_caller_authority' => false,
        'selects_disposition' => false,
        'seals_disposition' => false,
        'mutates_activation_artifact' => false,
        'creates_capability_custody' => false,
        'handles_credential' => false,
        'starts_external_io' => false,
    ];

    private function __construct() {}
}
