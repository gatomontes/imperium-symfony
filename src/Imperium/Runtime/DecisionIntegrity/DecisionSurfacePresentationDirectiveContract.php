<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\DecisionIntegrity;

final class DecisionSurfacePresentationDirectiveContract
{
    public const string SCHEMA = 'imperium.decision-surface-presentation-directive/v1';

    public const array REQUIRED_FIELDS = [
        'schema',
        'directive_id',
        'instance_id',
        'proceeding_id',
        'source_option_universe',
        'decision_owner',
        'decision_question',
        'presented_option_ids',
        'unavailable_option_ids',
        'prohibited_option_ids',
        'rejected_option_ids',
        'unexamined_option_ids',
        'material_consequences',
        'risks',
        'reversibility',
        'recommendation',
        'evidence',
        'requested_authority',
        'authority_not_requested',
        'limitations',
        'expires_at',
        'allowed_dispositions',
        'authored_at',
        'sealed',
        'record_digest',
    ];

    public const array CATEGORIES = [
        'presented_option_ids',
        'unavailable_option_ids',
        'prohibited_option_ids',
        'rejected_option_ids',
        'unexamined_option_ids',
    ];

    private function __construct()
    {
    }
}
