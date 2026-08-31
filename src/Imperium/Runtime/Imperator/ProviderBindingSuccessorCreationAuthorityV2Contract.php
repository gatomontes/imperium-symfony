<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorCreationAuthorityV2Contract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-creation-authority/v2';
    public const int VERSION = 2;
    public const string SUPERSEDES =
        ProviderBindingSuccessorCreationAuthorityContract::SCHEMA;
    public const string PRODUCER_POSTURE =
        'future-sealed-v2-decision-bound-single-use-authority-issuer';
    public const array CONSUMER_POSTURES = [
        'la-cortine.future-atomic-successor-creation',
        'imperium.audit.provider-binding-successor-authority-consumption',
    ];
    public const string PERMITTED_TRANSITION =
        ProviderBindingSuccessorProductionDecisionV2Contract::PERMITTED_TRANSITION;
    public const array REQUIRED_FIELDS = [
        'schema',
        'authority_id',
        'instance_id',
        'source_decision',
        'source_issuance_target',
        'competent_actor',
        'successor_target',
        'permitted_transition',
        'replay_contention_root',
        'authority_single_use',
        'authority_exercisable',
        'validity',
        'consumed',
        'continuing_authority',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_ISSUANCE_TARGET_FIELDS =
        ProviderBindingSuccessorProductionDecisionV2Contract::REQUIRED_ISSUANCE_TARGET_FIELDS;
    public const array REQUIRED_ROOT_FIELDS =
        ProviderBindingSuccessorCreationAuthorityContract::REQUIRED_ROOT_FIELDS;
    public const array REQUIRED_VALIDITY_FIELDS =
        ProviderBindingSuccessorCreationAuthorityContract::REQUIRED_VALIDITY_FIELDS;
    public const array REQUIRED_INVARIANTS = [
        'authority_single_use' => true,
        'authority_exercisable' => true,
        'consumed' => false,
        'continuing_authority' => false,
    ];
    public const array NON_AUTHORITIES =
        ProviderBindingSuccessorCreationAuthorityContract::NON_AUTHORITIES;

    private function __construct()
    {
    }
}
