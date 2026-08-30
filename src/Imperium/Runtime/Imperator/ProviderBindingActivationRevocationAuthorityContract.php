<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingActivationRevocationAuthorityContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-activation-revocation-authority/v1';
    public const int VERSION = 1;
    public const array REQUIRED_FIELDS = [
        'schema', 'authority_id', 'instance_id', 'source_decision',
        'provider_binding_activation', 'execution_boundary', 'executor_principal',
        'provider_binding', 'allowed_reason_codes', 'validity',
        'authority_single_use', 'authority_exercisable', 'consumed',
        'continuing_authority', 'issued_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_VALIDITY_FIELDS = [
        'effective_at', 'expires_at', 'revocation_reference',
    ];
    public const array NON_AUTHORITIES = [
        'revokes_without_consumption' => false,
        'issues_activation' => false,
        'issues_execution_authority' => false,
        'consumes_provider_execution_activation' => false,
        'consumes_execution_authority' => false,
        'activates_principal' => false,
        'activates_provider_binding' => false,
        'issues_credential_capability' => false,
        'resolves_credentials' => false,
        'invokes_provider' => false,
        'starts_external_io' => false,
        'authorizes_retry' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
