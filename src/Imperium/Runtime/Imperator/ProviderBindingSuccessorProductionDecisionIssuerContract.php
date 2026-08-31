<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorProductionDecisionIssuerContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-production-decision-issuer/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'future-imperator-exact-successor-production-decision-producer';
    public const string PERMITTED_TRANSITION =
        ProviderBindingSuccessorProductionDecisionV2Contract::PERMITTED_TRANSITION;
    public const array REQUIRED_FIELDS = [
        'schema',
        'issuer_id',
        'instance_id',
        'exact_principal',
        'decision_schema',
        'permitted_transition',
        'decision_scope',
        'operation_scope',
        'replay_contention_root',
        'authority_empty',
        'decision_production_performed',
        'continuing_authority',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array INVARIANTS = [
        'decision_schema' =>
            ProviderBindingSuccessorProductionDecisionV2Contract::SCHEMA,
        'decision_scope' =>
            ProviderBindingSuccessorProductionDecisionPrincipalContract::DECISION_SCOPE,
        'authority_empty' => true,
        'decision_production_performed' => false,
        'continuing_authority' => false,
    ];
    public const array NON_AUTHORITIES =
        ProviderBindingSuccessorProductionDecisionPrincipalContract::NON_AUTHORITIES;

    private function __construct()
    {
    }
}
