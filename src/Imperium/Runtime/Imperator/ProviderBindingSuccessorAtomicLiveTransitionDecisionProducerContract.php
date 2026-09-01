<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorAtomicLiveTransitionDecisionProducerContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-atomic-live-transition-decision-producer/v1';
    public const int VERSION = 1;
    public const string STATUS = 'CONTRACT_ONLY_NOT_EXECUTED';
    public const array REQUIRED_FIELDS = [
        'schema', 'producer_id', 'instance_id', 'principal_input',
        'decision_result_schema', 'decision_scope', 'permitted_dispositions',
        'operation_scope', 'replay_contention_root', 'authority_empty',
        'decision_production_performed', 'continuing_authority', 'status',
        'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array PERMITTED_DISPOSITIONS = ['AUTHORIZED', 'REFUSED'];
    public const array NON_AUTHORITIES =
        ProviderBindingSuccessorAtomicLiveTransitionDecisionPrincipalInputContract::NON_AUTHORITIES;

    private function __construct()
    {
    }
}
