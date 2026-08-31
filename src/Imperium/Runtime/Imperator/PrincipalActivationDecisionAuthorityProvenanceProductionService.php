<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PrincipalActivationDecisionAuthorityProvenanceProductionService
{
    public const string PRODUCTIONS =
        'var/imperium/runtime/principal-activation-decision-authority-provenance-productions';

    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private PrincipalActivationDecisionAuthorityProvenanceBatch5BValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
        $this->validator = new PrincipalActivationDecisionAuthorityProvenanceBatch5BValidator();
    }

    public function produce(
        array $aggregate,
        array $sourcePrincipal,
        array $scopeSuccessor,
        array $activationDisposition,
        array $successorPrincipal,
        array $envelope,
        array $issuanceAuthorization,
        \DateTimeImmutable $at,
    ): array {
        $this->assertEligible($aggregate, $issuanceAuthorization, $activationDisposition, $at);
        $this->validator->assertSuccessorPrincipal(
            $successorPrincipal,
            $sourcePrincipal,
            $scopeSuccessor,
        );
        $this->validator->assertProductionEnvelope(
            $envelope,
            $issuanceAuthorization,
            $successorPrincipal,
        );

        $target = implode(':', [
            $aggregate['instance_id'],
            $successorPrincipal['principal_version_id'],
            $issuanceAuthorization['issuance_authorization_id'],
            $envelope['decision_id'],
        ]);

        return $this->atomic->run(
            'decision-provenance-production:'.hash('sha256', $target),
            function () use (
                $aggregate,
                $activationDisposition,
                $successorPrincipal,
                $envelope,
                $issuanceAuthorization,
                $at,
                $target,
            ): array {
                $decision = $this->seal([
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
                    'decided_at' => $at->format(DATE_ATOM),
                    'external_action_performed' => false,
                    'sealed' => true,
                ]);

                $production = $this->seal([
                    'schema' => PrincipalActivationDecisionAuthorityProvenanceProductionContract::SCHEMA,
                    'production_id' => 'decision-provenance-production-'.hash('sha256', $target),
                    'instance_id' => $aggregate['instance_id'],
                    'eligible_aggregate' => [
                        'schema' => $aggregate['schema'],
                        'classification' => 'ELIGIBLE',
                        'reconstructed_at' => $aggregate['reconstructed_at'],
                        'references' => $aggregate['references'],
                    ],
                    'pending_successor_principal' => $successorPrincipal,
                    'applied_lifecycle_disposition' =>
                        $this->reference($activationDisposition, 'disposition_id'),
                    'effective_principal_status' => 'ACTIVE',
                    'consumed_issuance_authorization' => [
                        'source_authorization' => $this->reference(
                            $issuanceAuthorization,
                            'issuance_authorization_id',
                        ),
                        'consumed_at' => $at->format(DATE_ATOM),
                        'consumed' => true,
                        'continuing_authority' => false,
                    ],
                    'activation_decision' => $decision,
                    'combined_winner' => true,
                    'produced_at' => $at->format(DATE_ATOM),
                    'provider_executor_principal_activated' => false,
                    'provider_binding_activated' => false,
                    'activation_authority_consumed' => false,
                    'credential_or_capability_handled' => false,
                    'provider_invoked' => false,
                    'external_action_performed' => false,
                    'continuing_authority' => false,
                    'sealed' => true,
                ]);
                $this->assertProduction($production, $successorPrincipal, $activationDisposition);

                return $this->records->put(
                    self::PRODUCTIONS,
                    $production['production_id'],
                    $production,
                );
            },
        );
    }

    public function reconstruct(string $productionId): array
    {
        $production = $this->records->read(self::PRODUCTIONS, $productionId);
        if (PrincipalActivationDecisionAuthorityProvenanceProductionContract::REQUIRED_FIELDS
            !== array_keys($production)) {
            throw new \RuntimeException('PAD5C03_PRODUCTION_INVALID');
        }

        return $production;
    }

    private function assertEligible(
        array $aggregate,
        array $authorization,
        array $activationDisposition,
        \DateTimeImmutable $at,
    ): void {
        if (PrincipalActivationDecisionAuthorityProvenanceAggregateResultContract::REQUIRED_FIELDS
                !== array_keys($aggregate)
            || PrincipalActivationDecisionAuthorityProvenanceAggregateResultContract::SCHEMA
                !== ($aggregate['schema'] ?? null)
            || 'ELIGIBLE' !== ($aggregate['classification'] ?? null)
            || true !== ($aggregate['read_only'] ?? null)
            || ($aggregate['instance_id'] ?? null) !== ($authorization['instance_id'] ?? null)
            || ($aggregate['references']['issuance_authorization'] ?? null)
                !== $this->reference($authorization, 'issuance_authorization_id')
            || ($aggregate['references']['activation_disposition'] ?? null)
                !== $this->reference($activationDisposition, 'disposition_id')
            || ($authorization['activation_disposition'] ?? null)
                !== $this->reference($activationDisposition, 'disposition_id')
            || true === ($authorization['consumed'] ?? null)
            || null !== ($authorization['revocation'] ?? null)
            || $at < new \DateTimeImmutable($authorization['issued_at'] ?? '')
            || $at >= new \DateTimeImmutable($authorization['expires_at'] ?? '')
            || 'PENDING_ACTIVATION' !== ($activationDisposition['source_status'] ?? null)
            || 'ACTIVATE' !== ($activationDisposition['disposition'] ?? null)
            || new \DateTimeImmutable($activationDisposition['effective_at'] ?? '') > $at) {
            throw new \RuntimeException('PAD5C00_PRODUCTION_NOT_ELIGIBLE');
        }
        foreach ([
            'record_created',
            'record_repaired',
            'scope_granted',
            'authority_issued',
            'authority_consumed',
            'principal_created',
            'principal_activated',
            'binding_activated',
            'activation_decision_created',
            'source_artifact_mutated',
            'credential_or_capability_handled',
            'provider_invoked',
            'external_action_performed',
        ] as $field) {
            if (false !== ($aggregate[$field] ?? null)) {
                throw new \RuntimeException('PAD5C00_PRODUCTION_NOT_ELIGIBLE');
            }
        }
    }

    private function assertProduction(
        array $production,
        array $successorPrincipal,
        array $activationDisposition,
    ): void {
        $decision = $production['activation_decision'] ?? null;
        $consumption = $production['consumed_issuance_authorization'] ?? null;
        if (PrincipalActivationDecisionAuthorityProvenanceProductionContract::REQUIRED_FIELDS
                !== array_keys($production)
            || !is_array($decision)
            || ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_FIELDS
                !== array_keys($decision)
            || !is_array($consumption)
            || PrincipalActivationDecisionAuthorityProvenanceProductionContract::REQUIRED_CONSUMPTION_FIELDS
                !== array_keys($consumption)
            || $production['pending_successor_principal'] !== $successorPrincipal
            || 'PENDING_ACTIVATION' !== $successorPrincipal['status']
            || 'ACTIVE' !== $production['effective_principal_status']
            || $production['applied_lifecycle_disposition']
                !== $this->reference($activationDisposition, 'disposition_id')
            || true !== $consumption['consumed']
            || false !== $consumption['continuing_authority']
            || true !== $production['combined_winner']
            || true === $decision['activation_authority']['consumed']
            || false !== $decision['activation_authority']['continuing_authority']
            || false !== $decision['external_action_performed']
            || false !== $production['provider_executor_principal_activated']
            || false !== $production['provider_binding_activated']
            || false !== $production['activation_authority_consumed']
            || false !== $production['credential_or_capability_handled']
            || false !== $production['provider_invoked']
            || false !== $production['external_action_performed']
            || false !== $production['continuing_authority']) {
            throw new \RuntimeException('PAD5C03_PRODUCTION_INVALID');
        }
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
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }
}
