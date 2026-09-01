<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionIndependentVerificationAttestationContract
{
    public const string SCHEMA = 'imperium.atomic-transition-independent-verification-detached-attestation/v1';
    public const array REQUIRED_FIELDS = [
        'schema', 'attestation_id', 'report_id', 'report_digest', 'identity_id',
        'key_id', 'algorithm', 'signature', 'signature_created',
        'private_key_retained', 'signing_capability_retained', 'authority_empty',
        'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
