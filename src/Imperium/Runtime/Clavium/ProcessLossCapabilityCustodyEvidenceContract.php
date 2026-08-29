<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

final class ProcessLossCapabilityCustodyEvidenceContract
{
    public const string SCHEMA = 'imperium.clavium.process-loss-capability-custody-evidence/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'clavium.offline-process-loss-custody-demonstration';
    public const array CONSUMER_POSTURES = ['imperator.activation-corridor-disposition', 'future-credential-platform-selection-gate'];
    public const array REQUIRED_FIELDS = ['schema', 'evidence_id', 'instance_id', 'source_activation', 'capability_identity', 'issuer_process', 'restart_process', 'process_cut', 'durable_observations', 'recovery_attempt', 'classification', 'observed_at', 'capability_reconstructed', 'credential_reference_persisted', 'credential_resolved', 'external_action_performed', 'sealed', 'record_digest'];
    public const array CLASSIFICATIONS = ['POSSESSION_LOST', 'TRANSFER_PROVEN', 'RECONSTRUCTION_DETECTED', 'UNPROVED'];
    public const array NON_AUTHORITIES = ['issues_capability' => false, 'transfers_capability' => false, 'reconstructs_capability' => false, 'resolves_credential' => false, 'authorizes_platform' => false, 'starts_external_io' => false];

    private function __construct() {}
}
