<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingActivationStateReconciliationAdversarialAuditResultContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-activation-state-reconciliation-adversarial-audit/v1';
    public const array CLASSIFICATIONS = ['PASSED', 'REFUSED', 'CONFLICTED'];
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
        'artifact_promoted',
        'production_decision_created',
        'activation_transition_performed',
        'binding_activated',
        'authority_issued',
        'authority_consumed',
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
