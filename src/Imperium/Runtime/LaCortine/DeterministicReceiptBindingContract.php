<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class DeterministicReceiptBindingContract
{
    public const string SCHEMA = 'imperium.la-cortine.deterministic-receipt-binding/v1';
    public const int VERSION = 1;

    public const array REQUIRED_FIELDS = [
        'schema',
        'binding_id',
        'instance_id',
        'execution_claim',
        'source_authorization',
        'request',
        'provider_outcome',
        'raw_receipt',
        'lazaretto_admission',
        'recovery',
        'bound_at',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_CLAIM_REFERENCE_FIELDS = [
        'id',
        'digest',
        'replay_fingerprint',
        'execution_id',
    ];

    public const array REQUIRED_SOURCE_REFERENCE_FIELDS = [
        'id',
        'digest',
    ];

    public const array REQUIRED_REQUEST_FIELDS = [
        'id',
        'commission_id',
        'authorization_id',
        'authorization_digest',
        'operation',
        'destination',
        'payload_digest',
        'credential_capability_id',
        'expected_return_contract',
    ];

    public const array REQUIRED_PROVIDER_OUTCOME_FIELDS = [
        'status',
        'effect_started_at',
        'resolved_at',
        'provider_idempotency_key',
        'provider_receipt_identity',
        'provider_contract_reference',
    ];

    public const array PROVIDER_OUTCOMES = [
        'ACCEPTED',
        'REJECTED',
        'UNKNOWN_REPLAY_PROHIBITED',
    ];

    public const array REQUIRED_RAW_RECEIPT_FIELDS = [
        'id',
        'schema',
        'content_digest',
        'sealed_content_reference',
        'observed_at',
        'received_at',
    ];

    public const array REQUIRED_ADMISSION_FIELDS = [
        'artifact_id',
        'artifact_digest',
        'expected_return_contract_validated',
        'admitted_at',
        'transformation',
    ];

    public const array REQUIRED_RECOVERY_FIELDS = [
        'checkpoint',
        'automatic_replay_permitted',
        'provider_reinvoked',
        'forward_recovery_source',
    ];

    public const array RECOVERY_CHECKPOINTS = [
        'CLAIM_ONLY',
        'OUTCOME_UNKNOWN',
        'RAW_RECEIPT_SEALED',
        'LAZARETTO_ADMITTED',
        'COMPLETE',
    ];

    public const array RECONSTRUCTION_REQUIREMENTS = [
        'source_authorization_exact' => true,
        'execution_claim_exact' => true,
        'request_scope_exact' => true,
        'credential_secret_excluded' => true,
        'provider_outcome_truthful' => true,
        'raw_receipt_digest_exact' => true,
        'lazaretto_admission_exact' => true,
        'forward_recovery_never_reinvokes_provider' => true,
    ];

    public const array CONTRACT_BOUNDARY = [
        'claims_provider_acceptance_without_receipt' => false,
        'treats_unknown_as_rejected' => false,
        'treats_unknown_as_accepted' => false,
        'stores_credential_secret' => false,
        'reinvokes_provider_during_recovery' => false,
        'expands_lazaretto_policy' => false,
        'performs_external_io' => false,
        'grants_execution_authority' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
