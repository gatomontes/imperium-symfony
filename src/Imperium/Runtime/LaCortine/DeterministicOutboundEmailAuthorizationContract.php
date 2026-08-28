<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class DeterministicOutboundEmailAuthorizationContract
{
    public const string SCHEMA = 'imperium.la-cortine.deterministic-outbound-email-authorization/v1';
    public const int VERSION = 1;

    public const array REQUIRED_FIELDS = [
        'schema',
        'authorization_id',
        'instance_id',
        'source_decision',
        'issuer',
        'holder',
        'scope',
        'provider_safety',
        'single_use',
        'exercisable',
        'consumed',
        'issued_at',
        'expires_at',
        'continuing_authority',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_SOURCE_DECISION_FIELDS = [
        'id',
        'digest',
        'schema',
        'decision',
        'decision_owner',
    ];

    public const array REQUIRED_ACTOR_FIELDS = [
        'actor_id',
        'office',
        'seat',
        'binding_id',
        'runtime_principal_id',
    ];

    public const array REQUIRED_SCOPE_FIELDS = [
        'operation',
        'commission_id',
        'inbox_id',
        'destination',
        'recipient_set_digest',
        'subject_digest',
        'body_digest',
        'attachment_manifest_digest',
        'payload_digest',
        'credential_reference_digest',
        'expected_return_contract',
    ];

    public const array REQUIRED_PROVIDER_SAFETY_FIELDS = [
        'strategy',
        'provider',
        'endpoint',
        'idempotency_key',
        'idempotency_key_digest',
        'request_fingerprint',
        'provider_contract_reference',
        'provider_key_expires_at',
    ];

    public const array EXACT_SCOPE_RULES = [
        'operation' => 'email.send',
        'one_inbox' => true,
        'one_destination' => true,
        'recipient_set_digest_required' => true,
        'payload_digest_required' => true,
        'credential_secret_excluded' => true,
        'scope_change_requires_new_authorization' => true,
    ];

    public const array CONSUMPTION_RULES = [
        'single_use' => true,
        'one_execution_winner_required' => true,
        'authorization_expiry_must_not_exceed_provider_key_expiry' => true,
        'same_key_retry_requires_same_request_fingerprint' => true,
        'retry_after_provider_key_expiry_permitted' => false,
        'unknown_outcome_without_durable_claim_may_retry' => false,
    ];

    public const array CONTRACT_BOUNDARY = [
        'identifies_competent_issuer' => false,
        'creates_source_decision' => false,
        'issues_authorization' => false,
        'selects_holder' => false,
        'consumes_authorization' => false,
        'creates_execution_claim' => false,
        'resolves_credentials' => false,
        'adds_provider_header' => false,
        'starts_external_io' => false,
        'persists_receipt' => false,
        'opens_lazaretto' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
