<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionIndependentVerificationIdentityContract
{
    public const string SCHEMA = 'imperium.atomic-transition-independent-verification-public-identity/v1';
    public const string ALGORITHM = 'ed25519';
    public const array REQUIRED_FIELDS = [
        'schema', 'identity_id', 'key_id', 'algorithm', 'public_key',
        'public_key_digest', 'key_purpose', 'verifier_implementation_digest',
        'verifier_dependency_set_digest', 'private_key_retained',
        'signing_capability_retained', 'authority_empty', 'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
