<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class NormalizedToolResultContract
{
    public const string SCHEMA = 'imperium.la-cortine.normalized-tool-result/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'la-cortine.exact-bound-provider-evidence-decoder';
    public const array CONSUMER_POSTURES = [
        'lazaretto.normalized-tool-result-admission',
        'la-cortine.receipt-reconstructor',
    ];

    public const array REQUIRED_FIELDS = [
        'schema',
        'result_id',
        'instance_id',
        'tool_operation',
        'source_authorization',
        'execution_claim',
        'provider_binding',
        'provider_evidence',
        'decoder',
        'effect_outcome',
        'normalized_attributes',
        'recovery',
        'normalized_at',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_PROVIDER_EVIDENCE_FIELDS = ['raw_result_id', 'raw_result_digest', 'sealed_content_reference', 'content_digest'];
    public const array REQUIRED_DECODER_FIELDS = ['decoder_id', 'decoder_version', 'decoder_contract'];
    public const array REQUIRED_EFFECT_OUTCOME_FIELDS = ['status', 'provider_status', 'effect_started_at', 'resolved_at'];
    public const array REQUIRED_RECOVERY_FIELDS = ['checkpoint', 'automatic_replay_permitted', 'provider_reinvoked'];
    public const array EFFECT_OUTCOMES = ['ACCEPTED', 'REJECTED', 'UNKNOWN_REPLAY_PROHIBITED'];

    public const array RESULT_RULES = [
        'provider_neutral_schema_required' => true,
        'exact_tool_operation_required' => true,
        'exact_provider_binding_required' => true,
        'exact_decoder_required' => true,
        'raw_provider_evidence_required' => true,
        'provider_specific_attributes_must_be_typed' => true,
        'automatic_replay_permitted' => false,
        'provider_reinvocation_permitted' => false,
    ];

    public const array CONTRACT_BOUNDARY = [
        'defines_tool' => false,
        'grants_tool_authority' => false,
        'selects_provider' => false,
        'creates_provider_binding' => false,
        'encodes_provider_request' => false,
        'issues_or_resolves_credentials' => false,
        'starts_external_io' => false,
        'decodes_itself' => false,
        'admits_itself' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
