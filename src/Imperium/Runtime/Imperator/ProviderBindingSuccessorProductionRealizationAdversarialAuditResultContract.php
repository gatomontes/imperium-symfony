<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorProductionRealizationAdversarialAuditResultContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-production-realization-adversarial-audit/v1';
    public const array CLASSIFICATIONS = ['PASSED', 'REFUSED', 'CONFLICTED'];
    public const array REQUIRED_FIELDS = [
        'schema', 'classification', 'findings', 'audited_root', 'audited_at',
        'read_only', 'record_created', 'record_repaired', 'artifact_promoted',
        'decision_performed', 'authority_issued', 'authority_consumed',
        'successor_created', 'adoption_decided', 'join_performed',
        'execution_admitted', 'live_adoption_performed', 'binding_activated',
        'credential_or_capability_handled', 'provider_invoked',
        'external_io_started', 'provider_effect_started',
        'retry_authority_created', 'continuing_authority',
    ];

    private function __construct()
    {
    }
}
