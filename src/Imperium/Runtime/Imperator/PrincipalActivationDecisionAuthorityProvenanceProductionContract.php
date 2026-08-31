<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class PrincipalActivationDecisionAuthorityProvenanceProductionContract
{
    public const string SCHEMA =
        'imperium.imperator.principal-activation-decision-authority-provenance-production/v1';
    public const array REQUIRED_FIELDS = [
        'schema',
        'production_id',
        'instance_id',
        'eligible_aggregate',
        'pending_successor_principal',
        'applied_lifecycle_disposition',
        'effective_principal_status',
        'consumed_issuance_authorization',
        'activation_decision',
        'combined_winner',
        'produced_at',
        'provider_executor_principal_activated',
        'provider_binding_activated',
        'activation_authority_consumed',
        'credential_or_capability_handled',
        'provider_invoked',
        'external_action_performed',
        'continuing_authority',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_CONSUMPTION_FIELDS = [
        'source_authorization',
        'consumed_at',
        'consumed',
        'continuing_authority',
    ];
    public const array NON_AUTHORITIES = [
        'activates_provider_executor_principal' => false,
        'activates_provider_binding' => false,
        'consumes_activation_authority' => false,
        'handles_credential_or_capability' => false,
        'invokes_provider' => false,
        'starts_external_io' => false,
        'authorizes_retry' => false,
        'migrates_live_consumer' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
    ];

    private function __construct()
    {
    }
}
