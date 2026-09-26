<?php

namespace App\Tests;

use App\Atheneum\AuthorizationRecords;
use App\Atheneum\InterviewRecords;
use App\Atheneum\ProposalRecords;
use App\Command\InterviewCommand;
use App\Curia\AuthorizationService;
use App\Entity\Authorization;
use App\Entity\Interview;
use App\Entity\Proposal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;

class AuthorizationTest extends KernelTestCase
{
    private AuthorizationRecords $authorizations;
    private AuthorizationService $service;
    private InterviewRecords $interviews;
    private ProposalRecords $proposals;
    private MockHttpClient $http;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connectServices();
        $em = self::getContainer()->get(EntityManagerInterface::class);
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

    public function testAuthorizationRequiresApprovedProposal(): void
    {
        [$interview, $proposal] = $this->proposalFixture(approved: false);

        try {
            $this->service->request($interview->getId(), '');
            self::fail('Authorization was requested from an unapproved proposal.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('Proposal approval is required', $exception->getMessage());
        }

        self::assertNull($this->authorizations->forProposal($proposal));
        self::assertSame(0, $this->http->getRequestsCount());
    }

    public function testAuthorizationRequestSnapshotsApprovedScopeAndSurvivesRestart(): void
    {
        [$interview, $proposal] = $this->proposalFixture();

        $authorization = $this->service->request(
            $interview->getId(),
            "Create one local test file;open one pull request",
        );

        self::assertSame(Authorization::PENDING, $authorization->getStatus());
        self::assertSame(['The interview record.', 'Local filesystem write access.'], $authorization->getResources());
        self::assertSame(['Create one local test file', 'open one pull request'], $authorization->getEffects());
        self::assertSame(['No external publication.', 'No deletion.'], $authorization->getLimits());
        self::assertSame([
            'capability' => 'filesystem.write.public_output',
            'effect' => 'file.create.public',
            'root' => 'public/output',
            'visibility' => 'public',
            'allowedExtensions' => ['txt'],
            'maxFiles' => 1,
            'overwrite' => false,
            'maxBytes' => 32768,
        ], $authorization->getExecutionScope());
        self::assertNull($authorization->getDecidedAt());
        self::assertSame(0, $this->http->getRequestsCount());

        $this->reboot();
        $savedProposal = $this->proposals->latest($this->interviews->get($interview->getId()));
        self::assertNotNull($savedProposal);
        $saved = $this->authorizations->forProposal($savedProposal);
        self::assertNotNull($saved);
        self::assertSame($authorization->getId(), $saved->getId());
        self::assertSame(['Create one local test file', 'open one pull request'], $saved->getEffects());
        self::assertSame('filesystem.write.public_output', $saved->getExecutionScope()['capability'] ?? null);
    }

    public function testAuthorizationDecisionIsPersistedAndCannotBeChanged(): void
    {
        [$interview, $proposal] = $this->proposalFixture();
        $authorization = $this->service->request($interview->getId(), 'Create one local file.');

        $decided = $this->service->decide($interview->getId(), $authorization->getId(), true);
        self::assertSame(Authorization::AUTHORIZED, $decided->getStatus());
        self::assertNotNull($decided->getDecidedAt());

        try {
            $this->service->decide($interview->getId(), $authorization->getId(), false);
            self::fail('A decided authorization was changed.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('already been decided', $exception->getMessage());
        }

        $this->reboot();
        $savedProposal = $this->proposals->latest($this->interviews->get($interview->getId()));
        self::assertSame(Authorization::AUTHORIZED, $this->authorizations->forProposal($savedProposal)?->getStatus());
    }

    public function testRefusalIsPersistedSeparatelyFromProposalApproval(): void
    {
        [$interview, $proposal] = $this->proposalFixture();
        $authorization = $this->service->request($interview->getId(), 'Publish externally.');

        $decided = $this->service->decide($interview->getId(), $authorization->getId(), false);

        self::assertSame(Authorization::REFUSED, $decided->getStatus());
        self::assertSame(Proposal::APPROVED, $proposal->getStatus());
        self::assertNotNull($proposal->getApprovedAt());
        self::assertSame(0, $this->http->getRequestsCount());
    }

    public function testInvalidEffectScopeDoesNotCreateAuthorization(): void
    {
        [$interview, $proposal] = $this->proposalFixture();

        try {
            $this->service->request($interview->getId(), str_repeat('x', 6001));
            self::fail('Oversized effect scope was accepted.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('6000 characters or fewer', $exception->getMessage());
        }

        self::assertNull($this->authorizations->forProposal($proposal));
        self::assertSame(0, $this->http->getRequestsCount());
    }

    public function testReopeningApprovedProposalDoesNotCreateAuthorization(): void
    {
        [$interview, $proposal] = $this->proposalFixture();

        $tester = new CommandTester(self::getContainer()->get(InterviewCommand::class));
        $tester->execute(['id' => $interview->getId()], ['interactive' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Prepare authorization request', $tester->getDisplay());
        self::assertStringContainsString('No resource/effect authorization request', $tester->getDisplay());
        self::assertNull($this->authorizations->forProposal($proposal));
        self::assertSame(0, $this->http->getRequestsCount());
    }

    public function testCliPreparesAndAuthorizesScopeWithoutExecutingOrCallingProvider(): void
    {
        [$interview, $proposal] = $this->proposalFixture();

        $tester = new CommandTester(self::getContainer()->get(InterviewCommand::class));
        $tester->setInputs([
            '1',
            'Create one local test file; open one pull request',
            '1',
        ]);
        $tester->execute(['id' => $interview->getId()], ['interactive' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Executable scope', $tester->getDisplay());
        self::assertStringContainsString('filesystem.write.public_output', $tester->getDisplay());
        self::assertStringContainsString('file.create.public', $tester->getDisplay());
        self::assertStringContainsString('Proposal resource context', $tester->getDisplay());
        self::assertStringContainsString('Local filesystem write access.', $tester->getDisplay());
        self::assertStringContainsString('Create one local test file', $tester->getDisplay());
        self::assertStringContainsString('Requested scope authorized', $tester->getDisplay());
        self::assertStringContainsString('No execution occurred', $tester->getDisplay());
        self::assertSame(Authorization::AUTHORIZED, $this->authorizations->forProposal($proposal)?->getStatus());
        self::assertSame(0, $this->http->getRequestsCount());
    }

    public function testReopeningDecidedAuthorizationRemainsReadOnlyAndBackDoesNotExecute(): void
    {
        [$interview, $proposal] = $this->proposalFixture();
        $authorization = $this->service->request($interview->getId(), 'Create one local test file.');
        $this->service->decide($interview->getId(), $authorization->getId(), true);

        $this->reboot();
        $tester = new CommandTester(self::getContainer()->get(InterviewCommand::class));
        $tester->execute(['id' => $interview->getId()], ['interactive' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Authorization — authorized', $tester->getDisplay());
        self::assertStringContainsString('Create authorized local file', $tester->getDisplay());
        self::assertStringContainsString('No execution attempt was created', $tester->getDisplay());
        self::assertStringNotContainsString('Authorization decision', $tester->getDisplay());
        self::assertSame(0, $this->http->getRequestsCount());
    }

    /** @return array{Interview, Proposal} */
    private function proposalFixture(bool $approved = true): array
    {
        $interview = $this->interviews->create();
        $interview->submit('Create a one-page staff report for Friday.');
        $interview->beginAttempt();
        $interview->receive('A one-page staff report for Friday.\n\n'.Interview::PERMISSION_QUESTION, true, 'staff report');
        $this->interviews->save($interview);
        $interview->decideDraftPermission(true);
        $this->interviews->save($interview);

        $proposal = new Proposal($interview, 1, $interview->getVersion(), [
            'objective' => 'Create a one-page staff report.',
            'deliverable' => 'A one-page Markdown report.',
            'steps' => ['Draft the report.', 'Review it against the criteria.'],
            'acceptanceCriteria' => ['The report is one page.'],
            'resourceRequirements' => ['The interview record.', 'Local filesystem write access.'],
            'limits' => ['No external publication.', 'No deletion.'],
            'unresolvedAssumptions' => [],
        ]);
        if ($approved) {
            $proposal->approve();
        }
        $this->proposals->save($proposal);

        return [$interview, $proposal];
    }

    private function connectServices(): void
    {
        $this->authorizations = self::getContainer()->get(AuthorizationRecords::class);
        $this->service = self::getContainer()->get(AuthorizationService::class);
        $this->interviews = self::getContainer()->get(InterviewRecords::class);
        $this->proposals = self::getContainer()->get(ProposalRecords::class);
        $this->http = self::getContainer()->get('seneschal.test_client');
    }

    private function reboot(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();
        $this->connectServices();
    }
}
