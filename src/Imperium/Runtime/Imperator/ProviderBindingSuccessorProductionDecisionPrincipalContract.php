<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorProductionDecisionPrincipalContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-production-decision-principal/v1';
    public const int VERSION = 1;
    public const string DECISION_SCOPE =
        'DECIDE_EXACT_PROVIDER_BINDING_SUCCESSOR_PRODUCTION';
    public const string STATUS = 'IDENTIFIED_NOT_ACTIVATED';
    public const array REQUIRED_FIELDS = [
        'schema',
        'principal_id',
        'instance_id',
        'office',
        'seat',
        'binding_id',
        'generation',
        'decision_scope',
        'source_principal_activation',
        'operation_scope',
        'replay_contention_root',
        'status',
        'decision_authority_held',
        'continuing_authority',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array NON_AUTHORITIES = [
        'activates_principal' => false,
        'produces_decision' => false,
        'issues_authority' => false,
        'consumes_authority' => false,
        'creates_successor' => false,
        'implements_v3_admission' => false,
        'decides_adoption' => false,
        'handles_credential_capability' => false,
        'invokes_provider' => false,
        'starts_external_io' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
