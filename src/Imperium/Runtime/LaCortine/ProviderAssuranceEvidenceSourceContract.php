<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderAssuranceEvidenceSourceContract
{
    public const string SCHEMA = 'imperium.la-cortine.provider-assurance-evidence-source/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'la-cortine.provider-assurance-evidence-source-definition';
    public const array CONSUMER_POSTURES = [
        'la-cortine.provider-operation-assurance-profile-definition',
        'la-cortine.provider-assurance-evidence-admission-assessment',
    ];
    public const array REQUIRED_FIELDS = [
        'schema',
        'source_id',
        'provider_id',
        'source_kind',
        'canonical_uri',
        'observed_at',
        'content_digest',
        'version_identity',
        'immutability_posture',
        'status',
        'sealed',
        'record_digest',
    ];
    public const array SOURCE_KINDS = [
        'OFFICIAL_PROVIDER_DOCUMENTATION',
        'IMMUTABLE_PROVIDER_ARTIFACT',
        'STERILE_CONFORMANCE_OBSERVATION',
    ];
    public const array STATUSES = ['DEFINED_EVIDENCE_ONLY', 'SUPERSEDED', 'WITHDRAWN'];
    public const array NON_AUTHORITIES = [
        'admits_provider_contract' => false,
        'asserts_provider_conformance' => false,
        'activates_executor_principal' => false,
        'activates_provider_binding' => false,
        'issues_execution_authority' => false,
        'authorizes_retry' => false,
        'resolves_credentials' => false,
        'invokes_provider' => false,
        'starts_external_io' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
    ];

    private function __construct()
    {
    }
}
