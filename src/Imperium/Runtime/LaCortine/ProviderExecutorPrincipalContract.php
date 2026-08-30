<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderExecutorPrincipalContract
{
    public const string SCHEMA = 'imperium.la-cortine.provider-executor-principal/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'imperator.exact-provider-executor-principal-attestation';
    public const array CONSUMER_POSTURES = [
        'imperator.durable-provider-execution-authority',
        'la-cortine.single-operation-provider-binding-activation',
        'la-cortine.provider-execution-admission',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'principal_attestation_id', 'instance_id', 'execution_boundary',
        'principal', 'source_attestation', 'competence', 'validity', 'status',
        'attested_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_PRINCIPAL_FIELDS = [
        'principal_id', 'infrastructure_role', 'binding_id', 'generation',
        'process_boundary_id',
    ];
    public const array REQUIRED_COMPETENCE_FIELDS = [
        'operation', 'provider_id', 'adapter_id', 'credential_family',
        'same_process_execution_required',
    ];
    public const array REQUIRED_VALIDITY_FIELDS = [
        'effective_at', 'expires_at', 'revocation_reference',
    ];
    public const array STATUSES = ['ATTESTED_INERT', 'EXPIRED', 'REVOKED'];
    public const array NON_AUTHORITIES = [
        'installs_principal' => false,
        'activates_principal' => false,
        'issues_source_attestation' => false,
        'issues_execution_authority' => false,
        'activates_provider_binding' => false,
        'consumes_authority' => false,
        'resolves_credentials' => false,
        'starts_effect' => false,
        'starts_external_io' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
