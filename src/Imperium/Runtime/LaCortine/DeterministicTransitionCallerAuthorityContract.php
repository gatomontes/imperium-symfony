<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class DeterministicTransitionCallerAuthorityContract
{
    public const string SCHEMA = 'imperium.runtime-principal.deterministic-transition-caller-authority/v1';
    public const array TRANSITIONS = [
        'REQUEST_EXACT_OUTBOUND_EMAIL_AUTHORIZATION',
        'DECIDE_EXACT_OUTBOUND_EMAIL_REQUEST',
        'ISSUE_EXACT_OUTBOUND_EMAIL_AUTHORIZATION',
    ];
    public const array REQUIRED_FIELDS = ['schema', 'authority_id', 'instance_id', 'principal', 'source', 'permitted_transition', 'target', 'authority_single_use', 'authority_exercisable', 'issued_at', 'expires_at', 'consumed', 'continuing_authority', 'sealed', 'record_digest'];
    public const array REQUIRED_PRINCIPAL_FIELDS = ['principal_id', 'office', 'seat', 'binding_id', 'generation'];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest'];

    private function __construct()
    {
    }
}
