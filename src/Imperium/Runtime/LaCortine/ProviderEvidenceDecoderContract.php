<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderEvidenceDecoderContract
{
    public const string SCHEMA = 'imperium.la-cortine.provider-evidence-decoder/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'la-cortine.bound-provider-evidence-decoder';
    public const array CONSUMER_POSTURES = [
        'lazaretto.normalized-tool-result-admission',
        'la-cortine.receipt-reconstructor',
    ];

    public const array REQUIRED_INPUT_FIELDS = [
        'tool_operation',
        'provider_binding',
        'source_authorization',
        'execution_claim',
        'provider_invocation_admission',
        'provider_response_envelope',
        'raw_provider_result',
        'sealed_content_reference',
        'content_digest',
    ];

    public const array REQUIRED_OUTPUT_FIELDS = [
        'schema',
        'decoder_id',
        'decoder_version',
        'provider_binding',
        'raw_provider_result',
        'normalized_result_contract',
        'normalized_attributes',
        'decoded_at',
        'sealed',
        'record_digest',
    ];

    public const array DECODING_RULES = [
        'exact_bound_decoder_required' => true,
        'exact_raw_content_digest_required' => true,
        'caller_supplied_provider_truth_permitted' => false,
        'raw_evidence_mutation_permitted' => false,
        'provider_substitution_permitted' => false,
        'decoder_substitution_permitted' => false,
        'credential_resolution_permitted' => false,
        'provider_reinvocation_permitted' => false,
        'external_io_permitted' => false,
    ];

    public const array CONTRACT_BOUNDARY = [
        'defines_tool' => false,
        'grants_tool_authority' => false,
        'selects_provider' => false,
        'creates_provider_binding' => false,
        'encodes_provider_request' => false,
        'issues_or_resolves_credentials' => false,
        'starts_external_io' => false,
        'changes_provider_outcome' => false,
        'admits_external_evidence' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
