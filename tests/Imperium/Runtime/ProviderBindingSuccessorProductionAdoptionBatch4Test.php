<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorProductionAdoptionAggregateReconstructor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorProductionAdoptionFixtureStore;

class ProviderBindingSuccessorProductionAdoptionBatch4Test extends ProviderBindingSuccessorProductionAdoptionBatch3Test
{
    public function testCompleteExactChainReconstructsEligibleAndReadOnly(): void
    {
        $fixture = $this->productionFixture();
        $root = $this->temporaryRootForBatch4();
        try {
            $this->storeProductionChain($root, $fixture);
            $result = $this->reconstruct($root, $fixture);

            self::assertSame(
                'ELIGIBLE_OFFLINE_PRODUCTION_ADOPTION_EVIDENCE',
                $result['classification'],
            );
            self::assertSame([], $result['reasons']);
            self::assertSame(
                'successor-creation-authority.1',
                $result['chain']['successor_creation_authority']['id'],
            );
            self::assertSame('NOT_IMPLEMENTED', $result['chain']['required_admission_contract']['status']);
            self::assertTrue($result['read_only']);
            foreach ($result as $name => $value) {
                if (str_ends_with($name, '_created')
                    || str_ends_with($name, '_repaired')
                    || str_ends_with($name, '_replaced')
                    || str_ends_with($name, '_promoted')
                    || str_ends_with($name, '_issued')
                    || str_ends_with($name, '_consumed')
                    || str_ends_with($name, '_decided')
                    || str_ends_with($name, '_performed')
                    || str_ends_with($name, '_changed')
                    || str_ends_with($name, '_activated')
                    || str_ends_with($name, '_handled')
                    || str_ends_with($name, '_invoked')
                    || str_ends_with($name, '_started')) {
                    self::assertFalse($value, $name);
                }
            }
            self::assertFalse($result['continuing_authority']);
        } finally {
            $this->removeTree($root);
        }
    }

    public function testMissingChainClassifiesIncompleteWithoutCreatingFixtures(): void
    {
        $fixture = $this->productionFixture();
        $root = $this->temporaryRootForBatch4();
        try {
            $result = $this->reconstruct($root, $fixture);
            self::assertSame('INCOMPLETE', $result['classification']);
            self::assertSame(['FIXTURE_ABSENT'], $result['reasons']);
            self::assertFalse($result['fixture_created']);
            self::assertDirectoryDoesNotExist(
                $root.'/'.ProviderBindingSuccessorProductionAdoptionFixtureStore::DECISIONS,
            );
        } finally {
            $this->removeTree($root);
        }
    }

    public function testCorruptFixtureClassifiesConflictedWithoutRepair(): void
    {
        $fixture = $this->productionFixture();
        $root = $this->temporaryRootForBatch4();
        try {
            $this->storeProductionChain($root, $fixture);
            $path = $root.'/'.ProviderBindingSuccessorProductionAdoptionFixtureStore::DECISIONS
                .'/binding-reconciliation-root.1.json';
            $corrupt = str_replace(
                '"disposition": "AUTHORIZED"',
                '"disposition": "REFUSED"',
                (string) file_get_contents($path),
            );
            file_put_contents($path, $corrupt);
            $before = hash_file('sha256', $path);

            $result = $this->reconstruct($root, $fixture);
            self::assertSame('CONFLICTED', $result['classification']);
            self::assertContains('PST113_IMMUTABLE_RECORD_TAMPERED', $result['reasons']);
            self::assertFalse($result['fixture_repaired']);
            self::assertSame($before, hash_file('sha256', $path));
        } finally {
            $this->removeTree($root);
        }
    }

    public function testIneligibleSuppliedLineageClassifiesRefused(): void
    {
        $fixture = $this->productionFixture();
        $root = $this->temporaryRootForBatch4();
        try {
            $this->storeProductionChain($root, $fixture);
            $fixture['binding']['status'] = 'BOUND_ACTIVE';
            $fixture['binding'] = $this->seal($fixture['binding']);

            $result = $this->reconstruct($root, $fixture);
            self::assertSame('REFUSED', $result['classification']);
            self::assertContains('PBR200_RECONCILED_TARGET_INVALID', $result['reasons']);
            self::assertFalse($result['artifact_replaced']);
            self::assertFalse($result['artifact_promoted']);
        } finally {
            $this->removeTree($root);
        }
    }

    public function testExactReadOnlyReplayIsStableAndDoesNotRewriteChain(): void
    {
        $fixture = $this->productionFixture();
        $root = $this->temporaryRootForBatch4();
        try {
            $this->storeProductionChain($root, $fixture);
            $before = $this->snapshotForBatch4($root);
            $first = $this->reconstruct($root, $fixture);
            $second = $this->reconstruct($root, $fixture);

            self::assertSame($first, $second);
            self::assertSame($first['proof_digest'], $second['proof_digest']);
            self::assertSame($before, $this->snapshotForBatch4($root));
        } finally {
            $this->removeTree($root);
        }
    }

    public function testDocumentationAuthorizesReadOnlyAdversarialAuditNext(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-production-adoption-batch-4-reconstruction.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-production-adoption-batch-4-complete.md',
        );

        foreach ([
            'BATCH_4_READ_ONLY_AGGREGATE_RECONSTRUCTION_COMPLETE',
            'ELIGIBLE_OFFLINE_PRODUCTION_ADOPTION_EVIDENCE',
            'INCOMPLETE',
            'CONFLICTED',
            'REFUSED',
            'validates each artifact before reading the next',
            'persists, repairs, replaces or promotes nothing',
            'The provider binding remains BOUND_INACTIVE.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Production Adoption Batch 5 read-only adversarial readiness audit may next be considered.',
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

    protected function storeProductionChain(string $root, array $fixture): void
    {
        $store = new ProviderBindingSuccessorProductionAdoptionFixtureStore($root);
        $store->putDecision(...$this->productionDecisionArguments($fixture));
        $store->putAuthority(...$this->productionAuthorityArguments($fixture));
        $store->putAdoptionTarget(...$this->adoptionArguments($fixture));
    }

    protected function reconstruct(string $root, array $fixture): array
    {
        return (new ProviderBindingSuccessorProductionAdoptionAggregateReconstructor($root))
            ->reconstruct(
                'binding-reconciliation-root.1',
                $fixture['decisionAuthority'],
                $fixture['target'],
                $fixture['input'],
                $fixture['successor'],
                $fixture['principal'],
                $fixture['binding'],
                $fixture['assurance'],
                $fixture['boundary'],
                new \DateTimeImmutable('2026-08-31T01:00:00+00:00'),
            );
    }

    protected function temporaryRootForBatch4(): string
    {
        $root = sys_get_temp_dir().'/imperium-pba-batch4-'.bin2hex(random_bytes(8));
        mkdir($root, 0770, true);

        return $root;
    }

    protected function snapshotForBatch4(string $root): array
    {
        $snapshot = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $snapshot[substr($file->getPathname(), strlen($root) + 1)] =
                    hash_file('sha256', $file->getPathname());
            }
        }
        ksort($snapshot);

        return $snapshot;
    }
}
