<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Governance;

final class ContinuousGovernanceContext
{
    public const string SCHEMA = 'imperium.continuous-governance-consequence-classification/v1';

    public static function advisoryCognition(array $authority): array
    {
        $instance = $authority['instance_id'] ?? null;
        $seat = $authority['seat'] ?? null;
        $source = $authority['source'] ?? null;
        $purpose = $authority['purpose'] ?? null;
        $inputDigest = $authority['input_digest'] ?? null;
        if (!is_string($instance) || '' === $instance
            || !is_string($seat) || !preg_match('/^[a-z0-9][a-z0-9._-]{2,127}$/', $seat)
            || !is_array($source) || !is_string($source['id'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/', $source['digest'] ?? '')
            || !is_string($purpose) || '' === $purpose
            || !preg_match('/^[a-f0-9]{64}$/', $inputDigest ?? '')) {
            throw new \RuntimeException('CAG101_CONTINUOUS_GOVERNANCE_CONTEXT_INVALID');
        }
        $office = explode('.', $seat, 2)[0];

        return [
            'schema' => self::SCHEMA,
            'governance_tier' => 'ADVISORY_COGNITION',
            'consequence_class' => 'INTERNAL_REVERSIBLE',
            'native_authority' => ['id' => $source['id'], 'digest' => $source['digest']],
            'purpose' => $purpose,
            'input_digest' => $inputDigest,
            'runtime_principal_references' => [
                ['principal_kind' => 'INSTANCE', 'principal_id' => $instance, 'role' => 'GOVERNED_RUNTIME', 'lifecycle_evidence' => 'REFERENCE_ONLY'],
                ['principal_kind' => 'OFFICE', 'principal_id' => $office, 'role' => 'COMPETENT_OFFICE', 'lifecycle_evidence' => 'REFERENCE_ONLY'],
                ['principal_kind' => 'SEAT', 'principal_id' => $seat, 'role' => 'NATIVE_AUTHORITY_TARGET', 'lifecycle_evidence' => 'AUTHORITY_REREAD'],
            ],
            'principal_identity_inferred' => false,
            'native_authority_replaced' => false,
            'authority_granted' => false,
            'authority_consumed' => false,
            'credential_authority' => false,
            'tool_authority' => false,
            'network_authority' => false,
            'perimeter_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'continuation_authority' => false,
            'revocation_authority' => false,
            'incident_authority' => false,
        ];
    }

    public static function isExactAdvisoryCognition(mixed $context, array $native): bool
    {
        if (!is_array($context)) {
            return false;
        }
        try {
            return $context === self::advisoryCognition($native);
        } catch (\Throwable) {
            return false;
        }
    }

    private function __construct()
    {
    }
}
