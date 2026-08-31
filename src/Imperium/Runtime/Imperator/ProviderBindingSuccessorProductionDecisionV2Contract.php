<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorProductionDecisionV2Contract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-production-decision/v2';
    public const int VERSION = 2;
    public const string SUPERSEDES =
        ProviderBindingSuccessorProductionDecisionContract::SCHEMA;
    public const string PRODUCER_POSTURE =
        'future-competent-imperator-acyclic-successor-production-decision';
    public const array CONSUMER_POSTURES = [
        'imperator.future-v2-successor-creation-authority-issuance',
        'la-cortine.future-atomic-successor-creation',
        'imperium.audit.provider-binding-successor-production-decision',
    ];
    public const string TARGET_KIND =
        ProviderBindingSuccessorProductionDecisionContract::TARGET_KIND;
    public const string PERMITTED_TRANSITION =
        ProviderBindingSuccessorProductionDecisionContract::PERMITTED_TRANSITION;
    public const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED'];
    public const array REQUIRED_FIELDS = [
        'schema',
        'decision_id',
        'instance_id',
        'competent_actor',
        'source_decision_authority',
        'reconciled_target',
        'reconciled_decision_input',
        'requested_transition',
        'disposition',
        'limitations',
        'validity',
        'successor_creation_authority_issuance_target',
        'decided_at',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_ACTOR_FIELDS =
        ProviderBindingSuccessorProductionDecisionContract::REQUIRED_ACTOR_FIELDS;
    public const array REQUIRED_VALIDITY_FIELDS =
        ProviderBindingSuccessorProductionDecisionContract::REQUIRED_VALIDITY_FIELDS;
    public const array REQUIRED_ISSUANCE_TARGET_FIELDS = [
        'authority_id',
        'authority_schema',
        'successor_target',
        'permitted_transition',
        'replay_contention_root',
        'authority_single_use',
        'continuing_authority',
    ];
    public const array ISSUANCE_TARGET_INVARIANTS = [
        'authority_schema' =>
            'imperium.imperator.provider-binding-successor-creation-authority/v2',
        'permitted_transition' => self::PERMITTED_TRANSITION,
        'authority_single_use' => true,
        'continuing_authority' => false,
    ];
    public const array NON_AUTHORITIES =
        ProviderBindingSuccessorProductionDecisionContract::NON_AUTHORITIES;

    private function __construct()
    {
    }
}
