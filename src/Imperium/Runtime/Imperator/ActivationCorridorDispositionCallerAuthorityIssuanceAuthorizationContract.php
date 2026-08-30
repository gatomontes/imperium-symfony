<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract
{
    public const string SCHEMA = 'imperium.imperator.activation-corridor-disposition-caller-authority-issuance-authorization/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'future-imperator.activation-corridor-disposition-caller-authority-issuance-authorizer';
    public const array CONSUMER_POSTURES = ['future-imperator.activation-corridor-disposition-caller-authority-issuer'];
    public const string PERMITTED_TRANSITION = 'ISSUE_EXACT_ACTIVATION_CORRIDOR_DISPOSITION_CALLER_AUTHORITY';
    public const array REQUIRED_FIELDS = [
        'schema', 'issuance_authorization_id', 'instance_id', 'issuer_principal',
        'scope_successor', 'activation_disposition', 'target', 'evidence_dossier',
        'eligibility', 'proposed_disposition', 'result_authority_id',
        'permitted_transition', 'authority_single_use', 'authority_exercisable',
        'issuance_winner_required', 'consumption_winner_required', 'issued_at', 'expires_at',
        'revocation', 'consumed', 'continuing_authority', 'custody_refusal',
        'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_PRINCIPAL_FIELDS = ['id', 'digest', 'schema', 'generation'];
    public const array DISPOSITIONS = ['QUARANTINED_PENDING_REMEDIATION', 'RETIRE_CORRIDOR'];
    public const string CONTINUING_CUSTODY_REFUSAL = 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE';
    public const array NON_AUTHORITIES = [
        'activates_principal' => false,
        'widens_principal_scope' => false,
        'issues_itself' => false,
        'issues_caller_authority' => false,
        'consumes_caller_authority' => false,
        'selects_disposition' => false,
        'seals_disposition' => false,
        'mutates_activation_artifact' => false,
        'creates_successor_authority' => false,
        'creates_capability_custody' => false,
        'handles_credential' => false,
        'starts_external_io' => false,
    ];

    private function __construct() {}
}
