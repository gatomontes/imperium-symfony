<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class OutboundEmailAuthorizationIssuanceContract
{
    public const string REQUEST_SCHEMA = 'imperium.curia-deterministic-outbound-email-request/v1';
    public const string DECISION_SCHEMA = 'imperium.imperator-deterministic-outbound-email-decision/v1';
    public const string ISSUANCE_SCHEMA = 'imperium.imperator-outbound-email-authorization-issuance/v1';
    public const int VERSION = 1;

    public const array COMPETENT_ROUTE = [
        'request_owner' => 'curia.seneschal',
        'decision_owner' => 'imperator',
        'authorization_issuer' => 'imperator.outbound-email-authorization-issuer',
        'credential_capability_issuer' => 'clavium.locksmith',
        'perimeter_consumer' => 'la-cortine.deterministic-boundary-executor',
        'perimeter_authority_issuer' => false,
    ];

    public const array REQUIRED_REQUEST_FIELDS = [
        'schema',
        'request_id',
        'instance_id',
        'requester',
        'holder',
        'purpose',
        'scope',
        'provider_safety',
        'requested_at',
        'expires_at',
        'authority_requested',
        'authority_granted',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_DECISION_FIELDS = [
        'schema',
        'decision_id',
        'instance_id',
        'source_request',
        'actor',
        'disposition',
        'rationale',
        'limitations',
        'issuance_authority',
        'decided_at',
        'expires_at',
        'external_action_performed',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_ISSUANCE_AUTHORITY_FIELDS = [
        'authority_id',
        'authority_single_use',
        'authority_exercisable',
        'issuer_service',
        'permitted_transition',
        'source_request_digest',
        'scope_digest',
        'expires_at',
        'consumed',
        'continuing_authority',
    ];

    public const array REQUIRED_ISSUANCE_FIELDS = [
        'schema',
        'issuance_id',
        'instance_id',
        'source_decision',
        'source_request',
        'consumed_issuance_authority',
        'issued_authorization',
        'issuer',
        'issued_at',
        'authority_issued',
        'external_action_performed',
        'sealed',
        'record_digest',
    ];

    public const array DISPOSITIONS = [
        'AUTHORIZED',
        'REFUSED',
    ];

    public const array ROUTE_RULES = [
        'request_grants_authority' => false,
        'refusal_opens_issuance_authority' => false,
        'authorized_decision_opens_one_issuance_authority' => true,
        'decision_performs_external_action' => false,
        'issuance_requires_exact_request_and_decision_digests' => true,
        'issuance_scope_must_equal_request_scope' => true,
        'issuance_expiry_must_not_exceed_decision_expiry' => true,
        'issuer_may_resolve_credentials' => false,
        'issuer_may_dispatch' => false,
        'issuer_may_start_external_io' => false,
    ];

    public const array CONTRACT_BOUNDARY = [
        'creates_request' => false,
        'records_decision' => false,
        'opens_issuance_authority' => false,
        'consumes_issuance_authority' => false,
        'issues_outbound_authorization' => false,
        'resolves_credentials' => false,
        'creates_execution_claim' => false,
        'dispatches_iron_gate' => false,
        'starts_external_io' => false,
        'opens_lazaretto' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
