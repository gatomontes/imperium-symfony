<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderBindingActivationRevocationContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-binding-activation-revocation/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'la-cortine.activation-keyed-revocation-transition';
    public const array CONSUMER_POSTURES = [
        'la-cortine.activation-keyed-combined-provider-execution-admission',
        'la-cortine.provider-execution-reconstruction-v2',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'revocation_id', 'instance_id',
        'provider_binding_activation', 'source_revocation_authority',
        'reason_code', 'revoked_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REASON_CODES = [
        'OPERATOR_REVOKED',
        'PRINCIPAL_REVOKED',
        'BINDING_REVOKED',
        'ASSURANCE_WITHDRAWN',
        'EXECUTION_SCOPE_WITHDRAWN',
    ];
    public const string ID_PREFIX =
        'provider-binding-activation-revocation-';
    public const string LOCK_SCOPE_PREFIX =
        'governed-provider-execution-admission:';
    public const array NON_AUTHORITIES = [
        'issues_activation' => false,
        'issues_execution_authority' => false,
        'consumes_activation' => false,
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
