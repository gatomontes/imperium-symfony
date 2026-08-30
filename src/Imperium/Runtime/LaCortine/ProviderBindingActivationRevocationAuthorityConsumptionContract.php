<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderBindingActivationRevocationAuthorityConsumptionContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-binding-activation-revocation-authority-consumption/v1';
    public const int VERSION = 1;
    public const array REQUIRED_FIELDS = [
        'schema', 'consumption_id', 'instance_id', 'revocation_authority',
        'provider_binding_activation', 'revocation_fact',
        'single_use', 'consumed', 'continuing_authority',
        'winner_scope', 'consumed_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const string WINNER_SCOPE_PREFIX =
        'single-authoritative-root:provider-binding-activation:';
    public const array NON_AUTHORITIES = [
        'issues_revocation_authority' => false,
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
