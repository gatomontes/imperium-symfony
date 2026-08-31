<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3Contract as V3;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3ContractValidator as Validator;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionRealizationBatch4Test extends TestCase
{
    public function testExactSuccessorAdmissionBoundaryValidatesButRemainsUnimplemented(): void
    {
        $fixture = $this->fixture();
        $validator = new Validator();

        $validator->assert($fixture['admission']);
        $validator->assertJoins(
            $fixture['admission'],
            $fixture['successor'],
            $fixture['winner'],
            $fixture['adoption'],
        );

        self::assertSame('NOT_IMPLEMENTED', $fixture['admission']['status']);
        self::assertFalse($fixture['admission']['execution_admitted']);
        self::assertFalse($fixture['admission']['live_adoption_performed']);
        self::assertFalse($fixture['admission']['effect_start_permitted']);
    }

    public function testChangedSameRootJoinRefuses(): void
    {
        $fixture = $this->fixture();
        $fixture['winner']['replay_contention_root'] = 'changed-root.1';
        $fixture['winner'] = $this->seal($fixture['winner']);

        $this->expectExceptionMessage('PBR410_SUCCESSOR_ADMISSION_V3_JOIN_INVALID');
        (new Validator())->assertJoins(
            $fixture['admission'],
            $fixture['successor'],
            $fixture['winner'],
            $fixture['adoption'],
        );
    }

    public function testFalseImplementationOrSecretMaterialRefuses(): void
    {
        $fixture = $this->fixture();
        $fixture['admission']['status'] = 'IMPLEMENTED';
        $fixture['admission'] = $this->seal($fixture['admission']);

        $this->expectExceptionMessage('PBR400_SUCCESSOR_ADMISSION_V3_BOUNDARY_INVALID');
        (new Validator())->assert($fixture['admission']);
    }

    public function testContractGrantsNoAuthority(): void
    {
        foreach (V3::NON_AUTHORITIES as $name => $value) {
            self::assertFalse($value, $name);
        }

        self::assertSame('NOT_IMPLEMENTED', V3::INVARIANTS['status']);
        self::assertFalse(V3::INVARIANTS['execution_admitted']);
        self::assertFalse(V3::INVARIANTS['live_adoption_performed']);
    }

    public function testDocumentationAuthorizesBatchFiveContractsOnly(): void
    {
        $doc = $this->document('docs/provider-binding-successor-production-realization-batch-4-v3-admission.md');
        $handoff = $this->document('docs/handoffs/provider-binding-successor-production-realization-batch-4-complete.md');

        foreach ([
            'BATCH_4_AUTHORITY_EMPTY_SUCCESSOR_ADMISSION_V3_CONTRACT_AND_VALIDATOR_COMPLETE',
            'The v3 status remains NOT_IMPLEMENTED.',
            'Execution admitted remains false.',
            'Live adoption performed remains false.',
            'Credential resolution, provider invocation, external I/O and effect start remain forbidden.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Production Realization Batch 5 explicit adoption decision and successor-to-v3 join contracts may next be considered.',
            'may define authority-empty adoption-decision and join contracts',
            'may not decide or perform live adoption, admit execution',
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
        $successor = $this->seal([
            'schema' => 'imperium.la-cortine.provider-binding-activation-reconciled-lifecycle-successor/v1',
            'successor_id' => 'binding-successor.1',
            'instance_id' => 'instance.1',
            'operation_scope' => ['operation' => 'provider.binding.successor.production'],
            'replay_contention_root' => 'binding-reconciliation-root.1',
            'sealed' => true,
        ]);
        $winner = $this->seal([
            'schema' => 'imperium.la-cortine.provider-binding-successor-atomic-creation-winner-boundary/v1',
            'winner_boundary_id' => 'successor-atomic-winner-boundary.1',
            'replay_contention_root' => $successor['replay_contention_root'],
            'sealed' => true,
        ]);
        $adoption = $this->seal([
            'schema' => 'imperium.la-cortine.provider-binding-successor-execution-adoption-target/v1',
            'adoption_target_id' => 'successor-adoption-target.1',
            'replay_contention_root' => $successor['replay_contention_root'],
            'sealed' => true,
        ]);

        $admission = $this->seal([
            'schema' => V3::SCHEMA,
            'admission_boundary_id' => 'successor-admission-v3.1',
            'instance_id' => $successor['instance_id'],
            'completed_successor' => $this->reference($successor, 'successor_id'),
            'atomic_creation_winner' => $this->reference($winner, 'winner_boundary_id'),
            'adoption_target' => $this->reference($adoption, 'adoption_target_id'),
            'executor_principal' => [
                'id' => 'provider-executor-principal.1',
                'digest' => str_repeat('a', 64),
                'schema' => 'imperium.imperator.provider-executor-principal/v1',
            ],
            'execution_boundary' => [
                'id' => 'provider-execution-boundary.1',
                'digest' => str_repeat('b', 64),
                'schema' => 'imperium.la-cortine.provider-execution-boundary/v1',
            ],
            'operation_scope' => $successor['operation_scope'],
            'replay_contention_root' => $successor['replay_contention_root'],
            'legacy_activation_substitution_permitted' => false,
            'successor_synthesis_permitted' => false,
            'original_binding_mutation_permitted' => false,
            'credential_resolution_permitted' => false,
            'provider_invocation_permitted' => false,
            'external_io_permitted' => false,
            'effect_start_permitted' => false,
            'execution_admitted' => false,
            'live_adoption_performed' => false,
            'continuing_authority' => false,
            'status' => V3::STATUS,
            'sealed' => true,
        ]);

        return compact('successor', 'winner', 'adoption', 'admission');
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
