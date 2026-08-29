<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ActivationPrincipalProvenanceEvidenceContract
{
    public const string SCHEMA = 'imperium.imperator.activation-principal-provenance-evidence/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'imperator.activation-principal-provenance-observer';
    public const array CONSUMER_POSTURES = ['imperator.activation-corridor-disposition'];
    public const array REQUIRED_FIELDS = ['schema', 'evidence_id', 'instance_id', 'principal', 'source_installation_authority', 'authority_field', 'lifecycle', 'observations', 'classification', 'observed_at', 'authority_granted', 'principal_installed', 'sealed', 'record_digest'];
    public const array REQUIRED_PRINCIPAL_FIELDS = ['principal_id', 'binding_id', 'generation', 'status', 'record_digest'];
    public const array REQUIRED_LIFECYCLE_FIELDS = ['producer_id', 'producer_schema', 'installed_at', 'expires_at', 'revocation_reference'];
    public const array CLASSIFICATIONS = ['PROVEN_CANONICAL', 'FRAGMENTED', 'ABSENT', 'INVALID'];
    public const array NON_AUTHORITIES = ['installs_principal' => false, 'grants_activation_authority' => false, 'issues_caller_authority' => false, 'activates_binding' => false, 'starts_external_io' => false];

    private function __construct() {}
}
