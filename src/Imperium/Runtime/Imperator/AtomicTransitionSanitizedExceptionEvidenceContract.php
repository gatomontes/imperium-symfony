<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionSanitizedExceptionEvidenceContract
{
    public const string SCHEMA =
        'imperium.imperator.atomic-transition-sanitized-exception-evidence/v1';
    public const array REQUIRED_FIELDS = [
        'schema', 'exception_id', 'exception_class', 'error_code', 'message',
        'trace_digest', 'sanitized', 'raw_trace_excluded', 'sealed',
        'record_digest',
    ];

    private function __construct()
    {
    }
}
