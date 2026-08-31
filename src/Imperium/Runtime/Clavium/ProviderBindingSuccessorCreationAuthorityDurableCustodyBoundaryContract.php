<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorCreationAuthorityV2Contract;

final class ProviderBindingSuccessorCreationAuthorityDurableCustodyBoundaryContract
{
    public const string SCHEMA =
        'imperium.clavium.provider-binding-successor-creation-authority-durable-custody-boundary/v1';
    public const int VERSION = 1;
    public const string STATUS = 'CONTRACT_ONLY_EMPTY';
    public const array REQUIRED_FIELDS = [
        'schema', 'custody_boundary_id', 'instance_id', 'authority_schema',
        'custody_key_kind', 'replay_contention_root', 'authorized_consumer',
        'single_authority', 'authority_present', 'authority_consumed',
        'secret_material_persisted', 'process_local_identity_persisted',
        'continuing_authority', 'status', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_CONSUMER_FIELDS = [
        'service', 'transition', 'same_root_lock_required',
    ];
    public const array INVARIANTS = [
        'authority_schema' =>
            ProviderBindingSuccessorCreationAuthorityV2Contract::SCHEMA,
        'custody_key_kind' => 'exact_replay_contention_root',
        'single_authority' => true,
        'authority_present' => false,
        'authority_consumed' => false,
        'secret_material_persisted' => false,
        'process_local_identity_persisted' => false,
        'continuing_authority' => false,
        'status' => self::STATUS,
    ];
    public const array NON_AUTHORITIES = [
        'issues_authority' => false,
        'reconstructs_authority' => false,
        'reissues_authority' => false,
        'consumes_authority' => false,
        'creates_successor' => false,
        'handles_credential_capability' => false,
        'resolves_credentials' => false,
        'activates_provider_binding' => false,
        'starts_external_io' => false,
        'authorizes_retry' => false,
    ];

    private function __construct()
    {
    }
}
