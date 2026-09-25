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
        self::assertStringContainsString('Draft review only', $tester->getDisplay());
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
        self::assertStringContainsString('Draft review only', $tester->getDisplay());
        self::assertStringContainsString('no resource authority or execution authority', $tester->getDisplay());
        self::assertNotNull($this->proposals->latest($interview));
        self::assertSame(1, $this->http->getRequestsCount());
    }

    public function testRevisionCreatesANewVersionAndPreservesHistory(): void
    {
        $interview = $this->authorizedInterview();
        $this->http->setResponseFactory([$this->proposalResponse()]);
        $first = $this->service->generate($interview->getId());

        $this->http->setResponseFactory([$this->proposalResponse('Create a two-page staff report.')]);
        $second = $this->service->revise($interview->getId(), $first->getVersion(), 'Make the report two pages.');

        self::assertSame(2, $second->getVersion());
        self::assertSame('Create a two-page staff report.', $second->getContent()['objective']);
        $history = $this->proposals->history($interview);
        self::assertSame([1, 2], array_map(static fn (Proposal $proposal): int => $proposal->getVersion(), $history));
        self::assertSame('Create a one-page staff report.', $history[0]->getContent()['objective']);
        self::assertSame(2, $this->http->getRequestsCount());
    }

    public function testInvalidRevisionGuidanceIsRejectedBeforeProviderCall(): void
    {
        $interview = $this->authorizedInterview();
        $this->http->setResponseFactory([$this->proposalResponse()]);
        $proposal = $this->service->generate($interview->getId());
        self::assertSame(1, $this->http->getRequestsCount());

        foreach (['', str_repeat('x', 6001)] as $guidance) {
            try {
                $this->service->revise($interview->getId(), $proposal->getVersion(), $guidance);
                self::fail('Invalid revision guidance was accepted.');
            } catch (\DomainException $exception) {
                self::assertStringContainsString('between 1 and 6000 characters', $exception->getMessage());
                self::assertStringNotContainsString('provider charges', $exception->getMessage());
            }
        }

        self::assertSame(1, $this->http->getRequestsCount());
        self::assertCount(1, $this->proposals->history($interview));
    }

    public function testFailedRevisionDoesNotReplaceCurrentProposal(): void
    {
        $interview = $this->authorizedInterview();
        $this->http->setResponseFactory([$this->proposalResponse()]);
        $first = $this->service->generate($interview->getId());

        $this->http->setResponseFactory([$this->jsonResponse('{"objective":"broken"}')]);
        try {
            $this->service->revise($interview->getId(), $first->getVersion(), 'Make it shorter.');
            self::fail('Invalid revision was accepted.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('No revised proposal was saved', $exception->getMessage());
        }

        self::assertSame(1, $this->proposals->latest($interview)?->getVersion());
        self::assertCount(1, $this->proposals->history($interview));
    }

    public function testApprovalPersistsWithoutAnotherProviderCall(): void
    {
        $interview = $this->authorizedInterview();
        $this->http->setResponseFactory([$this->proposalResponse()]);
        $proposal = $this->service->generate($interview->getId());
        self::assertSame(1, $this->http->getRequestsCount());

        $approved = $this->service->approve($interview->getId(), $proposal->getVersion());
        self::assertSame(Proposal::APPROVED, $approved->getStatus());
        self::assertNotNull($approved->getApprovedAt());
        self::assertSame(1, $this->http->getRequestsCount());

        $this->reboot();
        $savedInterview = $this->interviews->get($interview->getId());
        $saved = $this->proposals->latest($savedInterview);
        self::assertSame(Proposal::APPROVED, $saved?->getStatus());
        self::assertNotNull($saved?->getApprovedAt());
        self::assertSame(0, $this->http->getRequestsCount());
    }

    public function testStaleProposalVersionCannotBeApprovedAfterRevision(): void
    {
        $interview = $this->authorizedInterview();
        $this->http->setResponseFactory([$this->proposalResponse()]);
        $first = $this->service->generate($interview->getId());
        $this->http->setResponseFactory([$this->proposalResponse('Create a revised staff report.')]);
        $second = $this->service->revise($interview->getId(), $first->getVersion(), 'Revise the plan.');

        try {
            $this->service->approve($interview->getId(), $first->getVersion());
            self::fail('Stale proposal approval was accepted.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('proposal changed', $exception->getMessage());
        }

        self::assertSame(Proposal::DRAFT, $second->getStatus());
        self::assertNull($this->proposals->latest($interview)?->getApprovedAt());
    }

    public function testCliApprovesSavedProposalWithoutInference(): void
    {
        $interview = $this->authorizedInterview();
        $this->http->setResponseFactory([$this->proposalResponse()]);
        $this->service->generate($interview->getId());

        $this->reboot();
        $tester = new CommandTester(self::getContainer()->get(InterviewCommand::class));
        $tester->setInputs(['1']);
        $tester->execute(['id' => $interview->getId()], ['interactive' => true]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('Proposal v1 approved', $tester->getDisplay());
        self::assertStringContainsString('plan approval only', $tester->getDisplay());
        self::assertSame(Proposal::APPROVED, $this->proposals->latest($this->interviews->get($interview->getId()))?->getStatus());
        self::assertSame(0, $this->http->getRequestsCount());
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

    private function proposalResponse(string $objective = 'Create a one-page staff report.'): MockResponse
    {
        return $this->jsonResponse(json_encode([
            'objective' => $objective,
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
