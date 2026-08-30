<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ActivationCorridorDispositionContract
{
    public const string SCHEMA = 'imperium.imperator.activation-corridor-disposition/v1';
    public const array REQUIRED_FIELDS = ['schema', 'disposition_id', 'instance_id', 'principal', 'caller_authority', 'target', 'evidence_dossier', 'eligibility', 'disposition', 'rationale', 'limitations', 'consequences', 'decided_at', 'terminal_custody_refusal', 'source_artifact_mutated', 'successor_authority_created', 'binding_activated', 'external_action_performed', 'sealed', 'record_digest'];
    public const array DISPOSITIONS = ['QUARANTINED_PENDING_REMEDIATION', 'RETIRE_CORRIDOR'];
    public const string CUSTODY_REFUSAL = 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE';
    public const array NON_AUTHORITIES = ['activates_principal' => false, 'activates_binding' => false, 'mutates_source_artifact' => false, 'revokes_artifact_retroactively' => false, 'creates_successor_authority' => false, 'authorizes_credential_platform' => false, 'authorizes_provider_execution' => false, 'handles_capability' => false, 'handles_credential' => false, 'starts_external_io' => false];
    private function __construct() {}
}
