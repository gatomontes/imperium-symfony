<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorCreationAuthorityIssuanceBoundaryContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-creation-authority-issuance-boundary/v1';
    public const int VERSION = 1;
    public const string STATUS = 'CONTRACT_ONLY_NOT_ISSUED';
    public const string PERMITTED_TRANSITION =
        'ISSUE_EXACT_PROVIDER_BINDING_SUCCESSOR_CREATION_AUTHORITY';
    public const array REQUIRED_FIELDS = [
        'schema', 'issuance_boundary_id', 'instance_id', 'exact_principal',
        'decision_issuer', 'decision_schema', 'authority_schema',
        'permitted_transition', 'replay_contention_root', 'custody_target',
        'authority_single_use', 'authority_exercisable', 'authority_issued',
        'continuing_authority', 'status', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array INVARIANTS = [
        'decision_schema' =>
            ProviderBindingSuccessorProductionDecisionV2Contract::SCHEMA,
        'authority_schema' =>
            ProviderBindingSuccessorCreationAuthorityV2Contract::SCHEMA,
        'authority_single_use' => true,
        'authority_exercisable' => false,
        'authority_issued' => false,
        'continuing_authority' => false,
        'status' => self::STATUS,
    ];
    public const array NON_AUTHORITIES = [
        'produces_decision' => false,
        'issues_authority' => false,
        'takes_authority_custody' => false,
        'consumes_authority' => false,
        'creates_successor' => false,
        'implements_v3_admission' => false,
        'decides_adoption' => false,
        'activates_provider_binding' => false,
        'handles_credential_capability' => false,
        'starts_external_io' => false,
    ];

    private function __construct()
    {
    }
}
