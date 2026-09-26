<?php

namespace App\Tests;

use App\Atheneum\AuthorizationRecords;
use App\Atheneum\ExecutionRecords;
use App\Atheneum\InterviewRecords;
use App\Atheneum\ProposalRecords;
use App\Command\InterviewCommand;
use App\Curia\AuthorizationService;
use App\Curia\InterviewService;
use App\Curia\LocalFileExecutionService;
use App\Entity\Authorization;
use App\Entity\ExecutionAttempt;
use App\Entity\Interview;
use App\Entity\Proposal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;

class ExecutionTest extends KernelTestCase
{
    private AuthorizationRecords $authorizations;
    private AuthorizationService $authorizationService;
    private ExecutionRecords $executions;
    private LocalFileExecutionService $executionService;
    private InterviewService $interviewService;
    private InterviewRecords $interviews;
    private ProposalRecords $proposals;
    private MockHttpClient $http;
    private string $executionDir;
    private string $stagingDir;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connectServices();
        $this->executionDir = self::getContainer()->getParameter('kernel.project_dir').'/public/output';
        $this->stagingDir = self::getContainer()->getParameter('kernel.project_dir').'/var/execution-staging';
        foreach (['imperium-exec.txt', 'imperium-recover.txt', 'imperium-mismatch.txt', 'imperium-existing.txt'] as $name) {
            @unlink($this->executionDir.'/'.$name);
        }

        $em = self::getContainer()->get(EntityManagerInterface::class);
        foreach ($em->getRepository(ExecutionAttempt::class)->findAll() as $attempt) {
            $em->remove($attempt);
        }
        foreach ($em->getRepository(Authorization::class)->findAll() as $authorization) {
            $em->remove($authorization);
        }
        foreach ($em->getRepository(Proposal::class)->findAll() as $proposal) {
            $em->remove($proposal);
        }
        foreach ($em->getRepository(Interview::class)->findAll() as $interview) {
            $em->remove($interview);
        }
        $em->flush();
    }

    protected function tearDown(): void
    {
        foreach (['imperium-exec.txt', 'imperium-recover.txt', 'imperium-mismatch.txt', 'imperium-existing.txt'] as $name) {
            @unlink($this->executionDir.'/'.$name);
        }
        if (is_dir($this->stagingDir)) {
            foreach (glob($this->stagingDir.'/*.tmp') ?: [] as $path) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    public function testExecutionRequiresAuthorizedMatchingScope(): void
    {
        [$interview] = $this->authorizedFixture(effect: 'Open one pull request');

        try {
            $this->executionService->execute($interview->getId(), 'imperium-exec.txt', 'test');
            self::fail('Execution outside authorized effect was accepted.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('no structured executable scope', $exception->getMessage());
        }

        self::assertFileDoesNotExist($this->executionDir.'/imperium-exec.txt');
        self::assertSame(0, $this->http->getRequestsCount());
    }

    public function testStructuredScopeDoesNotDependOnProposalResourceWording(): void
    {
        [$interview, $authorization, $proposal] = $this->authorizedFixture();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->remove($authorization);
        $em->remove($proposal);
        $em->flush();

        $replacement = new Proposal($interview, 2, $interview->getVersion(), [
            'objective' => 'Create one local test file.',
            'deliverable' => 'One local file.',
            'steps' => ['Create the file.'],
            'acceptanceCriteria' => ['The file exists.'],
            'resourceRequirements' => ['Human-readable resource wording can vary.'],
            'limits' => ['Human-readable plan limit context.'],
            'unresolvedAssumptions' => [],
        ]);
        $replacement->approve();
        $this->proposals->save($replacement);
        $replacementAuthorization = $this->authorizationService->request($interview->getId(), 'Create one local test file');
        $replacementAuthorization = $this->authorizationService->decide($interview->getId(), $replacementAuthorization->getId(), true);

        self::assertSame('filesystem.write.public_output', $replacementAuthorization->getExecutionScope()['capability'] ?? null);
        self::assertSame('file.create.public', $replacementAuthorization->getExecutionScope()['effect'] ?? null);
        self::assertSame('public', $replacementAuthorization->getExecutionScope()['visibility'] ?? null);

        $attempt = $this->executionService->execute($interview->getId(), 'imperium-exec.txt', 'test');

        self::assertSame(ExecutionAttempt::SUCCEEDED, $attempt->getStatus());
        self::assertSame('test', file_get_contents($this->executionDir.'/imperium-exec.txt'));
    }

    public function testLegacyAuthorizationWithoutStructuredScopeCannotExecute(): void
    {
        [$interview, $authorization, $proposal] = $this->authorizedFixture();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->remove($authorization);
        $em->flush();

        $legacy = new Authorization($proposal, 1, ['Create one local test file']);
        $legacy->decide(true);
        $this->authorizations->save($legacy);

        try {
            $this->executionService->execute($interview->getId(), 'imperium-exec.txt', 'test');
            self::fail('Legacy free-form authorization executed without structured scope.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('no structured executable scope', $exception->getMessage());
        }

        self::assertFileDoesNotExist($this->executionDir.'/imperium-exec.txt');
    }

    public function testSuccessfulExecutionCreatesOneFileAndPersistsEvidence(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();
        $content = 'Imperium bounded execution test.';

        $attempt = $this->executionService->execute($interview->getId(), 'imperium-exec.txt', $content);

        self::assertSame(ExecutionAttempt::SUCCEEDED, $attempt->getStatus());
        self::assertSame('public/output/imperium-exec.txt', $attempt->getTargetPath());
        self::assertSame(hash('sha256', $content), $attempt->getContentSha256());
        self::assertSame(strlen($content), $attempt->getBytesWritten());
        self::assertFileExists($this->executionDir.'/imperium-exec.txt');
        self::assertSame($content, file_get_contents($this->executionDir.'/imperium-exec.txt'));
        self::assertSame($attempt->getId(), $this->executions->forAuthorization($authorization)?->getId());
        self::assertSame(0, $this->http->getRequestsCount());
    }

    public function testPublicExecutorRefusesNonTextExtensionBeforeAttempt(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();

        try {
            $this->executionService->execute($interview->getId(), 'imperium-exec.php', '<?php echo "no";');
            self::fail('Executable extension was accepted under public/output.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('only .txt files', $exception->getMessage());
        }

        self::assertNull($this->executions->forAuthorization($authorization));
        self::assertFileDoesNotExist($this->executionDir.'/imperium-exec.php');
    }

    public function testPublicExecutorRefusesSymlinkedOutputRootBeforeAttempt(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();

        $backup = $this->executionDir.'.real-'.bin2hex(random_bytes(4));
        $outside = sys_get_temp_dir().'/imperium-output-'.bin2hex(random_bytes(4));
        $hadOriginal = is_dir($this->executionDir) && !is_link($this->executionDir);

        if ($hadOriginal && !rename($this->executionDir, $backup)) {
            self::fail('Could not move the real public/output directory for the symlink regression test.');
        }
        if (!mkdir($outside, 0775, true) && !is_dir($outside)) {
            if ($hadOriginal) {
                rename($backup, $this->executionDir);
            }
            self::fail('Could not create the external directory for the symlink regression test.');
        }
        if (!@symlink($outside, $this->executionDir)) {
            @rmdir($outside);
            if ($hadOriginal) {
                rename($backup, $this->executionDir);
            }
            self::markTestSkipped('Directory symlinks are not available in this test environment.');
        }

        try {
            $this->executionService->execute($interview->getId(), 'imperium-exec.txt', 'must not escape');

            self::fail('A symlinked public/output root was accepted.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('authorized output root', $exception->getMessage());
            self::assertNull($this->executions->forAuthorization($authorization));
            self::assertFileDoesNotExist($outside.'/imperium-exec.txt');
        } finally {
            @unlink($this->executionDir);
            @rmdir($outside);
            if ($hadOriginal) {
                rename($backup, $this->executionDir);
            }
        }
    }

    public function testPublicExecutorRefusesSymlinkedStagingRootBeforeAttempt(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();

        $backup = $this->stagingDir.'.real-'.bin2hex(random_bytes(4));
        $outside = sys_get_temp_dir().'/imperium-staging-'.bin2hex(random_bytes(4));
        $hadOriginal = is_dir($this->stagingDir) && !is_link($this->stagingDir);

        if ($hadOriginal && !rename($this->stagingDir, $backup)) {
            self::fail('Could not move the real execution staging directory for the symlink regression test.');
        }
        if (!mkdir($outside, 0775, true) && !is_dir($outside)) {
            if ($hadOriginal) {
                rename($backup, $this->stagingDir);
            }
            self::fail('Could not create the external staging directory for the symlink regression test.');
        }
        if (!@symlink($outside, $this->stagingDir)) {
            @rmdir($outside);
            if ($hadOriginal) {
                rename($backup, $this->stagingDir);
            }
            self::markTestSkipped('Directory symlinks are not available in this test environment.');
        }

        try {
            $this->executionService->execute($interview->getId(), 'imperium-exec.txt', 'must remain private while staged');

            self::fail('A symlinked execution staging root was accepted.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('staging root', $exception->getMessage());
            self::assertNull($this->executions->forAuthorization($authorization));
            self::assertSame([], glob($outside.'/*') ?: []);
        } finally {
            @unlink($this->stagingDir);
            @rmdir($outside);
            if ($hadOriginal) {
                rename($backup, $this->stagingDir);
            }
        }
    }

    public function testPublicExecutorAcceptsNormalResolvedOutputRoot(): void
    {
        [$interview] = $this->authorizedFixture();

        if (file_exists($this->executionDir) || is_link($this->executionDir)) {
            self::assertFalse(is_link($this->executionDir));
        }

        $attempt = $this->executionService->execute($interview->getId(), 'imperium-exec.txt', 'contained');

        self::assertDirectoryExists($this->executionDir);
        self::assertFalse(is_link($this->executionDir));
        self::assertSame(ExecutionAttempt::SUCCEEDED, $attempt->getStatus());
        self::assertSame('contained', file_get_contents($this->executionDir.'/imperium-exec.txt'));
    }

    public function testFilenameTraversalAndOversizedContentAreRefusedBeforeAttempt(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();

        foreach ([
            ['../escape.txt', 'test'],
            ['subdir/file.txt', 'test'],
            ['imperium-exec.txt', str_repeat('x', 32769)],
        ] as [$filename, $content]) {
            try {
                $this->executionService->execute($interview->getId(), $filename, $content);
                self::fail('Unsafe execution input was accepted.');
            } catch (\DomainException $exception) {
                self::assertNotSame('', $exception->getMessage());
            }
        }

        self::assertNull($this->executions->forAuthorization($authorization));
        self::assertFileDoesNotExist($this->executionDir.'/imperium-exec.txt');
    }

    public function testSuccessfulExecutionLeavesNoStagingArtifact(): void
    {
        [$interview] = $this->authorizedFixture();

        $attempt = $this->executionService->execute($interview->getId(), 'imperium-exec.txt', 'complete before publish');

        self::assertSame(ExecutionAttempt::SUCCEEDED, $attempt->getStatus());
        self::assertFileExists($this->executionDir.'/imperium-exec.txt');
        self::assertFileDoesNotExist($this->stagingDir.'/'.$attempt->getId().'.tmp');
        self::assertFileDoesNotExist($this->executionDir.'/.imperium-'.$attempt->getId().'.publish');
    }

    public function testSucceededAttemptRetriesStagingCleanupWhenMissionReopens(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();
        $attempt = new ExecutionAttempt($authorization, 'public/output/imperium-recover.txt', hash('sha256', 'already completed'));
        $attempt->startEffect();
        $attempt->succeed(strlen('already completed'));
        $this->executions->save($attempt);

        if (!is_dir($this->stagingDir)) {
            mkdir($this->stagingDir, 0775, true);
        }
        $stagingPath = $this->stagingDir.'/'.$attempt->getId().'.tmp';
        file_put_contents($stagingPath, 'already completed');
        self::assertFileExists($stagingPath);

        $reconciled = $this->executionService->reconcile($interview->getId());

        self::assertSame(ExecutionAttempt::SUCCEEDED, $reconciled?->getStatus());
        self::assertFileDoesNotExist($stagingPath);
    }

    public function testMissionWithExecutionEvidenceCannotBeDeleted(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();
        $attempt = $this->executionService->execute($interview->getId(), 'imperium-exec.txt', 'retained evidence');
        self::assertSame(ExecutionAttempt::SUCCEEDED, $attempt->getStatus());

        try {
            $this->interviewService->delete($interview->getId());
            self::fail('Mission with execution evidence was deleted.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('execution evidence', $exception->getMessage());
        }

        self::assertSame($interview->getId(), $this->interviews->get($interview->getId())->getId());
        self::assertSame($attempt->getId(), $this->executions->forAuthorization($authorization)?->getId());
        self::assertFileExists($this->executionDir.'/imperium-exec.txt');
    }

    public function testExistingTargetFailsWithoutOverwriteAndConsumesAttempt(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();
        if (!is_dir($this->executionDir)) {
            mkdir($this->executionDir, 0775, true);
        }
        file_put_contents($this->executionDir.'/imperium-existing.txt', 'original');

        $attempt = $this->executionService->execute($interview->getId(), 'imperium-existing.txt', 'replacement');

        self::assertSame(ExecutionAttempt::FAILED, $attempt->getStatus());
        self::assertSame('target_exists_or_unavailable', $attempt->getFailureCode());
        self::assertSame('original', file_get_contents($this->executionDir.'/imperium-existing.txt'));
        self::assertSame($attempt->getId(), $this->executions->forAuthorization($authorization)?->getId());

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('already has an execution attempt');
        $this->executionService->execute($interview->getId(), 'imperium-exec.txt', 'second try');
    }

    public function testPreparedAttemptReopensWhenExecutionDirectoriesDoNotYetExist(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();
        $attempt = new ExecutionAttempt($authorization, 'public/output/imperium-recover.txt', hash('sha256', 'not written yet'));
        $this->executions->save($attempt);

        $outputBackup = $this->executionDir.'.prepared-backup-'.bin2hex(random_bytes(4));
        $stagingBackup = $this->stagingDir.'.prepared-backup-'.bin2hex(random_bytes(4));
        $hadOutput = is_dir($this->executionDir) && !is_link($this->executionDir);
        $hadStaging = is_dir($this->stagingDir) && !is_link($this->stagingDir);

        if ($hadOutput && !rename($this->executionDir, $outputBackup)) {
            self::fail('Could not move public/output for the prepared recovery regression test.');
        }
        if ($hadStaging && !rename($this->stagingDir, $stagingBackup)) {
            if ($hadOutput) {
                rename($outputBackup, $this->executionDir);
            }
            self::fail('Could not move execution staging for the prepared recovery regression test.');
        }

        try {
            self::assertDirectoryDoesNotExist($this->executionDir);
            self::assertDirectoryDoesNotExist($this->stagingDir);

            $reconciled = $this->executionService->reconcile($interview->getId());

            self::assertSame($attempt->getId(), $reconciled?->getId());
            self::assertSame(ExecutionAttempt::PREPARED, $reconciled?->getStatus());
            self::assertNull($reconciled?->getFailureCode());
        } finally {
            if ($hadStaging) {
                rename($stagingBackup, $this->stagingDir);
            }
            if ($hadOutput) {
                rename($outputBackup, $this->executionDir);
            }
        }
    }

    public function testPreparedAttemptNeverClaimsSuccessFromMatchingPreexistingFile(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();
        $content = 'recovered result';
        $attempt = new ExecutionAttempt($authorization, 'public/output/imperium-recover.txt', hash('sha256', $content));
        $this->executions->save($attempt);

        if (!is_dir($this->executionDir)) {
            mkdir($this->executionDir, 0775, true);
        }
        file_put_contents($this->executionDir.'/imperium-recover.txt', $content);

        $reconciled = $this->executionService->reconcile($interview->getId());

        self::assertSame(ExecutionAttempt::FAILED, $reconciled?->getStatus());
        self::assertSame('prepared_target_exists_without_start_evidence', $reconciled?->getFailureCode());
        self::assertSame($content, file_get_contents($this->executionDir.'/imperium-recover.txt'));
    }

    public function testStartedAttemptCanReconcileMatchingFileWithoutRepeatingEffect(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();
        $content = 'recovered started result';
        $attempt = new ExecutionAttempt($authorization, 'public/output/imperium-recover.txt', hash('sha256', $content));
        $attempt->startEffect();
        $this->executions->save($attempt);

        if (!is_dir($this->executionDir)) {
            mkdir($this->executionDir, 0775, true);
        }
        if (!is_dir($this->stagingDir)) {
            mkdir($this->stagingDir, 0775, true);
        }
        $stagingPath = $this->stagingDir.'/'.$attempt->getId().'.tmp';
        file_put_contents($stagingPath, $content);
        link($stagingPath, $this->executionDir.'/imperium-recover.txt');

        $reconciled = $this->executionService->reconcile($interview->getId());

        self::assertSame(ExecutionAttempt::SUCCEEDED, $reconciled?->getStatus());
        self::assertSame(strlen($content), $reconciled?->getBytesWritten());
        self::assertSame($content, file_get_contents($this->executionDir.'/imperium-recover.txt'));
    }

    public function testStartedAttemptCannotClaimAnotherPublishWithSameContent(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();
        $content = 'same bytes, different inode';
        $attempt = new ExecutionAttempt($authorization, 'public/output/imperium-recover.txt', hash('sha256', $content));
        $attempt->startEffect();
        $this->executions->save($attempt);

        if (!is_dir($this->executionDir)) {
            mkdir($this->executionDir, 0775, true);
        }
        if (!is_dir($this->stagingDir)) {
            mkdir($this->stagingDir, 0775, true);
        }
        file_put_contents($this->stagingDir.'/'.$attempt->getId().'.tmp', $content);
        file_put_contents($this->executionDir.'/imperium-recover.txt', $content);

        $reconciled = $this->executionService->reconcile($interview->getId());

        self::assertSame(ExecutionAttempt::FAILED, $reconciled?->getStatus());
        self::assertSame('recovery_ownership_mismatch', $reconciled?->getFailureCode());
    }

    public function testStartedAttemptWithMismatchedFileFailsClosed(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();
        $attempt = new ExecutionAttempt($authorization, 'public/output/imperium-mismatch.txt', hash('sha256', 'expected'));
        $attempt->startEffect();
        $this->executions->save($attempt);

        if (!is_dir($this->executionDir)) {
            mkdir($this->executionDir, 0775, true);
        }
        if (!is_dir($this->stagingDir)) {
            mkdir($this->stagingDir, 0775, true);
        }
        $stagingPath = $this->stagingDir.'/'.$attempt->getId().'.tmp';
        file_put_contents($stagingPath, 'different');
        link($stagingPath, $this->executionDir.'/imperium-mismatch.txt');

        $reconciled = $this->executionService->reconcile($interview->getId());

        self::assertSame(ExecutionAttempt::FAILED, $reconciled?->getStatus());
        self::assertSame('recovery_hash_mismatch', $reconciled?->getFailureCode());
        self::assertFileDoesNotExist($this->executionDir.'/imperium-mismatch.txt');
        self::assertFileDoesNotExist($stagingPath);
    }

    public function testStartedAttemptRejectsSymlinkTargetDuringRecovery(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();
        $content = 'recovery symlink must not count';
        $attempt = new ExecutionAttempt($authorization, 'public/output/imperium-symlink.txt', hash('sha256', $content));
        $attempt->startEffect();
        $this->executions->save($attempt);

        if (!is_dir($this->executionDir)) {
            mkdir($this->executionDir, 0775, true);
        }
        if (!is_dir($this->stagingDir)) {
            mkdir($this->stagingDir, 0775, true);
        }

        $stagingPath = $this->stagingDir.'/'.$attempt->getId().'.tmp';
        file_put_contents($stagingPath, $content);
        $targetPath = $this->executionDir.'/imperium-symlink.txt';
        if (!@symlink($stagingPath, $targetPath)) {
            @unlink($stagingPath);
            self::markTestSkipped('File symlinks are not available in this test environment.');
        }

        try {
            $reconciled = $this->executionService->reconcile($interview->getId());

            self::assertSame(ExecutionAttempt::FAILED, $reconciled?->getStatus());
            self::assertSame('recovery_target_symlink', $reconciled?->getFailureCode());
            self::assertTrue(is_link($targetPath));
        } finally {
            @unlink($targetPath);
            @unlink($stagingPath);
        }
    }

    public function testCliExecutesAuthorizedLocalFileAndShowsEvidence(): void
    {
        [$interview] = $this->authorizedFixture();
        $tester = new CommandTester(self::getContainer()->get(InterviewCommand::class));
        $tester->setInputs(['1', 'imperium-exec.txt', 'hello imperium']);
        $tester->execute(['id' => $interview->getId()], ['interactive' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Execution attempt — succeeded', $tester->getDisplay());
        self::assertStringContainsString('public/output/imperium-exec.txt', $tester->getDisplay());
        self::assertStringContainsString('Authorized local-file effect completed', $tester->getDisplay());
        self::assertSame('hello imperium', file_get_contents($this->executionDir.'/imperium-exec.txt'));
        self::assertSame(0, $this->http->getRequestsCount());
    }

    public function testCliBackCreatesNoExecutionAttempt(): void
    {
        [$interview, $authorization] = $this->authorizedFixture();
        $tester = new CommandTester(self::getContainer()->get(InterviewCommand::class));
        $tester->execute(['id' => $interview->getId()], ['interactive' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Create authorized local file', $tester->getDisplay());
        self::assertStringContainsString('No execution attempt was created', $tester->getDisplay());
        self::assertNull($this->executions->forAuthorization($authorization));
        self::assertSame(0, $this->http->getRequestsCount());
    }

    /** @return array{Interview, Authorization, Proposal} */
    private function authorizedFixture(string $effect = 'Create one local test file'): array
    {
        $interview = $this->interviews->create();
        $interview->submit('Create a local test file.');
        $interview->beginAttempt();
        $interview->receive('Create one local test file.\n\n'.Interview::PERMISSION_QUESTION, true, 'local file test');
        $this->interviews->save($interview);
        $interview->decideDraftPermission(true);
        $this->interviews->save($interview);

        $proposal = new Proposal($interview, 1, $interview->getVersion(), [
            'objective' => 'Create one local test file.',
            'deliverable' => 'One local file.',
            'steps' => ['Create the file.', 'Verify its contents.'],
            'acceptanceCriteria' => ['The file exists with the expected content.'],
            'resourceRequirements' => ['Local filesystem write access.'],
            'limits' => ['One new local file only.', 'No overwrite.'],
            'unresolvedAssumptions' => [],
        ]);
        $proposal->approve();
        $this->proposals->save($proposal);

        $authorization = $this->authorizationService->request($interview->getId(), $effect);
        $authorization = $this->authorizationService->decide($interview->getId(), $authorization->getId(), true);

        return [$interview, $authorization, $proposal];
    }

    private function connectServices(): void
    {
        $this->authorizations = self::getContainer()->get(AuthorizationRecords::class);
        $this->authorizationService = self::getContainer()->get(AuthorizationService::class);
        $this->executions = self::getContainer()->get(ExecutionRecords::class);
        $this->executionService = self::getContainer()->get(LocalFileExecutionService::class);
        $this->interviewService = self::getContainer()->get(InterviewService::class);
        $this->interviews = self::getContainer()->get(InterviewRecords::class);
        $this->proposals = self::getContainer()->get(ProposalRecords::class);
        $this->http = self::getContainer()->get('seneschal.test_client');
    }
}
