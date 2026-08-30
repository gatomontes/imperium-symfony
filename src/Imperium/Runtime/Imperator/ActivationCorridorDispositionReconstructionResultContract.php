<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ActivationCorridorDispositionReconstructionResultContract
{
    public const string SCHEMA = 'imperium.imperator.activation-corridor-disposition-read-only-reconstruction/v1';
    public const int VERSION = 1;
    public const array CLASSIFICATIONS = ['ELIGIBLE', 'INCOMPLETE', 'CONFLICTED', 'REFUSED'];
    public const array REQUIRED_FIELDS = [
        'schema', 'instance_id', 'target', 'proposed_disposition', 'principal',
        'classification', 'reasons', 'evidence', 'reconstructed_at', 'read_only',
        'authority_created', 'authority_issued', 'authority_consumed',
        'disposition_selected', 'disposition_sealed', 'source_artifact_mutated',
        'successor_authority_created', 'continuing_custody_refusal',
        'external_action_performed',
    ];
    public const array NON_AUTHORITIES = [
        'activates_principal' => false,
        'issues_caller_authority' => false,
        'consumes_caller_authority' => false,
        'selects_disposition' => false,
        'seals_disposition' => false,
        'repairs_evidence' => false,
        'mutates_activation_artifact' => false,
        'creates_successor_authority' => false,
        'handles_capability' => false,
        'handles_credential' => false,
        'starts_external_io' => false,
    ];

    private function __construct() {}
}
