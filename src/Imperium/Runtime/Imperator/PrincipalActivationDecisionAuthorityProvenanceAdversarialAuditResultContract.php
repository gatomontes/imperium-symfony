<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class PrincipalActivationDecisionAuthorityProvenanceAdversarialAuditResultContract
{
    public const string SCHEMA =
        'imperium.imperator.principal-activation-decision-authority-provenance-adversarial-audit/v1';
    public const array CLASSIFICATIONS = ['PASSED', 'REFUSED', 'CONFLICTED'];
    public const array REQUIRED_FIELDS = [
        'schema',
        'classification',
        'findings',
        'audited_production',
        'audited_at',
        'read_only',
        'record_created',
        'record_repaired',
        'authority_issued',
        'authority_consumed',
        'principal_activated',
        'binding_activated',
        'credential_or_capability_handled',
        'provider_invoked',
        'external_action_performed',
    ];

    private function __construct()
    {
    }
}
