<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionIndependentVerificationInputContract
{
    public const string SCHEMA = 'imperium.atomic-transition-independent-verification-input/v1';
    public const array REQUIRED_FIELDS = [
        'schema', 'verification_id', 'sanitized_evidence', 'source_commit',
        'source_tree_digest', 'artifact_bindings', 'private_receipt_digest',
        'private_receipt_availability', 'private_receipt_locator_supplied',
        'producer_reconstruction_supplied', 'producer_conclusion_supplied',
        'read_only', 'authority_empty', 'execution_authorized',
        'provider_authorized', 'external_io_authorized', 'runtime_write_authorized',
        'continuing_authority', 'sealed', 'record_digest',
    ];
    public const array AVAILABILITY = ['AVAILABLE_OPERATOR_LOCAL', 'UNAVAILABLE', 'UNKNOWN'];

    private function __construct()
    {
    }
}
