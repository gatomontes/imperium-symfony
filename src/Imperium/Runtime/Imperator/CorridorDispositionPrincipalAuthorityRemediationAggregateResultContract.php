<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class CorridorDispositionPrincipalAuthorityRemediationAggregateResultContract
{
    public const string SCHEMA = 'imperium.imperator.corridor-disposition-principal-authority-remediation-read-only-aggregate/v1';
    public const array CLASSIFICATIONS = ['ELIGIBLE', 'INCOMPLETE', 'CONFLICTED', 'REFUSED'];
    public const array REQUIRED_FIELDS = ['schema', 'instance_id', 'classification', 'reasons', 'references', 'interruption_coverage', 'reconstructed_at', 'read_only', 'authority_created', 'authority_issued', 'authority_consumed', 'principal_created', 'principal_activated', 'binding_activated', 'caller_authority_created', 'disposition_selected', 'disposition_sealed', 'source_artifact_mutated', 'continuing_custody_refusal', 'external_action_performed'];
    private function __construct() {}
}
