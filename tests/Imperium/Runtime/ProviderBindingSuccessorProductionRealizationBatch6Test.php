<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorExecutionAdoptionDecisionBoundaryContract as Decision;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionRealizationAdversarialAuditResultContract as Result;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionRealizationAdversarialAuditService as Audit;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3Contract as V3;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorToV3AdoptionJoinBoundaryContract as Join;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionRealizationBatch6Test extends TestCase
{
    public function testExactCallerSuppliedChainPassesReadOnlyAudit(): void
    {
        $result = $this->audit($this->fixture());

        self::assertSame('PASSED', $result['classification']);
        self::assertSame(Result::SCHEMA, $result['schema']);
        self::assertSame(Result::REQUIRED_FIELDS, array_keys($result));
        self::assertTrue($result['read_only']);

        foreach (array_slice($result, 6) as $field => $value) {
            self::assertFalse($value, $field);
        }
    }

    public function testEveryProofIsRequiredAndTrue(): void
    {
        $fixture = $this->fixture();
        $proofs = $this->proofs();
        array_pop($proofs);

        self::assertSame('CONFLICTED', $this->audit($fixture, $proofs)['classification']);

        $proofs = $this->proofs();
        $proofs['same_root_contention_proved'] = false;
        self::assertSame('CONFLICTED', $this->audit($fixture, $proofs)['classification']);
    }

    public function testExpiryAndRevocationRefuseBeforeValidation(): void
    {
        $expired = $this->fixture();
        $expired['lifecycle']['expires_at'] = '2026-08-31T19:00:00+00:00';
        self::assertSame('REFUSED', $this->audit($expired)['classification']);

        $revoked = $this->fixture();
        $revoked['lifecycle']['revocation_reference'] = [
            'id' => 'revocation.1',
            'digest' => str_repeat('f', 64),
            'schema' => 'imperium.imperator.revocation/v1',
        ];
        self::assertSame('REFUSED', $this->audit($revoked)['classification']);
    }

    public function testChangedRootAndSecretAttacksConflict(): void
    {
        $changed = $this->fixture();
        $changed['join']['replay_contention_root'] = 'changed-root.1';
        $changed['join'] = $this->seal($changed['join']);
        self::assertSame('CONFLICTED', $this->audit($changed)['classification']);

        $secret = $this->fixture();
        $secret['successor']['credential_reference'] = 'env://forbidden';
        $secret['successor'] = $this->seal($secret['successor']);
        self::assertSame('CONFLICTED', $this->audit($secret)['classification']);
    }

    public function testAuditSourceHasNoPersistenceOrEffectDependency(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/ProviderBindingSuccessorProductionRealizationAdversarialAuditService.php',
        );
        self::assertNotFalse($source);

        foreach ([
            'AtomicTransition',
            'AuthorityConsumptionStore',
            'ImmutableRecordStore',
            'FixtureStore',
            'CredentialBroker',
            'ProviderTransport',
            'public function produce',
            'public function issue',
            'public function consume',
            'public function adopt',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesTerminalAuditOnly(): void
    {
        $doc = $this->document('docs/provider-binding-successor-production-realization-batch-6-adversarial-proof.md');
        $handoff = $this->document('docs/handoffs/provider-binding-successor-production-realization-batch-6-complete.md');

        foreach ([
            'BATCH_6_READ_ONLY_INTERRUPTION_REPLAY_CONTENTION_AND_ADVERSARIAL_PROOF_PASSED',
            'interruption before commit leaves no winner',
            'exact replay converges',
            'changed evidence and same-root contenders conflict',
            'expired or revoked lineage refuses',
            'v3 remains NOT_IMPLEMENTED',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Production Realization Batch 7 terminal audit and campaign closure may next be considered.',
            'may write terminal documentation and focused audit guards only',
            'may not decide or perform adoption, admit execution, issue or consume authority',
            'may not activate a principal or provider binding',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
            'The v3 execution admission remains NOT_IMPLEMENTED.',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function audit(array $fixture, ?array $proofs = null): array
    {
        return (new Audit())->audit(
            $fixture['decision'],
            $fixture['join'],
            $fixture['successor'],
            $fixture['adoption'],
            $fixture['v3'],
            $fixture['lifecycle'],
            $proofs ?? $this->proofs(),
            new \DateTimeImmutable('2026-08-31T20:00:00+00:00'),
        );
    }

    private function proofs(): array
    {
        return array_fill_keys(Audit::REQUIRED_PROOFS, true);
    }

    private function fixture(): array
    {
        $root = 'binding-reconciliation-root.1';
        $scope = ['operation' => 'provider.binding.successor.production'];
        $successor = $this->seal([
            'schema' => 'imperium.la-cortine.provider-binding-activation-reconciled-lifecycle-successor/v1',
            'successor_id' => 'binding-successor.1',
            'instance_id' => 'instance.1',
            'operation_scope' => $scope,
            'replay_contention_root' => $root,
            'sealed' => true,
        ]);
        $adoption = $this->seal([
            'schema' => 'imperium.la-cortine.provider-binding-successor-execution-adoption-target/v1',
            'adoption_target_id' => 'successor-adoption-target.1',
            'replay_contention_root' => $root,
            'sealed' => true,
        ]);
        $v3 = $this->seal([
            'schema' => V3::SCHEMA,
            'admission_boundary_id' => 'successor-admission-v3.1',
            'replay_contention_root' => $root,
            'status' => V3::STATUS,
            'execution_admitted' => false,
            'live_adoption_performed' => false,
            'sealed' => true,
        ]);
        $decision = $this->seal([
            'schema' => Decision::SCHEMA,
            'decision_boundary_id' => 'successor-adoption-decision.1',
            'instance_id' => 'instance.1',
            'exact_principal' => [
                'id' => 'imperator-successor-adoption-principal.1',
                'digest' => str_repeat('a', 64),
                'schema' => 'imperium.imperator.provider-binding-successor-adoption-principal/v1',
            ],
            'completed_successor' => $this->reference($successor, 'successor_id'),
            'adoption_target' => $this->reference($adoption, 'adoption_target_id'),
            'v3_admission' => $this->reference($v3, 'admission_boundary_id'),
            'operation_scope' => $scope,
            'replay_contention_root' => $root,
            'decision_scope' => Decision::DECISION_SCOPE,
            'permitted_dispositions' => Decision::PERMITTED_DISPOSITIONS,
            'authority_empty' => true,
            'decision_performed' => false,
            'disposition' => 'NOT_DECIDED',
            'live_adoption_performed' => false,
            'continuing_authority' => false,
            'status' => Decision::STATUS,
            'sealed' => true,
        ]);
        $join = $this->seal([
            'schema' => Join::SCHEMA,
            'join_boundary_id' => 'successor-to-v3-join.1',
            'instance_id' => 'instance.1',
            'adoption_decision' => $this->reference($decision, 'decision_boundary_id'),
            'completed_successor' => $decision['completed_successor'],
            'adoption_target' => $decision['adoption_target'],
            'v3_admission' => $decision['v3_admission'],
            'operation_scope' => $scope,
            'replay_contention_root' => $root,
            'exact_join_required' => true,
            'adoption_decision_authorized' => false,
            'join_performed' => false,
            'execution_admitted' => false,
            'live_adoption_performed' => false,
            'effect_started' => false,
            'continuing_authority' => false,
            'status' => Join::STATUS,
            'sealed' => true,
        ]);

        return [
            'successor' => $successor,
            'adoption' => $adoption,
            'v3' => $v3,
            'decision' => $decision,
            'join' => $join,
            'lifecycle' => [
                'effective_at' => '2026-08-31T19:00:00+00:00',
                'expires_at' => '2026-08-31T21:00:00+00:00',
                'revocation_reference' => null,
            ],
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
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function document(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
