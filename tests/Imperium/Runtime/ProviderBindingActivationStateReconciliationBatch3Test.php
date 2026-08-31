<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciliationFixtureInterruptionProofService as Proof;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciliationFixtureStore;

class ProviderBindingActivationStateReconciliationBatch3Test extends ProviderBindingActivationStateReconciliationBatch2Test
{
    public function testAllThreePathsRemainAbsentWhenInterruptedBeforeCommit(): void
    {
        $fixture = $this->fixture();
        $root = $this->temporaryRoot();
        try {
            $proof = new Proof($root);
            foreach ([
                [fn (array $arguments) => $proof->putTarget(...$arguments), $this->targetArguments($fixture)],
                [fn (array $arguments) => $proof->putDecisionInput(...$arguments), $this->decisionArguments($fixture)],
                [fn (array $arguments) => $proof->putLifecycleSuccessor(...$arguments), $this->successorArguments($fixture)],
            ] as [$put, $arguments]) {
                $arguments[] = Proof::CUT_BEFORE_COMMIT;
                $this->expectFailure('PBR300_INTERRUPTED_BEFORE_IMMUTABLE_COMMIT', fn () => $put($arguments));
            }

            foreach ([
                fn () => $proof->readTarget('binding-reconciliation-root.1'),
                fn () => $proof->readDecisionInput('binding-reconciliation-root.1'),
                fn () => $proof->readLifecycleSuccessor('binding-reconciliation-root.1'),
            ] as $read) {
                $this->expectFailure('PST112_IMMUTABLE_RECORD_ABSENT', $read);
            }
        } finally {
            $this->removeTree($root);
        }
    }

    public function testAllThreePathsRetainOneWinnerWhenInterruptedAfterCommit(): void
    {
        $fixture = $this->fixture();
        $root = $this->temporaryRoot();
        try {
            $proof = new Proof($root);
            $cases = [
                [
                    fn (array $arguments) => $proof->putTarget(...$arguments),
                    $this->targetArguments($fixture),
                    fn () => $proof->readTarget('binding-reconciliation-root.1'),
                    $fixture['target'],
                ],
                [
                    fn (array $arguments) => $proof->putDecisionInput(...$arguments),
                    $this->decisionArguments($fixture),
                    fn () => $proof->readDecisionInput('binding-reconciliation-root.1'),
                    $fixture['input'],
                ],
                [
                    fn (array $arguments) => $proof->putLifecycleSuccessor(...$arguments),
                    $this->successorArguments($fixture),
                    fn () => $proof->readLifecycleSuccessor('binding-reconciliation-root.1'),
                    $fixture['successor'],
                ],
            ];

            foreach ($cases as [$put, $arguments, $read, $expected]) {
                $arguments[] = Proof::CUT_AFTER_COMMIT;
                $this->expectFailure('PBR301_INTERRUPTED_AFTER_IMMUTABLE_COMMIT', fn () => $put($arguments));
                self::assertSame($expected, $read());
            }
        } finally {
            $this->removeTree($root);
        }
    }

    public function testExactReplayConvergesForEveryFixturePath(): void
    {
        $fixture = $this->fixture();
        $root = $this->temporaryRoot();
        try {
            $store = new ProviderBindingActivationReconciliationFixtureStore($root);
            foreach ([
                [fn () => $store->putTarget(...$this->targetArguments($fixture))],
                [fn () => $store->putDecisionInput(...$this->decisionArguments($fixture))],
                [fn () => $store->putLifecycleSuccessor(...$this->successorArguments($fixture))],
            ] as [$put]) {
                self::assertSame($put(), $put());
            }
        } finally {
            $this->removeTree($root);
        }
    }

    public function testDifferentArtifactsCannotWinTheSameRootOnAnyPath(): void
    {
        $fixture = $this->fixture();
        $contender = $this->contender($fixture);
        $root = $this->temporaryRoot();
        try {
            $store = new ProviderBindingActivationReconciliationFixtureStore($root);
            $store->putTarget(...$this->targetArguments($fixture));
            $this->expectFailure(
                'PST111_IMMUTABLE_RECORD_CONFLICT',
                fn () => $store->putTarget(...$this->targetArguments($contender)),
            );

            $store->putDecisionInput(...$this->decisionArguments($fixture));
            $this->expectFailure(
                'PST111_IMMUTABLE_RECORD_CONFLICT',
                fn () => $store->putDecisionInput(...$this->decisionArguments($contender)),
            );

            $store->putLifecycleSuccessor(...$this->successorArguments($fixture));
            $this->expectFailure(
                'PST111_IMMUTABLE_RECORD_CONFLICT',
                fn () => $store->putLifecycleSuccessor(...$this->successorArguments($contender)),
            );
        } finally {
            $this->removeTree($root);
        }
    }

    public function testExpiredAndRevokedAuthorityNeverReachImmutableCommit(): void
    {
        $root = $this->temporaryRoot();
        try {
            $store = new ProviderBindingActivationReconciliationFixtureStore($root);

            $expired = $this->fixture();
            $expired['target']['validity']['expires_at'] = '2026-08-31T00:30:00+00:00';
            $expired['target'] = $this->seal($expired['target']);
            $this->expectFailure(
                'PBR200_RECONCILED_TARGET_INVALID',
                fn () => $store->putTarget(...$this->targetArguments($expired)),
            );

            $revoked = $this->fixture();
            $revoked['input']['activation_authority']['revocation_reference'] = [
                'id' => 'revocation.1',
                'digest' => str_repeat('b', 64),
                'schema' => 'imperium.revocation/v1',
            ];
            $revoked['input'] = $this->seal($revoked['input']);
            $this->expectFailure(
                'PBR210_DECISION_INPUT_INVALID',
                fn () => $store->putDecisionInput(...$this->decisionArguments($revoked)),
            );

            $this->expectFailure(
                'PST112_IMMUTABLE_RECORD_ABSENT',
                fn () => $store->readTarget('binding-reconciliation-root.1'),
            );
            $this->expectFailure(
                'PST112_IMMUTABLE_RECORD_ABSENT',
                fn () => $store->readDecisionInput('binding-reconciliation-root.1'),
            );
        } finally {
            $this->removeTree($root);
        }
    }

    public function testBatch3DocumentationAuthorizesReadOnlyReconstructionNext(): void
    {
        $doc = $this->document(
            'docs/provider-binding-activation-state-reconciliation-batch-3-proof.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-activation-state-reconciliation-batch-3-complete.md',
        );

        foreach ([
            'BATCH_3_OFFLINE_INTERRUPTION_REPLAY_AND_CONTENTION_PROOF_COMPLETE',
            'absent before immutable commit',
            'one winner after immutable commit',
            'exact replay converges',
            'same-root contention',
            'expiry and revocation refuse before commit',
            'provider binding remains BOUND_INACTIVE',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }

        foreach ([
            'Only Provider Binding Activation State Reconciliation Batch 4',
            'read-only aggregate reconstruction',
            'may not activate a provider binding',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'approximately three batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function contender(array $fixture): array
    {
        $fixture['target']['target_id'] = 'reconciled-target.2';
        $fixture['target'] = $this->seal($fixture['target']);

        $fixture['input']['decision_input_id'] = 'binding-successor-decision-input.2';
        $fixture['input']['successor_target'] = $this->reference($fixture['target'], 'target_id');
        $fixture['input']['activation_authority']['authority_id'] = 'binding-successor-authority.2';
        $fixture['input']['activation_authority']['target_digest'] = $fixture['target']['record_digest'];
        $fixture['input'] = $this->seal($fixture['input']);

        $fixture['successor']['successor_id'] = 'binding-lifecycle-successor.2';
        $fixture['successor']['source_decision'] = $this->reference($fixture['input'], 'decision_input_id');
        $fixture['successor']['successor_target'] = $this->reference($fixture['target'], 'target_id');
        $fixture['successor']['consumed_activation_authority']['id'] =
            $fixture['input']['activation_authority']['authority_id'];
        $fixture['successor']['consumed_activation_authority']['digest'] =
            $fixture['input']['record_digest'];
        $fixture['successor'] = $this->seal($fixture['successor']);

        return $fixture;
    }

    private function temporaryRoot(): string
    {
        $root = sys_get_temp_dir().'/imperium-pbr-batch3-'.bin2hex(random_bytes(8));
        mkdir($root, 0770, true);

        return $root;
    }
}
