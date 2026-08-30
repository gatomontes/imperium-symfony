<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ActivationCorridorDispositionEvidenceDossierContract
{
    public const string SCHEMA = 'imperium.imperator.activation-corridor-disposition-evidence-dossier/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'imperator.activation-corridor-read-only-evidence-dossier-assembler';
    public const array CONSUMER_POSTURES = [
        'imperator.activation-corridor-disposition-eligibility-assessor',
        'future-imperator.activation-corridor-disposition-caller-authority-issuer',
        'future-imperator.activation-corridor-disposition-producer',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'dossier_id', 'instance_id', 'target', 'active_principal',
        'activation_decision', 'activation_authority', 'activation_lease',
        'transition_interruption_evidence', 'stranded_artifact_dispositions',
        'process_loss_custody_evidence', 'credential_secret_exclusion_evidence',
        'terminal_custody_refusal', 'completeness', 'conflicts', 'assembled_at',
        'read_only', 'authority_created', 'disposition_sealed',
        'source_artifact_mutated', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array COMPLETENESS = ['COMPLETE', 'INCOMPLETE', 'CONFLICTED', 'REFUSED'];
    public const int REQUIRED_INTERRUPTION_EVIDENCE_COUNT = 6;
    public const string CONTINUING_CUSTODY_REFUSAL = 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE';
    public const array NON_AUTHORITIES = [
        'activates_principal' => false,
        'issues_caller_authority' => false,
        'selects_disposition' => false,
        'seals_disposition' => false,
        'repairs_evidence' => false,
        'reinterprets_custody_refusal' => false,
        'mutates_activation_artifact' => false,
        'creates_successor_authority' => false,
        'reconstructs_capability' => false,
        'resolves_credential' => false,
        'starts_external_io' => false,
    ];

    private function __construct() {}
}
