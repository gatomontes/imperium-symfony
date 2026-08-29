<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingAuthorizationContract
{
    public const string SCHEMA = 'imperium.imperator.provider-binding-authorization/v1';
    public const array REQUIRED_FIELDS = [
        'schema',
        'authority_id',
        'instance_id',
        'source',
        'tool_operation',
        'provider_implementation',
        'assurance_profile',
        'credential_family',
        'request_encoder',
        'evidence_decoder',
        'destination_policy',
        'scope',
        'issued_at',
        'expires_at',
        'authority_single_use',
        'authority_exercisable',
        'consumed',
        'continuing_authority',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_SOURCE_FIELDS = ['office', 'seat', 'id', 'digest'];

    public const array CONTRACT_BOUNDARY = [
        'grants_tool_execution' => false,
        'produces_provider_binding' => false,
        'issues_credential_capability' => false,
        'resolves_credentials' => false,
        'starts_external_io' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
