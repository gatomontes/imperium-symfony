<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ActivationCorridorDispositionEligibilityContract
{
    public const string SCHEMA = 'imperium.imperator.activation-corridor-disposition-eligibility/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'imperator.activation-corridor-disposition-eligibility-assessor';
    public const array CONSUMER_POSTURES = [
        'future-imperator.activation-corridor-disposition-caller-authority-issuer',
        'future-imperator.activation-corridor-disposition-producer',
        'future-imperator.activation-corridor-terminal-auditor',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'eligibility_id', 'instance_id', 'target', 'evidence_dossier',
        'principal', 'proposed_disposition', 'predicates', 'consequences',
        'classification', 'reasons', 'assessed_at', 'authority_created',
        'disposition_sealed', 'source_artifact_mutated', 'successor_authority_created',
        'continuing_custody_refusal', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array DISPOSITIONS = ['QUARANTINED_PENDING_REMEDIATION', 'RETIRE_CORRIDOR'];
    public const array CLASSIFICATIONS = ['ELIGIBLE', 'INELIGIBLE', 'INCOMPLETE', 'CONFLICTED', 'REFUSED'];
    public const array REQUIRED_PREDICATE_FIELDS = [
        'principal_intact', 'principal_effectively_active',
        'principal_corridor_disposition_authority', 'principal_not_expired_or_revoked',
        'target_intact', 'dossier_complete', 'all_evidence_intact',
        'terminal_custody_refusal_authoritative', 'conflicting_disposition_absent',
        'source_artifact_mutation_prohibited', 'successor_authority_prohibited',
    ];
    public const array REQUIRED_QUARANTINE_CONSEQUENCE_FIELDS = [
        'corridor_operationally_usable', 'remediation_authority_created',
        'future_reconsideration_requires_new_authority', 'historical_evidence_readable',
        'terminal_custody_refusal_authoritative',
    ];
    public const array REQUIRED_RETIREMENT_CONSEQUENCE_FIELDS = [
        'corridor_operationally_usable', 'retirement_irreversible',
        'replacement_corridor_requires_new_authority', 'historical_evidence_readable',
        'outstanding_artifacts_create_no_authority', 'terminal_custody_refusal_authoritative',
    ];
    public const string CONTINUING_CUSTODY_REFUSAL = 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE';
    public const array NON_AUTHORITIES = [
        'activates_principal' => false,
        'issues_caller_authority' => false,
        'consumes_caller_authority' => false,
        'selects_disposition' => false,
        'seals_disposition' => false,
        'mutates_activation_artifact' => false,
        'revokes_artifact_retroactively' => false,
        'creates_successor_authority' => false,
        'authorizes_credential_platform' => false,
        'handles_capability' => false,
        'handles_credential' => false,
        'starts_external_io' => false,
    ];

    private function __construct() {}
}
