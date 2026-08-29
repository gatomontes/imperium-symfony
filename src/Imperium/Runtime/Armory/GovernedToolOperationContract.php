<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Armory;

final class GovernedToolOperationContract
{
    public const string SCHEMA = 'imperium.armory.governed-tool-operation/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'armory.armorer-governed-tool-definition';
    public const array CONSUMER_POSTURES = [
        'curia.exact-tool-authority-request',
        'la-cortine.provider-implementation-binding',
        'la-cortine.iron-gate-tool-execution',
        'lazaretto.normalized-tool-result-admission',
    ];

    public const array REQUIRED_FIELDS = [
        'schema',
        'tool_id',
        'tool_version',
        'owner',
        'operation',
        'payload_contract',
        'effect_class',
        'normalized_return_contract',
        'secret_policy',
        'provider_policy',
        'status',
        'sealed_at',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_OWNER_FIELDS = ['office', 'seat'];
    public const array REQUIRED_PAYLOAD_CONTRACT_FIELDS = ['schema', 'digest_algorithm', 'exact_bytes_required'];
    public const array REQUIRED_SECRET_POLICY_FIELDS = ['payload_may_contain_credentials', 'provider_adapter_may_receive_opaque_authentication'];
    public const array REQUIRED_PROVIDER_POLICY_FIELDS = ['provider_neutral', 'provider_binding_required', 'provider_substitution_permitted'];
    public const array STATUSES = ['DEFINED_INACTIVE', 'ACTIVE', 'RETIRED'];

    public const array CONTRACT_BOUNDARY = [
        'grants_tool_authority' => false,
        'selects_provider' => false,
        'binds_provider' => false,
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
