<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Senate\ModelBoundProfileExaminationTestimonyOpeningService;
use App\Imperium\Runtime\Senate\ProfileSenateAuthorityTransition;
use PHPUnit\Framework\TestCase;

final class ProfileSenateAuthorityTransitionTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-profile-senate-transition-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testSealsAndValidatesOneExactModelBoundProfileSenateAuthority(): void
    {
        $id = 'model-bound-profile-examination-testimony-opening-'.str_repeat('a', 20);
        $record = $this->record($id);
        $sealed = ProfileSenateAuthorityTransition::seal(
            $id,
            $record,
            ModelBoundProfileExaminationTestimonyOpeningService::class,
        );
        $transaction = $sealed['transactional_consumption'];

        self::assertSame('imperium.runtime-transactional-authority-consumption/v1', $transaction['schema']);
        self::assertSame([$record['testimony_opening_authority']['id']], array_column($transaction['authority_set'], 'authority_id'));
        self::assertSame('profile-senate-authority:'.hash('sha256', $record['testimony_opening_authority']['id']), $transaction['lock_plan'][0]['scope']);
        self::assertSame(ModelBoundProfileExaminationTestimonyOpeningService::class, $transaction['consumer']['competent_service']);
        self::assertSame('COMPLETE', $transaction['recovery']['checkpoint']);
        self::assertFalse($transaction['recovery']['external_effect']['started']);
        self::assertTrue(ProfileSenateAuthorityTransition::isExactOrHistorical($sealed));

        $sealed['transactional_consumption']['consumer']['competent_service'] = self::class;
        self::assertFalse(ProfileSenateAuthorityTransition::isExactOrHistorical($sealed));
        self::assertTrue(ProfileSenateAuthorityTransition::isExactOrHistorical($record));
    }

    public function testFaultAfterImmutableCommitRecoversTheExactOpening(): void
    {
        $directory = $this->directory();
        $id = 'model-bound-profile-examination-testimony-opening-'.str_repeat('a', 20);
        $record = $this->record($id);

        try {
            ProfileSenateAuthorityTransition::run($directory, $record['testimony_opening_authority']['id'], function () use ($directory, $id, $record): void {
                ProfileSenateAuthorityTransition::put($directory, $id, $record, ModelBoundProfileExaminationTestimonyOpeningService::class, 'WRITE_FAILED', 'CONFLICT');
                throw new \RuntimeException('TEST_FAULT_AFTER_RESULT_COMMITTED');
            });
            self::fail('The selected fault was not injected.');
        } catch (\RuntimeException $exception) {
            self::assertSame('TEST_FAULT_AFTER_RESULT_COMMITTED', $exception->getMessage());
        }

        $recovered = ProfileSenateAuthorityTransition::run(
            $directory,
            $record['testimony_opening_authority']['id'],
            fn (): array => ProfileSenateAuthorityTransition::put($directory, $id, $record, ModelBoundProfileExaminationTestimonyOpeningService::class, 'WRITE_FAILED', 'CONFLICT'),
        );

        self::assertSame($id, $recovered['opening_id']);
        self::assertSame('COMPLETE', $recovered['transactional_consumption']['recovery']['checkpoint']);
        self::assertCount(1, glob($directory.'/*.json') ?: []);
    }

    public function testExactHistoricalOpeningReplaysWithoutRewrite(): void
    {
        $directory = $this->directory();
        self::assertTrue(mkdir($directory, 0770, true));
        $id = 'model-bound-profile-examination-testimony-opening-'.str_repeat('a', 20);
        $record = $this->record($id);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        self::assertNotFalse(file_put_contents(
            $directory.'/'.$id.'.json',
            json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n",
            LOCK_EX,
        ));

        $replayed = ProfileSenateAuthorityTransition::run(
            $directory,
            $record['testimony_opening_authority']['id'],
            fn (): array => ProfileSenateAuthorityTransition::put($directory, $id, $record, ModelBoundProfileExaminationTestimonyOpeningService::class, 'WRITE_FAILED', 'CONFLICT'),
        );

        self::assertSame($record, $replayed);
        self::assertArrayNotHasKey('transactional_consumption', $replayed);
    }

    public function testTwoProcessesConvergeBeforeCompetingOpeningsCanCommit(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open is required for process-level contention proof.');
        }
        $authorityId = 'model-bound-profile-testimony-opening-authority-'.str_repeat('e', 20);
        $gate = $this->root.'/go';
        $worker = dirname(__DIR__, 2).'/fixtures/profile-senate-authority-contender.php';
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = $pipes = [];
        foreach (['a', 'f'] as $index => $contender) {
            $processes[$index] = proc_open([PHP_BINARY, $worker, $this->root, $authorityId, $gate, $contender], $descriptors, $pipes[$index]);
            self::assertIsResource($processes[$index]);
        }
        self::assertTrue(is_dir($this->root) || mkdir($this->root, 0770, true));
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
        self::assertCount(1, glob($this->directory().'/*.json') ?: []);
    }

    private function directory(): string
    {
        return $this->root.'/var/imperium/offices/senate/model-bound-profile-examination-testimony-openings';
    }

    private function record(string $id): array
    {
        return [
            'schema' => 'imperium.senate-model-bound-profile-examination-testimony-opening/v1',
            'opening_id' => $id,
            'instance_id' => 'imperium-test',
            'case_id' => 'profile-examination-case-'.str_repeat('1', 20),
            'case_digest' => str_repeat('2', 64),
            'source_panel_readiness' => ['id' => 'model-bound-profile-examination-panel-readiness-'.str_repeat('b', 20), 'digest' => str_repeat('c', 64)],
            'testimony_opening_authority' => ['id' => 'model-bound-profile-testimony-opening-authority-'.str_repeat('e', 20), 'consumed' => true, 'continuing_authority' => false],
            'lord_speaker' => ['seat' => 'senate.lord-speaker', 'binding_id' => 'senate-lord-speaker-binding-'.str_repeat('d', 20)],
            'subject_profile' => ['profile_id' => 'model-bound-profile'],
            'evidence_chain' => ['model_binding' => str_repeat('3', 64)],
            'question_authorities' => [],
            'opened_at' => '2026-08-28T12:30:00+00:00',
            'status' => 'PROFILE_EXAMINATION_TESTIMONY_OPENED_PENDING_SENATOR_QUESTIONING',
            'sealed' => true,
        ];
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
