<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderRequestEncoderContract
{
    public const string SCHEMA = 'imperium.la-cortine.provider-request-encoder/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'la-cortine.bound-provider-adapter';
    public const array CONSUMER_POSTURES = [
        'la-cortine.journal-bound-provider-invocation',
        'la-cortine.provider-request-reconstruction',
    ];

    public const array REQUIRED_INPUT_FIELDS = [
        'tool_operation',
        'provider_binding',
        'source_authorization',
        'execution_claim',
        'effect_start_journal',
        'destination',
        'payload_digest',
        'exact_payload_bytes',
        'opaque_authentication',
    ];

    public const array REQUIRED_OUTPUT_FIELDS = [
        'schema',
        'encoder_id',
        'encoder_version',
        'provider_binding',
        'method',
        'destination',
        'headers_digest',
        'body_digest',
        'request_fingerprint',
        'secret_persistence_permitted',
    ];

    public const array ENCODING_RULES = [
        'exact_payload_digest_required' => true,
        'exact_destination_policy_required' => true,
        'provider_binding_required' => true,
        'credential_family_match_required' => true,
        'authentication_callback_local_only' => true,
        'secret_persistence_permitted' => false,
        'external_io_permitted' => false,
        'provider_substitution_permitted' => false,
    ];

    public const array CONTRACT_BOUNDARY = [
        'defines_tool' => false,
        'grants_tool_authority' => false,
        'selects_provider' => false,
        'creates_provider_binding' => false,
        'issues_credential_capability' => false,
        'resolves_credentials' => false,
        'consumes_credentials' => false,
        'starts_external_io' => false,
        'observes_provider_response' => false,
        'decodes_provider_evidence' => false,
        'admits_external_evidence' => false,
    ];

    private function __construct()
    {
    }
}
