<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class StrandedActivationArtifactDispositionContract
{
    public const string SCHEMA = 'imperium.la-cortine.stranded-activation-artifact-disposition/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'imperator.activation-corridor-disposition';
    public const array CONSUMER_POSTURES = ['la-cortine.activation-artifact-reconstructor', 'future-credential-platform-selection-gate'];
    public const array REQUIRED_FIELDS = ['schema', 'disposition_id', 'instance_id', 'terminal_custody_refusal', 'activation_authority', 'activation_lease', 'evidence', 'disposition', 'rationale', 'limitations', 'decided_at', 'source_artifact_mutated', 'successor_authority_created', 'sealed', 'record_digest'];
    public const array DISPOSITIONS = ['QUARANTINED_EXPIRED_UNUSED', 'QUARANTINED_PENDING_REMEDIATION', 'RETIRE_CORRIDOR'];
    public const array NON_AUTHORITIES = ['mutates_source_artifact' => false, 'revokes_retroactively' => false, 'consumes_activation' => false, 'creates_successor_authority' => false, 'authorizes_credential_platform' => false, 'authorizes_retry' => false, 'starts_external_io' => false];

    private function __construct() {}
}
