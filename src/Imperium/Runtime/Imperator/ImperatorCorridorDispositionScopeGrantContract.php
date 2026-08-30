<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ImperatorCorridorDispositionScopeGrantContract
{
    public const string SCHEMA = 'imperium.operator-root.imperator-corridor-disposition-scope-grant/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'operator-root.imperator-corridor-disposition-scope-grant-issuer';
    public const array CONSUMER_POSTURES = ['future-mastermason.imperator-corridor-scope-successor-committer'];
    public const string PERMITTED_TRANSITION = 'AUTHORIZE_EXACT_CORRIDOR_SCOPE_SUCCESSOR';
    public const array REQUIRED_FIELDS = [
        'schema', 'grant_id', 'instance_id', 'operator_root', 'source_principal',
        'successor_principal', 'scope_delta', 'preserved_scope', 'permitted_transition',
        'rationale', 'authority_single_use', 'authority_exercisable',
        'issuance_winner_required', 'consumption_winner_required', 'issued_at', 'expires_at',
        'revocation', 'consumed', 'continuing_authority', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_OPERATOR_ROOT_FIELDS = ['operator_id', 'source_identity_digest', 'decision_id', 'decision_digest'];
    public const array REQUIRED_PRINCIPAL_FIELDS = ['id', 'digest', 'schema', 'generation'];
    public const array REQUIRED_SCOPE_DELTA_FIELDS = ['corridor_disposition_authority'];
    public const array REQUIRED_PRESERVED_SCOPE_FIELDS = [
        'provider_binding_activation_authority', 'outbound_email_authority',
        'credential_authority', 'provider_execution_authority',
    ];
    public const array NON_AUTHORITIES = [
        'identifies_operator_root' => false,
        'issues_grant' => false,
        'consumes_grant' => false,
        'widens_source_principal' => false,
        'creates_successor_principal' => false,
        'activates_successor_principal' => false,
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
