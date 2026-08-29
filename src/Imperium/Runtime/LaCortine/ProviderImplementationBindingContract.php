<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderImplementationBindingContract
{
    public const string SCHEMA = 'imperium.la-cortine.provider-implementation-binding/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'la-cortine.exact-provider-binding-transition';
    public const array CONSUMER_POSTURES = [
        'curia.provider-bound-tool-authorization',
        'clavium.provider-bound-credential-validation',
        'la-cortine.provider-request-encoder',
        'la-cortine.provider-evidence-decoder',
        'la-cortine.receipt-reconstructor',
    ];

    public const array REQUIRED_FIELDS = [
        'schema',
        'binding_id',
        'instance_id',
        'source_authority',
        'tool_operation',
        'provider_implementation',
        'assurance_profile',
        'credential_family',
        'request_encoder',
        'evidence_decoder',
        'destination_policy',
        'scope',
        'validity',
        'status',
        'bound_at',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_PROVIDER_IMPLEMENTATION_FIELDS = ['provider_id', 'adapter_id', 'adapter_version'];
    public const array REQUIRED_CREDENTIAL_FAMILY_FIELDS = ['family_id', 'provider_id', 'secret_persistence_permitted'];
    public const array REQUIRED_DESTINATION_POLICY_FIELDS = ['policy_id', 'policy_digest', 'exact_destination_required'];
    public const array REQUIRED_SCOPE_FIELDS = ['operation', 'authorization_target_id', 'authorization_target_digest', 'provider_substitution_permitted'];
    public const array REQUIRED_VALIDITY_FIELDS = ['effective_at', 'expires_at'];
    public const array STATUSES = ['BOUND_INACTIVE', 'BOUND_ACTIVE', 'EXPIRED', 'REVOKED'];

    public const array SUBSTITUTION_RULES = [
        'tool_operation_substitution_permitted' => false,
        'provider_substitution_permitted' => false,
        'adapter_substitution_permitted' => false,
        'assurance_profile_substitution_permitted' => false,
        'credential_family_substitution_permitted' => false,
        'request_encoder_substitution_permitted' => false,
        'evidence_decoder_substitution_permitted' => false,
        'destination_policy_substitution_permitted' => false,
    ];

    public const array CONTRACT_BOUNDARY = [
        'creates_tool_definition' => false,
        'grants_tool_authority' => false,
        'selects_itself' => false,
        'issues_source_authority' => false,
        'issues_credential_capability' => false,
        'resolves_credentials' => false,
        'encodes_provider_request' => false,
        'starts_external_io' => false,
        'decodes_provider_evidence' => false,
        'admits_external_evidence' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
