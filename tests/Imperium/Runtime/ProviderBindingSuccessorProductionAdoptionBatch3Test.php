<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorProductionAdoptionFixtureInterruptionProofService as Proof;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorProductionAdoptionFixtureStore;

class ProviderBindingSuccessorProductionAdoptionBatch3Test extends ProviderBindingSuccessorProductionAdoptionBatch2ATest
{
    public function testAllThreePathsRemainAbsentWhenInterruptedBeforeCommit(): void
    {
        $fixture = $this->productionFixture();
        $root = $this->temporaryRoot();
        try {
            $proof = new Proof($root);
            foreach ($this->proofCases($proof, $fixture) as [$put, $arguments]) {
                $arguments[] = Proof::CUT_BEFORE_COMMIT;
                $this->expectFailure(
                    'PBA800_INTERRUPTED_BEFORE_IMMUTABLE_COMMIT',
                    fn () => $put($arguments),
                );
            }
            foreach ($this->proofReads($proof) as $read) {
                $this->expectFailure('PST112_IMMUTABLE_RECORD_ABSENT', $read);
            }
        } finally {
            $this->removeTree($root);
        }
    }

    public function testAllThreePathsRetainOneWinnerWhenInterruptedAfterCommit(): void
    {
        $fixture = $this->productionFixture();
        $root = $this->temporaryRoot();
        try {
            $proof = new Proof($root);
            $expected = [$fixture['decision'], $fixture['authority'], $fixture['adoption']];
            foreach ($this->proofCases($proof, $fixture) as $index => [$put, $arguments]) {
                $arguments[] = Proof::CUT_AFTER_COMMIT;
                $this->expectFailure(
                    'PBA801_INTERRUPTED_AFTER_IMMUTABLE_COMMIT',
                    fn () => $put($arguments),
                );
                self::assertSame($expected[$index], $this->proofReads($proof)[$index]());
            }
        } finally {
            $this->removeTree($root);
        }
    }

    public function testExactReplayConvergesForEveryFixturePath(): void
    {
        $fixture = $this->productionFixture();
        $root = $this->temporaryRoot();
        try {
            $store = new ProviderBindingSuccessorProductionAdoptionFixtureStore($root);
            foreach ([
                fn () => $store->putDecision(...$this->productionDecisionArguments($fixture)),
                fn () => $store->putAuthority(...$this->productionAuthorityArguments($fixture)),
                fn () => $store->putAdoptionTarget(...$this->adoptionArguments($fixture)),
            ] as $put) {
                self::assertSame($put(), $put());
            }
        } finally {
            $this->removeTree($root);
        }
    }

    public function testDifferentArtifactsCannotWinTheSameReplayRootOnAnyPath(): void
    {
        $fixture = $this->productionFixture();
        $contender = $this->contender($fixture);
        $root = $this->temporaryRoot();
        try {
            $store = new ProviderBindingSuccessorProductionAdoptionFixtureStore($root);
            $store->putDecision(...$this->productionDecisionArguments($fixture));
            $this->expectFailure(
                'PST111_IMMUTABLE_RECORD_CONFLICT',
                fn () => $store->putDecision(...$this->productionDecisionArguments($contender)),
            );

            $store->putAuthority(...$this->productionAuthorityArguments($fixture));
            $this->expectFailure(
                'PST111_IMMUTABLE_RECORD_CONFLICT',
                fn () => $store->putAuthority(...$this->productionAuthorityArguments($contender)),
            );

            $store->putAdoptionTarget(...$this->adoptionArguments($fixture));
            $this->expectFailure(
                'PST111_IMMUTABLE_RECORD_CONFLICT',
                fn () => $store->putAdoptionTarget(...$this->adoptionArguments($contender)),
            );
        } finally {
            $this->removeTree($root);
        }
    }

    public function testExpiredAndRevokedDecisionLineageNeverReachesCommit(): void
    {
        $root = $this->temporaryRoot();
        try {
            $store = new ProviderBindingSuccessorProductionAdoptionFixtureStore($root);

            $expired = $this->productionFixture();
            $expired['decision']['validity']['expires_at'] = '2026-08-31T00:30:00+00:00';
            $expired['decision'] = $this->seal($expired['decision']);
            $this->expectFailure(
                'PBA700_PRODUCTION_DECISION_INVALID',
                fn () => $store->putDecision(...$this->productionDecisionArguments($expired)),
            );

            $revoked = $this->productionFixture();
            $revoked['decision']['validity']['revocation_reference'] = [
                'id' => 'revocation.1',
                'digest' => str_repeat('c', 64),
                'schema' => 'imperium.revocation/v1',
            ];
            $revoked['decision'] = $this->seal($revoked['decision']);
            $this->expectFailure(
                'PBA700_PRODUCTION_DECISION_INVALID',
                fn () => $store->putDecision(...$this->productionDecisionArguments($revoked)),
            );

            $this->expectFailure(
                'PST112_IMMUTABLE_RECORD_ABSENT',
                fn () => $store->readDecision('binding-reconciliation-root.1'),
            );
        } finally {
            $this->removeTree($root);
        }
    }

    public function testDocumentationAuthorizesReadOnlyAggregateReconstructionNext(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-production-adoption-batch-3-proof.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-production-adoption-batch-3-complete.md',
        );

        foreach ([
            'BATCH_3_OFFLINE_INTERRUPTION_REPLAY_AND_CONTENTION_PROOF_COMPLETE',
            'absent before immutable commit',
            'one winner after immutable commit',
            'exact replay converges',
            'same-root contention',
            'expiry and revocation refuse before commit',
            'The provider binding remains BOUND_INACTIVE.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Production Adoption Batch 4 read-only aggregate reconstruction may next be considered.',
            'may not activate a principal or provider binding',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function proofCases(Proof $proof, array $fixture): array
    {
        return [
            [fn (array $args) => $proof->putDecision(...$args), $this->productionDecisionArguments($fixture)],
            [fn (array $args) => $proof->putAuthority(...$args), $this->productionAuthorityArguments($fixture)],
            [fn (array $args) => $proof->putAdoptionTarget(...$args), $this->adoptionArguments($fixture)],
        ];
    }

    private function proofReads(Proof $proof): array
    {
        return [
            fn () => $proof->readDecision('binding-reconciliation-root.1'),
            fn () => $proof->readAuthority('binding-reconciliation-root.1'),
            fn () => $proof->readAdoptionTarget('binding-reconciliation-root.1'),
        ];
    }

    private function contender(array $fixture): array
    {
        $fixture['decision']['decision_id'] = 'successor-production-decision.2';
        $fixture['decision']['limitations'] = ['offline_fixture_only', 'contender'];
        $fixture['decision']['successor_creation_authority_issuance_target']['authority_id'] =
            'successor-creation-authority.2';
        $fixture['decision'] = $this->seal($fixture['decision']);

        $fixture['authority']['authority_id'] = 'successor-creation-authority.2';
        $fixture['authority']['source_decision'] =
            $this->reference($fixture['decision'], 'decision_id');
        $fixture['authority']['source_issuance_target'] =
            $fixture['decision']['successor_creation_authority_issuance_target'];
        $fixture['authority'] = $this->seal($fixture['authority']);

        $fixture['successor']['successor_id'] = 'binding-lifecycle-successor.2';
        $fixture['successor'] = $this->seal($fixture['successor']);
        $fixture['adoption']['completed_successor'] =
            $this->reference($fixture['successor'], 'successor_id');
        $fixture['adoption'] = $this->seal($fixture['adoption']);

        return $fixture;
    }

    private function temporaryRoot(): string
    {
        $root = sys_get_temp_dir().'/imperium-pba-batch3-'.bin2hex(random_bytes(8));
        mkdir($root, 0770, true);

        return $root;
    }
}
