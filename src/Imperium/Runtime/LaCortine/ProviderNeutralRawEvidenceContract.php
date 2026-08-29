<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderNeutralRawEvidenceContract
{
    public const string SCHEMA = 'imperium.la-cortine.provider-neutral-raw-evidence/v1';
    public const array REQUIRED_FIELDS = ['schema', 'evidence_id', 'instance_id', 'tool_operation', 'source_authorization', 'execution_claim', 'provider_binding', 'provider_observation', 'content_base64', 'observed_at', 'sealed', 'record_digest'];
    public const array BOUNDARY = ['interprets_provider_content' => false, 'invokes_provider' => false, 'admits_evidence' => false, 'permits_replay' => false];

    private function __construct()
    {
    }
}
