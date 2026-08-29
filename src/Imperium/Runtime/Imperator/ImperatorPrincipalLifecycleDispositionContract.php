<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ImperatorPrincipalLifecycleDispositionContract
{
    public const string SCHEMA = 'imperium.operator-root.imperator-principal-lifecycle-disposition/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'operator-root.imperator-principal-lifecycle-authority';
    public const array CONSUMER_POSTURES = ['mastermason.imperator-principal-lifecycle-transition', 'la-cortine.deterministic-transition-caller-authority-issuer', 'imperator.principal-read-only-reconstruction'];
    public const array DISPOSITIONS = ['ACTIVATE', 'RENEW', 'SUSPEND', 'SUPERSEDE', 'REVOKE', 'EXPIRE', 'RETIRE'];
    public const array REQUIRED_FIELDS = ['schema', 'disposition_id', 'instance_id', 'operator_root', 'source_principal_version', 'source_status', 'disposition', 'rationale', 'effective_at', 'successor_principal_version', 'authority_scope_changed', 'historical_attribution_preserved', 'caller_authority_issuance_permitted_after_effective_at', 'external_action_performed', 'sealed', 'record_digest'];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array NON_AUTHORITIES = ['rewrites_source_principal' => false, 'rewrites_historical_attribution' => false, 'creates_successor_without_authority' => false, 'widens_authority_scope' => false, 'issues_caller_authority' => false, 'reconsiders_corridor_disposition' => false, 'activates_provider_binding' => false, 'handles_credentials' => false, 'starts_external_io' => false];

    private function __construct() {}
}
