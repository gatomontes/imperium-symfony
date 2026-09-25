<?php

namespace App\Tests;

use App\Atheneum\InterviewRecords;
use App\Atheneum\ProposalRecords;
use App\Command\InterviewCommand;
use App\Curia\ProposalService;
use App\Entity\Interview;
use App\Entity\Proposal;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ProposalTest extends KernelTestCase
{
    private InterviewRecords $interviews;
    private ProposalRecords $proposals;
    private ProposalService $service;
    private MockHttpClient $http;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connectServices();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        foreach ($em->getRepository(Proposal::class)->findAll() as $proposal) {
            $em->remove($proposal);
        }
        foreach ($em->getRepository(Interview::class)->findAll() as $interview) {
            $em->remove($interview);
        }
        $em->flush();
    }

    public function testGenerationRequiresRecordedDraftPermission(): void
    {
        $interview = $this->interviews->create();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Drafting permission is required');
        try {
            $this->service->generate($interview->getId());
        } finally {
            self::assertSame(0, $this->http->getRequestsCount());
            self::assertNull($this->proposals->latest($interview));
        }
    }

    public function testAuthorizedProposalIsGeneratedAndPersisted(): void
    {
        $interview = $this->authorizedInterview();
        $this->http->setResponseFactory([$this->proposalResponse()]);

        $proposal = $this->service->generate($interview->getId());

        self::assertSame(1, $proposal->getVersion());
        self::assertSame($interview->getVersion(), $proposal->getSourceInterviewVersion());
        self::assertSame('Create a one-page staff report.', $proposal->getContent()['objective']);
        self::assertSame(1, $this->http->getRequestsCount());

        $this->reboot();
        $savedInterview = $this->interviews->get($interview->getId());
        $saved = $this->proposals->latest($savedInterview);
        self::assertNotNull($saved);
        self::assertSame($proposal->getId(), $saved->getId());
        self::assertSame('draft', $saved->getStatus());
    }

    public function testReopeningSavedProposalDoesNotCallProviderAgain(): void
    {
        $interview = $this->authorizedInterview();
        $this->http->setResponseFactory([$this->proposalResponse()]);
        $this->service->generate($interview->getId());
        self::assertSame(1, $this->http->getRequestsCount());

        $this->reboot();
        $tester = new CommandTester(self::getContainer()->get(InterviewCommand::class));
        $tester->execute(['id' => $interview->getId()], ['interactive' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Proposal v1', $tester->getDisplay());
        self::assertStringContainsString('Create a one-page staff report.', $tester->getDisplay());
        self::assertStringContainsString('Review only', $tester->getDisplay());
        self::assertSame(0, $this->http->getRequestsCount());
    }

    public function testInvalidProposalIsNotSavedAndRetryIsExplicit(): void
    {
        $interview = $this->authorizedInterview();
        $this->http->setResponseFactory([
            $this->jsonResponse(json_encode([
                'objective' => 'Report',
                'deliverable' => 'One page',
                'steps' => [],
                'acceptanceCriteria' => ['One page'],
                'resourceRequirements' => [],
                'limits' => [],
                'unresolvedAssumptions' => [],
            ], JSON_THROW_ON_ERROR)),
        ]);

        try {
            $this->service->generate($interview->getId());
            self::fail('Invalid proposal was accepted.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('No proposal was saved', $exception->getMessage());
            self::assertStringContainsString('retry explicitly', $exception->getMessage());
        }
        self::assertNull($this->proposals->latest($interview));
        self::assertSame(1, $this->http->getRequestsCount());
    }

    public function testCliGeneratesAndDisplaysProposalWithoutGrantingFurtherAuthority(): void
    {
        $interview = $this->authorizedInterview();
        $this->http->setResponseFactory([$this->proposalResponse()]);
        $tester = new CommandTester(self::getContainer()->get(InterviewCommand::class));
        $tester->setInputs(['1']);
        $tester->execute(['id' => $interview->getId()], ['interactive' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Waiting for Seneschal to draft...', $tester->getDisplay());
        self::assertStringContainsString('Proposal v1', $tester->getDisplay());
        self::assertStringContainsString('not proposal approval, resource authority, or execution authority', $tester->getDisplay());
        self::assertNotNull($this->proposals->latest($interview));
        self::assertSame(1, $this->http->getRequestsCount());
    }

    private function authorizedInterview(): Interview
    {
        $interview = $this->interviews->create();
        $interview->submit('Create a one-page staff report for Friday.');
        $interview->beginAttempt();
        $interview->receive('A one-page staff report for Friday.\n\n'.Interview::PERMISSION_QUESTION, true, 'staff report');
        $this->interviews->save($interview);
        $interview->decideDraftPermission(true);
        $this->interviews->save($interview);

        return $interview;
    }

    private function proposalResponse(): MockResponse
    {
        return $this->jsonResponse(json_encode([
            'objective' => 'Create a one-page staff report.',
            'deliverable' => 'A one-page report for staff.',
            'steps' => ['Draft the report.', 'Check it against the acceptance criteria.'],
            'acceptanceCriteria' => ['The report is one page.', 'It is suitable for staff.'],
            'resourceRequirements' => ['The interview record.'],
            'limits' => ['No external publication.'],
            'unresolvedAssumptions' => ['Final source material is still required.'],
        ], JSON_THROW_ON_ERROR));
    }

    private function jsonResponse(string $content): MockResponse
    {
        return new MockResponse(json_encode([
            'id' => 'chatcmpl_proposal',
            'object' => 'chat.completion',
            'choices' => [[
                'index' => 0,
                'finish_reason' => 'stop',
                'message' => ['role' => 'assistant', 'content' => $content],
            ]],
        ], JSON_THROW_ON_ERROR), ['response_headers' => ['content-type: application/json']]);
    }

    private function connectServices(): void
    {
        $this->interviews = self::getContainer()->get(InterviewRecords::class);
        $this->proposals = self::getContainer()->get(ProposalRecords::class);
        $this->service = self::getContainer()->get(ProposalService::class);
        $this->http = self::getContainer()->get('seneschal.test_client');
    }

    private function reboot(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();
        $this->connectServices();
    }
}
