<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Exact future result/refusal vocabulary; no evaluator is implemented here. */
final class NativeEffectReconciliationIssuanceOutcomeContract
{
    public const string SCHEMA = 'imperium.imperator.native-effect-reconciliation-issuance-outcome/v1';
    public const int VERSION = 1;
    public const array RESULTS = ['AUTHORIZED', 'EXACT_RETRY_CONVERGED', 'REFUSED'];
    public const array REFUSALS = [
        'REFUSED_DECISION_MISSING',
        'REFUSED_DECISION_COUNTERFEIT',
        'REFUSED_ISSUANCE_AUTHORITY_MISSING',
        'REFUSED_ISSUANCE_AUTHORITY_COUNTERFEIT',
        'REFUSED_CAPABILITY_MISSING',
        'REFUSED_CAPABILITY_COUNTERFEIT',
        'REFUSED_EXPIRED',
        'REFUSED_REPLAYED',
        'REFUSED_SUBSTITUTED',
        'REFUSED_CONSUMED',
        'REFUSED_STALE',
        'REFUSED_OPERATOR_ROOT_REVOKED',
        'REFUSED_NATIVE_PRINCIPAL_REVOKED',
        'REFUSED_SOURCE_SUSPENDED',
        'REFUSED_SOURCE_SUPERSEDED',
        'REFUSED_SOURCE_REVOKED',
        'REFUSED_SOURCE_EXPIRED',
        'REFUSED_SOURCE_RETIRED',
        'REFUSED_SOURCE_MIGRATION_REQUIRED',
        'REFUSED_CONFLICTED',
    ];
    public const array REQUIRED_RESULT_FIELDS = [
        'schema', 'result', 'refusal', 'replay_identity', 'established_result',
        'continuing_authority',
    ];

    private function __construct() {}
}
