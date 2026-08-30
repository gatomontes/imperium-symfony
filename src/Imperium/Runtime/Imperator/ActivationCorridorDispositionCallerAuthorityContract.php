<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ActivationCorridorDispositionCallerAuthorityContract
{
    public const string SCHEMA = 'imperium.imperator.activation-corridor-disposition-caller-authority/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'future-imperator.activation-corridor-disposition-caller-authority-issuer';
    public const array CONSUMER_POSTURES = ['future-imperator.activation-corridor-disposition-producer'];
    public const string PERMITTED_TRANSITION = 'DECIDE_EXACT_ACTIVATION_CORRIDOR_DISPOSITION';
    public const array REQUIRED_FIELDS = [
        'schema', 'authority_id', 'instance_id', 'principal', 'target', 'evidence_dossier',
        'eligibility', 'permitted_transition', 'proposed_disposition',
        'authority_single_use', 'authority_exercisable', 'issued_at', 'expires_at',
        'consumed', 'continuing_authority', 'issuance_winner_required',
        'consumption_winner_required', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array NON_AUTHORITIES = [
        'activates_principal' => false,
        'widens_principal_scope' => false,
        'issues_itself' => false,
        'consumes_itself' => false,
        'selects_disposition' => false,
        'seals_disposition' => false,
        'mutates_activation_artifact' => false,
        'creates_successor_authority' => false,
        'authorizes_credential_platform' => false,
        'handles_capability' => false,
        'handles_credential' => false,
        'starts_external_io' => false,
    ];

    private function __construct() {}
}
