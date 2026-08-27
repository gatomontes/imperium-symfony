<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Governance;

final class InternalCognitionLeaseControls
{
    public static function operational(array $decision, array $request, string $issuedAt, string $expiresAt): array
    {
        return self::build('OPERATIONAL_COGNITION', $decision, $request, $issuedAt, $expiresAt, [
            'profile_model_requirements_digest' => $request['profile_model_requirements_digest'] ?? null,
            'iteration' => $request['iteration'] ?? null,
        ]);
    }

    public static function governance(array $decision, array $request, string $issuedAt, string $expiresAt): array
    {
        return self::build('GOVERNANCE_COGNITION', $decision, $request, $issuedAt, $expiresAt, [
            'cluster' => $request['cluster'] ?? null,
            'native_authority' => $request['source_governance_authority'] ?? null,
        ]);
    }

    public static function isExactOperational(mixed $metadata, array $decision, array $request, string $issuedAt, string $expiresAt): bool
    {
        return is_array($metadata) && $metadata === self::operational($decision, $request, $issuedAt, $expiresAt);
    }

    public static function isExactGovernance(mixed $metadata, array $decision, array $request, string $issuedAt, string $expiresAt): bool
    {
        return is_array($metadata) && $metadata === self::governance($decision, $request, $issuedAt, $expiresAt);
    }

    private static function build(string $family, array $decision, array $request, string $issuedAt, string $expiresAt, array $familyScope): array
    {
        $decisionId = $decision['decision_id'] ?? null;
        $decisionDigest = $decision['record_digest'] ?? null;
        $requestId = $request['request_id'] ?? null;
        $requestDigest = $request['record_digest'] ?? null;
        if (!is_string($decisionId) || !preg_match('/^[a-f0-9]{64}$/', $decisionDigest ?? '')
            || !is_string($requestId) || !preg_match('/^[a-f0-9]{64}$/', $requestDigest ?? '')
            || !is_string($decision['instance_id'] ?? null) || !is_array($request['target'] ?? null)
            || !is_string($decision['provider'] ?? null) || !is_string($decision['model'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/', $request['input_digest'] ?? '')) {
            throw new \RuntimeException('CAG400_INTERNAL_LEASE_CONTROLS_SOURCE_INVALID');
        }

        return [
            'schema' => 'imperium.internal-cognition-lease-controls/v1',
            'lease_family' => $family,
            'freshness' => [
                'issued_at' => $issuedAt,
                'expires_at' => $expiresAt,
                'revalidation_checkpoint' => 'DURABLE_INVOCATION_CLAIM',
                'source_evidence' => [
                    ['id' => $decisionId, 'digest' => $decisionDigest],
                    ['id' => $requestId, 'digest' => $requestDigest],
                ],
            ],
            'scope' => [
                'instance_id' => $decision['instance_id'],
                'target' => $request['target'],
                'provider' => $decision['provider'],
                'model' => $decision['model'],
                'model_configuration' => $decision['model_configuration'] ?? null,
                'input_digest' => $request['input_digest'],
                'family_scope' => $familyScope,
            ],
            'supersession_conditions' => [
                'SOURCE_DECISION_ID_OR_DIGEST_CHANGED',
                'SOURCE_REQUEST_ID_OR_DIGEST_CHANGED',
                'TARGET_CHANGED',
                'PROVIDER_OR_MODEL_CHANGED',
                'MODEL_CONFIGURATION_CHANGED',
                'INPUT_DIGEST_CHANGED',
            ],
            'invalidation_conditions' => [
                'SOURCE_DECISION_EXPIRED',
                'LEASE_EXPIRED',
                'NATIVE_AUTHORITY_CONSUMED',
                'SOURCE_EVIDENCE_MISSING_OR_TAMPERED',
                'SCOPE_OR_VERSION_LINEAGE_DRIFT',
            ],
            'stop_conditions' => [
                'CLAIM_VALIDATION_FAILED',
                'LEASE_ALREADY_CONSUMED',
                'NATIVE_AUTHORITY_ALREADY_CONSUMED',
                'EXPIRY_REACHED',
            ],
            'revocation' => [
                'status' => 'UNASSIGNED_DEFERRED_BOUNDARY',
                'authority_reference' => null,
                'propagation_implemented' => false,
                'lease_closure_implemented' => false,
            ],
            'metadata_authority_granted' => false,
            'continuation_authority' => false,
        ];
    }

    private function __construct()
    {
    }
}
