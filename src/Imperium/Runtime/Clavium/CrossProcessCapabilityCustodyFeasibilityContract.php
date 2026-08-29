<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

final class CrossProcessCapabilityCustodyFeasibilityContract
{
    public const string SCHEMA = 'imperium.clavium.cross-process-capability-custody-feasibility/v1';
    public const array REQUIRED_FIELDS = ['schema', 'assessment_id', 'instance_id', 'source_activation', 'capability_identity', 'broker_assessment', 'disposition', 'reasons', 'assessed_at', 'custody_created', 'delivery_created', 'capability_issued', 'capability_reconstructed', 'credential_reference_persisted', 'secret_material_persisted', 'external_action_performed', 'sealed', 'record_digest'];
    public const string REFUSAL = 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE';
    public const array NON_AUTHORITIES = ['creates_custody' => false, 'creates_delivery' => false, 'issues_capability' => false, 'reconstructs_capability' => false, 'resolves_credentials' => false, 'starts_external_io' => false];

    private function __construct()
    {
    }
}
