<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderBindingActivationRevocationWinnerContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-binding-activation-revocation-winner/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'la-cortine.activation-keyed-authorized-revocation-winner';
    public const array CONSUMER_POSTURES = [
        'la-cortine.activation-keyed-combined-provider-execution-admission',
        'la-cortine.provider-execution-reconstruction-v2',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'winner_id', 'instance_id',
        'provider_binding_activation', 'revocation_authority',
        'revocation_authority_consumption', 'reason_code',
        'winner_scope', 'revoked_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_CONSUMPTION_FIELDS = [
        'authority_id', 'authority_digest', 'single_use',
        'consumed', 'continuing_authority',
    ];
    public const string ID_PREFIX =
        'provider-binding-activation-revocation-winner-';
    public const string WINNER_SCOPE_PREFIX =
        'single-authoritative-root:provider-binding-activation:';
    public const string LOCK_SCOPE_PREFIX =
        'governed-provider-execution-admission:';
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
