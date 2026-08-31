<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorExecutionAdoptionDecisionBoundaryContract as Decision;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3Contract as V3;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAdoptionBoundaryContractValidator as Validator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorToV3AdoptionJoinBoundaryContract as Join;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionRealizationBatch5Test extends TestCase
{
    public function testExactAdoptionDecisionAndJoinRemainInert(): void
    {
        $fixture = $this->fixture();
        $validator = new Validator();

        $validator->assertDecision($fixture['decision']);
        $validator->assertJoin($fixture['join']);
        $validator->assertExactChain(
            $fixture['decision'],
            $fixture['join'],
            $fixture['successor'],
            $fixture['adoption'],
            $fixture['v3'],
        );

        self::assertFalse($fixture['decision']['decision_performed']);
        self::assertFalse($fixture['join']['join_performed']);
        self::assertFalse($fixture['join']['live_adoption_performed']);
        self::assertSame('NOT_IMPLEMENTED', $fixture['v3']['status']);
    }

    public function testChangedDecisionReferenceRefusesTheExactChain(): void
    {
        $fixture = $this->fixture();
        $fixture['join']['adoption_decision']['digest'] = str_repeat('f', 64);
        $fixture['join'] = $this->seal($fixture['join']);

        $this->expectExceptionMessage('PBR520_SUCCESSOR_ADOPTION_CHAIN_INVALID');
        (new Validator())->assertExactChain(
            $fixture['decision'],
            $fixture['join'],
            $fixture['successor'],
            $fixture['adoption'],
            $fixture['v3'],
        );
    }

    public function testFalseDecisionOrLiveJoinRefuses(): void
    {
        $fixture = $this->fixture();
        $fixture['decision']['decision_performed'] = true;
        $fixture['decision'] = $this->seal($fixture['decision']);

        $this->expectExceptionMessage('PBR500_SUCCESSOR_ADOPTION_DECISION_BOUNDARY_INVALID');
        (new Validator())->assertDecision($fixture['decision']);
    }

    public function testContractsGrantNoAuthority(): void
    {
        foreach ([Decision::NON_AUTHORITIES, Join::NON_AUTHORITIES] as $posture) {
            foreach ($posture as $name => $value) {
                self::assertFalse($value, $name);
            }
        }

        self::assertFalse(Decision::INVARIANTS['decision_performed']);
        self::assertFalse(Join::INVARIANTS['join_performed']);
        self::assertFalse(Join::INVARIANTS['execution_admitted']);
    }

    public function testDocumentationAuthorizesProofOnly(): void
    {
        $doc = $this->document('docs/provider-binding-successor-production-realization-batch-5-adoption-contracts.md');
        $handoff = $this->document('docs/handoffs/provider-binding-successor-production-realization-batch-5-complete.md');

        foreach ([
            'BATCH_5_AUTHORITY_EMPTY_ADOPTION_DECISION_AND_SUCCESSOR_TO_V3_JOIN_CONTRACTS_COMPLETE',
            'The adoption decision status is CONTRACT_ONLY_NOT_DECIDED.',
            'The join status is CONTRACT_ONLY_NOT_JOINED.',
            'The v3 admission remains NOT_IMPLEMENTED.',
            'No adoption decision, join, execution admission, live adoption or effect occurs.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Production Realization Batch 6 interruption, replay, contention, expiry, revocation and adversarial proof may next be considered.',
            'may use caller-supplied disposable-root fixtures and read-only proof',
            'may not decide or perform adoption, admit execution',
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
            'instance_id' => $successor['instance_id'],
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
            'instance_id' => $decision['instance_id'],
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

        return compact('successor', 'adoption', 'v3', 'decision', 'join');
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
