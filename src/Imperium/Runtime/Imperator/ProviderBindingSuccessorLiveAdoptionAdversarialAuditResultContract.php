<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorLiveAdoptionAdversarialAuditResultContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-live-adoption-adversarial-audit-result/v1';

    public const array CLASSIFICATIONS = ['PASSED', 'CONFLICTED', 'REFUSED'];

    public const array REQUIRED_FIELDS = [
        'schema',
        'classification',
        'findings',
        'audited_root',
        'audited_at',
        'read_only',
        'record_created',
        'record_repaired',
        'artifact_replaced',
        'decision_performed',
        'authority_issued',
        'authority_consumed',
        'execution_admitted',
        'successor_adopted',
        'binding_transitioned',
        'credential_or_capability_handled',
        'provider_invoked',
        'external_io_started',
        'provider_effect_started',
        'retry_authority_created',
        'continuing_authority',
    ];

    private function __construct()
    {
    }
}
