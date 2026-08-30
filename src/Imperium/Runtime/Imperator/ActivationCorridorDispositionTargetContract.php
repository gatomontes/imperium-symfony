<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ActivationCorridorDispositionTargetContract
{
    public const string SCHEMA = 'imperium.imperator.activation-corridor-disposition-target/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'imperator.activation-corridor-target-identifier';
    public const array CONSUMER_POSTURES = [
        'imperator.activation-corridor-evidence-dossier-assembler',
        'imperator.activation-corridor-disposition-eligibility-assessor',
        'future-imperator.activation-corridor-disposition-caller-authority-issuer',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'target_id', 'instance_id', 'corridor_id', 'corridor_generation',
        'terminal_custody_refusal', 'source_campaign', 'scope', 'identified_at',
        'authority_created', 'binding_activated', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_SCOPE_FIELDS = [
        'provider_binding_activation_corridor', 'activation_artifact_set_digest',
        'historical_evidence_set_digest',
    ];
    public const array NON_AUTHORITIES = [
        'activates_principal' => false,
        'activates_binding' => false,
        'issues_caller_authority' => false,
        'selects_disposition' => false,
        'seals_disposition' => false,
        'mutates_activation_artifact' => false,
        'creates_successor_authority' => false,
        'handles_capability' => false,
        'handles_credential' => false,
        'starts_external_io' => false,
    ];

    private function __construct() {}
}
