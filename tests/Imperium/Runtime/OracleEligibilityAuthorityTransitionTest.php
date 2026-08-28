<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Oracle\ModelEligibilityFindingService;
use App\Imperium\Runtime\Oracle\OracleEligibilityAuthorityTransition;
use App\Imperium\Runtime\Oracle\OracleEligibilityTransitionFaultInjector;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class OracleEligibilityAuthorityTransitionTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-oracle-eligibility-transition-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testSealsExactSeparateConsumptionWithoutChangingNativeFindingOrPhase(): void
    {
        [$case, $authority, $bindingId] = $this->fixtures($this->root);
        $finding = $this->issue($this->root, $case, $authority, $bindingId);
        $phase = $this->only($this->root.'/var/imperium/offices/oracle/model-eligibility-phases');
        $transaction = $this->only($this->root.'/var/imperium/offices/oracle/model-eligibility-authority-transitions');

        self::assertArrayNotHasKey('transactional_consumption', $finding);
        self::assertSame($finding['issued_at'], $phase['closed_at']);
        self::assertSame($finding['finding_id'], $transaction['finding']['id']);
        self::assertSame('COMPLETE', $transaction['checkpoint']);
        self::assertSame('oracle-eligibility-case:'.hash('sha256', $case['case_id']), $transaction['transactional_consumption']['lock_plan'][0]['scope']);
        self::assertSame($finding['issued_at'], $transaction['transactional_consumption']['consumption_result']['authority_consumptions'][0]['consumed_at']);
        self::assertFalse($transaction['transactional_consumption']['recovery']['external_effect']['started']);

        $atomic = new AtomicTransition($this->root);
        $transition = new OracleEligibilityAuthorityTransition($this->root, $atomic, new ImmutableRecordStore($this->root, $atomic));
        self::assertTrue($transition->isExact($transaction, $case, $authority, $finding));
        $transaction['transactional_consumption']['consumer']['competent_service'] = self::class;
        self::assertFalse($transition->isExact($transaction, $case, $authority, $finding));
    }

    public function testEveryCommittedCheckpointRecoversForwardUsingOriginalIssuedAt(): void
    {
        foreach (['FINDING_COMMITTED', 'PHASE_RECONCILED', 'TRANSACTION_COMMITTED'] as $checkpoint) {
            $root = $this->root.'/'.$checkpoint;
            [$case, $authority, $bindingId] = $this->fixtures($root);
            $fault = new class($checkpoint) implements OracleEligibilityTransitionFaultInjector {
                private bool $fired = false;

                public function __construct(private readonly string $selected)
                {
                }

                public function at(string $checkpoint): void
                {
                    if (!$this->fired && $checkpoint === $this->selected) {
                        $this->fired = true;
                        throw new \RuntimeException('TEST_FAULT_'.$checkpoint);
                    }
                }
            };
            try {
                $this->issue($root, $case, $authority, $bindingId, $fault);
                self::fail('The selected fault was not injected.');
            } catch (\RuntimeException $exception) {
                self::assertSame('TEST_FAULT_'.$checkpoint, $exception->getMessage());
            }

            $recovered = (new ModelEligibilityFindingService($root))->issue(
                $case['case_id'],
                $authority['authority_id'],
                $bindingId,
                'INELIGIBLE',
                ['fit' => ['disposition' => 'FAILED', 'rationale' => 'A competing retry must not replace the committed winner.']],
                ['source-a'],
                ['claim-a'],
                ['COMPETING_RETRY'],
                new \DateTimeImmutable('2026-08-28T20:00:00+00:00'),
            );
            $phase = $this->only($root.'/var/imperium/offices/oracle/model-eligibility-phases');
            $transaction = $this->only($root.'/var/imperium/offices/oracle/model-eligibility-authority-transitions');

            self::assertSame('ELIGIBLE', $recovered['disposition'], $checkpoint);
            self::assertSame('2026-08-28T18:00:00+00:00', $phase['closed_at'], $checkpoint);
            self::assertSame('2026-08-28T18:00:00+00:00', $transaction['issued_at'], $checkpoint);
            self::assertCount(1, glob($root.'/var/imperium/offices/oracle/model-eligibility-findings/*.json') ?: []);
            self::assertCount(1, glob($root.'/var/imperium/offices/oracle/model-eligibility-phases/*.json') ?: []);
            self::assertCount(1, glob($root.'/var/imperium/offices/oracle/model-eligibility-authority-transitions/*.json') ?: []);
        }
    }

    public function testTwoProcessesConvergeOnOneFindingPhaseAndConsumption(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open is required for process-level contention proof.');
        }
        [$case, $authority, $bindingId] = $this->fixtures($this->root);
        $gate = $this->root.'/go';
        $worker = dirname(__DIR__, 2).'/fixtures/oracle-eligibility-authority-contender.php';
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = $pipes = [];
        foreach (['ELIGIBLE', 'INELIGIBLE'] as $index => $disposition) {
            $processes[$index] = proc_open([
                PHP_BINARY,
                $worker,
                $this->root,
                $case['case_id'],
                $authority['authority_id'],
                $bindingId,
                $gate,
                $disposition,
            ], $descriptors, $pipes[$index]);
            self::assertIsResource($processes[$index]);
        }
        self::assertTrue(touch($gate));
        $results = [];
        for ($index = 0; $index < 2; ++$index) {
            $results[] = stream_get_contents($pipes[$index][1]);
            $errors = stream_get_contents($pipes[$index][2]);
            fclose($pipes[$index][1]);
            fclose($pipes[$index][2]);
            self::assertSame(0, proc_close($processes[$index]));
            self::assertSame('', $errors);
        }

        self::assertSame($results[0], $results[1]);
        self::assertCount(1, glob($this->root.'/var/imperium/offices/oracle/model-eligibility-findings/*.json') ?: []);
        self::assertCount(1, glob($this->root.'/var/imperium/offices/oracle/model-eligibility-phases/*.json') ?: []);
        self::assertCount(1, glob($this->root.'/var/imperium/offices/oracle/model-eligibility-authority-transitions/*.json') ?: []);
    }

    public function testTwoDistinctFinalAuthoritiesSerializeOnePhaseUsingLatestNativeIssuedAt(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open is required for process-level contention proof.');
        }
        [$case, $authority, $bindingId] = $this->fixtures($this->root);
        $second = $authority;
        $second['authority_id'] = 'oracle-model-eligibility-authority-'.str_repeat('f', 20);
        $second['model_ref'] = 'provider/second-model@test';
        unset($case['record_digest']);
        $case['eligibility_authorities'][$second['model_ref']] = $second;
        $case = $this->record($case);
        $this->write($this->root.'/var/imperium/offices/oracle/model-evaluation-cases/'.$case['case_id'].'.json', $case);

        $gate = $this->root.'/go-distinct';
        $worker = dirname(__DIR__, 2).'/fixtures/oracle-eligibility-authority-contender.php';
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = $pipes = [];
        foreach ([[$authority['authority_id'], 'ELIGIBLE'], [$second['authority_id'], 'INELIGIBLE']] as $index => [$authorityId, $disposition]) {
            $processes[$index] = proc_open([
                PHP_BINARY,
                $worker,
                $this->root,
                $case['case_id'],
                $authorityId,
                $bindingId,
                $gate,
                $disposition,
            ], $descriptors, $pipes[$index]);
            self::assertIsResource($processes[$index]);
        }
        self::assertTrue(touch($gate));
        for ($index = 0; $index < 2; ++$index) {
            stream_get_contents($pipes[$index][1]);
            $errors = stream_get_contents($pipes[$index][2]);
            fclose($pipes[$index][1]);
            fclose($pipes[$index][2]);
            self::assertSame(0, proc_close($processes[$index]));
            self::assertSame('', $errors);
        }

        $phase = $this->only($this->root.'/var/imperium/offices/oracle/model-eligibility-phases');
        self::assertSame('2026-08-28T19:00:00+00:00', $phase['closed_at']);
        self::assertSame(['provider/model@test'], $phase['eligible_models']);
        self::assertCount(2, glob($this->root.'/var/imperium/offices/oracle/model-eligibility-findings/*.json') ?: []);
        self::assertCount(2, glob($this->root.'/var/imperium/offices/oracle/model-eligibility-authority-transitions/*.json') ?: []);
    }

    private function issue(
        string $root,
        array $case,
        array $authority,
        string $bindingId,
        ?OracleEligibilityTransitionFaultInjector $fault = null,
    ): array {
        return (new ModelEligibilityFindingService($root, $fault))->issue(
            $case['case_id'],
            $authority['authority_id'],
            $bindingId,
            'ELIGIBLE',
            ['fit' => ['disposition' => 'SATISFIED', 'rationale' => 'The exact model satisfies the frozen criterion.']],
            ['source-a'],
            ['claim-a'],
            [],
            new \DateTimeImmutable('2026-08-28T18:00:00+00:00'),
        );
    }

    private function fixtures(string $root): array
    {
        $caseId = 'oracle-model-evaluation-case-'.str_repeat('a', 20);
        $bindingId = 'oracle-augur-binding-'.str_repeat('b', 20);
        $authority = [
            'authority_id' => 'oracle-model-eligibility-authority-'.str_repeat('c', 20),
            'model_ref' => 'provider/model@test',
            'eligibility_finding_authority' => true,
            'authority_single_use' => true,
            'criteria_digest' => str_repeat('d', 64),
            'catalogue_snapshot_digest' => str_repeat('e', 64),
            'source_ids' => ['source-a'],
            'claim_ids' => ['claim-a'],
        ];
        $case = $this->record([
            'schema' => 'imperium.oracle-model-evaluation-case/v1',
            'case_id' => $caseId,
            'instance_id' => 'imperium-test',
            'actor' => ['binding_id' => $bindingId],
            'criteria' => ['evaluation_rubric' => ['fit']],
            'eligibility_authorities' => ['provider/model@test' => $authority],
            'candidate_universe_frozen' => true,
            'status' => 'ORACLE_MODEL_EVALUATION_CASE_OPENED_PENDING_AUGUR_ELIGIBILITY_FINDINGS',
        ]);
        $occupancy = $this->record([
            'schema' => 'imperium.oracle-augur-occupancy/v1',
            'binding_id' => $bindingId,
            'instance_id' => 'imperium-test',
            'manifestation_id' => 'manifestation-augur',
            'occupancy_generation' => 1,
            'status' => 'ORACLE_AUGUR_BOUND_ACTIVE_NO_MODEL_SELECTION_AUTHORITY',
        ]);
        $this->write($root.'/var/imperium/offices/oracle/model-evaluation-cases/'.$caseId.'.json', $case);
        $this->write($root.'/var/imperium/offices/oracle/occupancy/'.$bindingId.'.json', $occupancy);

        return [$case, $authority, $bindingId];
    }

    private function record(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function write(string $path, array $record): void
    {
        self::assertTrue(is_dir(dirname($path)) || mkdir(dirname($path), 0770, true));
        self::assertNotFalse(file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX));
    }

    private function only(string $directory): array
    {
        $paths = glob($directory.'/*.json') ?: [];
        self::assertCount(1, $paths);

        return json_decode((string) file_get_contents($paths[0]), true, 512, JSON_THROW_ON_ERROR);
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
