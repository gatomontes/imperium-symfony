<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\DecisionIntegrity;

final class DecisionSurfaceOptionUniverseContract
{
    public const string SCHEMA = 'imperium.decision-surface-option-universe/v1';

    public const array REQUIRED_FIELDS = [
        'schema',
        'universe_id',
        'instance_id',
        'proceeding_id',
        'options',
        'evidence',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_OPTION_FIELDS = [
        'option_id',
        'materially_relevant',
        'availability',
        'classification_reason',
        'plain_language_explanation',
        'material_consequences',
        'risks',
        'costs',
        'external_effects',
        'reversibility',
        'authority_effect',
        'evidence',
    ];

    public const array AVAILABILITY = ['AVAILABLE', 'UNAVAILABLE', 'PROHIBITED'];

    private function __construct()
    {
    }
}
