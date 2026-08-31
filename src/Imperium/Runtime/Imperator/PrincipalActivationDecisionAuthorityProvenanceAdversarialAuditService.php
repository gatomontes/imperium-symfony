<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

/** Pure audit of caller-supplied evidence. No persistence or effect dependency. */
final readonly class PrincipalActivationDecisionAuthorityProvenanceAdversarialAuditService
{
    public const array REQUIRED_PROOFS = [
        'exact_replay_converged',
        'changed_evidence_conflicted',
        'before_commit_left_no_winner',
        'after_commit_reconstructed_exact_winner',
        'single_combined_winner',
    ];

    public function audit(
        array $production,
        array $sourcePrincipal,
        array $scopeSuccessor,
        array $activationDisposition,
        array $envelope,
        array $issuanceAuthorization,
        array $proofs,
        \DateTimeImmutable $at,
    ): array {
        if (null !== ($issuanceAuthorization['revocation'] ?? null)
            || $at >= $this->date($issuanceAuthorization['expires_at'] ?? null)
            || $at < $this->date($issuanceAuthorization['issued_at'] ?? null)) {
            return $this->result($production, 'REFUSED', ['AUTHORIZATION_REVOKED_OR_EXPIRED'], $at);
        }

        try {
            $this->assertDigest(
                $production,
                PrincipalActivationDecisionAuthorityProvenanceProductionContract::REQUIRED_FIELDS,
                PrincipalActivationDecisionAuthorityProvenanceProductionContract::SCHEMA,
            );
            $principal = $production['pending_successor_principal'];
            (new PrincipalActivationDecisionAuthorityProvenanceBatch5BValidator())
                ->assertSuccessorPrincipal($principal, $sourcePrincipal, $scopeSuccessor);
            (new PrincipalActivationDecisionAuthorityProvenanceBatch5BValidator())
                ->assertProductionEnvelope($envelope, $issuanceAuthorization, $principal);
            $this->assertSecretExclusion($production);
            $this->assertLineage(
                $production,
                $activationDisposition,
                $envelope,
                $issuanceAuthorization,
            );
            $this->assertProofs($proofs);
        } catch (\Throwable $error) {
            return $this->result(
                $production,
                'CONFLICTED',
                ['ADVERSARIAL_CONFLICT:'.$error->getMessage()],
                $at,
            );
        }

        return $this->result(
            $production,
            'PASSED',
            [
                'ELIGIBILITY_AND_LINEAGE_EXACT',
                'REPLAY_CONTENTION_AND_INTERRUPTION_PROVED',
                'SECRET_EXCLUSION_AND_NON_AUTHORITY_PERIMETER_PRESERVED',
            ],
            $at,
        );
    }

    private function assertLineage(
        array $production,
        array $activationDisposition,
        array $envelope,
        array $authorization,
    ): void {
        $decision = $production['activation_decision'] ?? null;
        $consumption = $production['consumed_issuance_authorization'] ?? null;
        $expectedDecision = $this->seal([
            'schema' => ProviderExecutorPrincipalActivationDecisionContract::SCHEMA,
            'decision_id' => $envelope['decision_id'],
            'instance_id' => $envelope['instance_id'],
            'source_authority' => $envelope['source_authority'],
            'actor' => $envelope['actor'],
            'principal_attestation' => $envelope['principal_attestation'],
            'provider_assurance_admission' => $envelope['provider_assurance_admission'],
            'scope' => $envelope['scope'],
            'disposition' => $envelope['disposition'],
            'rationale' => $envelope['rationale'],
            'limitations' => $envelope['limitations'],
            'activation_authority' => $envelope['activation_authority'],
            'validity' => $envelope['validity'],
            'decided_at' => $production['produced_at'] ?? null,
            'external_action_performed' => false,
            'sealed' => true,
        ]);

        if ('ELIGIBLE' !== ($production['eligible_aggregate']['classification'] ?? null)
            || 'PENDING_ACTIVATION' !== ($production['pending_successor_principal']['status'] ?? null)
            || 'ACTIVE' !== ($production['effective_principal_status'] ?? null)
            || ($production['applied_lifecycle_disposition'] ?? null)
                !== $this->reference($activationDisposition, 'disposition_id')
            || !is_array($consumption)
            || PrincipalActivationDecisionAuthorityProvenanceProductionContract::REQUIRED_CONSUMPTION_FIELDS
                !== array_keys($consumption)
            || $consumption['source_authorization']
                !== $this->reference($authorization, 'issuance_authorization_id')
            || true !== $consumption['consumed']
            || false !== $consumption['continuing_authority']
            || $decision !== $expectedDecision
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || true !== ($decision['activation_authority']['authority_single_use'] ?? null)
            || true !== ($decision['activation_authority']['authority_exercisable'] ?? null)
            || false !== ($decision['activation_authority']['consumed'] ?? null)
            || false !== ($decision['activation_authority']['continuing_authority'] ?? null)
            || true !== ($production['combined_winner'] ?? null)) {
            throw new \RuntimeException('PAD600_LINEAGE_OR_COMBINED_WINNER_INVALID');
        }

        foreach ([
            'provider_executor_principal_activated',
            'provider_binding_activated',
            'activation_authority_consumed',
            'credential_or_capability_handled',
            'provider_invoked',
            'external_action_performed',
            'continuing_authority',
        ] as $field) {
            if (false !== ($production[$field] ?? null)) {
                throw new \RuntimeException('PAD601_NON_AUTHORITY_PERIMETER_VIOLATED:'.$field);
            }
        }
    }

    private function assertProofs(array $proofs): void
    {
        if (self::REQUIRED_PROOFS !== array_keys($proofs)) {
            throw new \RuntimeException('PAD602_ADVERSARIAL_PROOF_SET_INCOMPLETE');
        }
        foreach ($proofs as $proved) {
            if (true !== $proved) {
                throw new \RuntimeException('PAD603_ADVERSARIAL_PROOF_FAILED');
            }
        }
    }

    private function assertSecretExclusion(array $record): void
    {
        $forbidden = [
            'credential_secret',
            'credential_bytes',
            'api_key',
            'access_token',
            'refresh_token',
            'password',
            'process_local_capability',
        ];
        $walk = function (array $value) use (&$walk, $forbidden): void {
            foreach ($value as $key => $item) {
                $normalized = strtolower((string) $key);
                foreach ($forbidden as $fragment) {
                    if (str_contains($normalized, $fragment)
                        && false !== $item
                        && null !== $item) {
                        throw new \RuntimeException('PAD604_SECRET_MATERIAL_PRESENT');
                    }
                }
                if (is_array($item)) {
                    $walk($item);
                }
            }
        };
        $walk($record);
    }

    private function assertDigest(array $record, array $fields, string $schema): void
    {
        $plain = $record;
        $digest = $plain['record_digest'] ?? null;
        unset($plain['record_digest']);
        if ($fields !== array_keys($record)
            || $schema !== ($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null)
            || !is_string($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException('PAD605_IMMUTABLE_PRODUCTION_INVALID');
        }
    }

    private function result(
        array $production,
        string $classification,
        array $findings,
        \DateTimeImmutable $at,
    ): array {
        return [
            'schema' => PrincipalActivationDecisionAuthorityProvenanceAdversarialAuditResultContract::SCHEMA,
            'classification' => $classification,
            'findings' => $findings,
            'audited_production' => [
                'id' => $production['production_id'] ?? null,
                'digest' => $production['record_digest'] ?? null,
                'schema' => $production['schema'] ?? null,
            ],
            'audited_at' => $at->format(DATE_ATOM),
            'read_only' => true,
            'record_created' => false,
            'record_repaired' => false,
            'authority_issued' => false,
            'authority_consumed' => false,
            'principal_activated' => false,
            'binding_activated' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_action_performed' => false,
        ];
    }

    private function reference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function date(mixed $value): \DateTimeImmutable
    {
        if (!is_string($value)) {
            throw new \RuntimeException('PAD606_DATE_INVALID');
        }

        return new \DateTimeImmutable($value);
    }
}
