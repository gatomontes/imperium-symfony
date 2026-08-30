<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class PrincipalActivationDecisionAuthorityProvenanceAggregateResultContract
{
    public const string SCHEMA =
        'imperium.imperator.principal-activation-decision-authority-provenance-read-only-aggregate/v1';
    public const array CLASSIFICATIONS = [
        'ELIGIBLE',
        'INCOMPLETE',
        'CONFLICTED',
        'REFUSED',
    ];
    public const array REQUIRED_FIELDS = [
        'schema',
        'instance_id',
        'classification',
        'reasons',
        'references',
        'interruption_coverage',
        'reconstructed_at',
        'read_only',
        'record_created',
        'record_repaired',
        'scope_granted',
        'authority_issued',
        'authority_consumed',
        'principal_created',
        'principal_activated',
        'binding_activated',
        'activation_decision_created',
        'source_artifact_mutated',
        'credential_or_capability_handled',
        'provider_invoked',
        'external_action_performed',
    ];

    private function __construct()
    {
    }
}
