<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

final class CredentialReferenceExposureObservationContract
{
    public const string SCHEMA = 'imperium.clavium.credential-reference-exposure-observation/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'clavium.offline-credential-reference-exposure-observer';
    public const array CONSUMER_POSTURES = ['clavium.credential-reference-boundary-hardening', 'imperator.activation-corridor-disposition'];
    public const array REQUIRED_FIELDS = ['schema', 'observation_id', 'instance_id', 'subject_component', 'access_purpose', 'reader_identity', 'lifetime', 'copying', 'logging', 'exceptions', 'dumps', 'durable_persistence', 'classification', 'observed_at', 'credential_reference_observed', 'credential_secret_observed', 'sealed', 'record_digest'];
    public const array CLASSIFICATIONS = ['EXCLUDED', 'BOUNDED_IN_MEMORY', 'EXPOSED', 'UNPROVED'];
    public const array SECRET_EXCLUSION = ['clear_reference_persistence_permitted' => false, 'secret_persistence_permitted' => false, 'clear_reference_logging_permitted' => false, 'clear_reference_exception_permitted' => false, 'secret_observation_permitted' => false];
    public const array NON_AUTHORITIES = ['reads_live_credential' => false, 'resolves_credential' => false, 'changes_credential_boundary' => false, 'issues_capability' => false, 'starts_external_io' => false];

    private function __construct() {}
}
