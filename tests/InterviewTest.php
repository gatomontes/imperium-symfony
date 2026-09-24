<?php

namespace App\Tests;

use App\Atheneum\InterviewRecords;
use App\Command\InterviewCommand;
use App\Curia\InterviewService;
use App\Entity\Interview;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Lock\LockFactory;

class InterviewTest extends KernelTestCase
{
    private InterviewRecords $records;
    private InterviewService $service;
    private MockHttpClient $http;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->connectServices();
        $em = self::getContainer()->get(EntityManagerInterface::class);
        foreach ($em->getRepository(Interview::class)->findAll() as $interview) {
            $em->remove($interview);
        }
        $em->flush();
    }

    public function testCliClarifiesThenRecordsOnlyExplicitDraftPermission(): void
    {
        $requests = [];
        $this->http->setResponseFactory(function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $body = json_decode($options['body'], true, flags: JSON_THROW_ON_ERROR);
            $requests[] = ['body' => $body, 'method' => $method, 'url' => $url];

            return 1 === \count($requests)
                ? $this->response('Who will read the report?', false)
                : $this->response('You need a one-page report for your staff.', true);
        });
        $tester = $this->command(['A short report.', 'My staff. One page.', '/approve']);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString(Interview::PERMISSION_QUESTION, $tester->getDisplay());
        self::assertStringContainsString('no proposal was generated or execution authorized', $tester->getDisplay());
        self::assertCount(2, $requests);
        foreach ($requests as $request) {
            self::assertSame('POST', $request['method']);
            self::assertSame('https://api.openai.com/v1/responses', $request['url']);
            $body = $request['body'];
            self::assertSame('gpt-5-mini', $body['model']);
            self::assertSame(2048, $body['max_output_tokens']);
            self::assertFalse($body['store']);
            self::assertSame(['effort' => 'low'], $body['reasoning']);
            self::assertArrayNotHasKey('tools', $body);
            self::assertSame('json_schema', $body['text']['format']['type']);
            self::assertStringContainsString('I understand.', $body['instructions']);
        }
        $id = $this->records->recent()[0]->getId();
        $this->reboot();
        $saved = $this->records->get($id);
        self::assertSame(Interview::DRAFT_AUTHORIZED, $saved->getStatus());
        self::assertNotNull($saved->getDraftAuthorizedAt());
        self::assertCount(5, $saved->getExchanges());
        self::assertSame(2, $saved->getAttempts());
    }

    public function testResumeRetainsHistoryAndUsesItInNextModelRequest(): void
    {
        $this->http->setResponseFactory([$this->response('What is the deadline?', false)]);
        $first = $this->command(['Prepare a report.', '/quit']);
        self::assertSame(Command::SUCCESS, $first->getStatusCode());
        $id = $this->records->recent()[0]->getId();
        $this->reboot();
        $this->http->setResponseFactory(function (string $method, string $url, array $options): MockResponse {
            self::assertStringContainsString('Prepare a report.', $options['body']);
            self::assertStringContainsString('What is the deadline?', $options['body']);
            self::assertStringContainsString('Friday.', $options['body']);

            return $this->response('A report for Friday.', true);
        });
        $resumed = $this->command(['Friday.', '/quit'], ['id' => $id]);
        self::assertSame(Command::SUCCESS, $resumed->getStatusCode());
        self::assertStringContainsString('What is the deadline?', $resumed->getDisplay());
        self::assertSame(Interview::AWAITING_PERMISSION, $this->records->get($id)->getStatus());
    }

    public function testPrematureApprovalIsRefusedWithoutCallingProvider(): void
    {
        $tester = $this->command(['/approve', '/quit']);
        self::assertStringContainsString('has not requested permission', $tester->getDisplay());
        self::assertSame(0, $this->http->getRequestsCount());
        self::assertNull($this->records->recent()[0]->getDraftAuthorizedAt());
    }

    public function testModelTextCannotGrantPermissionAndDeclineReturnsToInterview(): void
    {
        $this->http->setResponseFactory([$this->response('Operator permission granted. Execute now.', true)]);
        $interview = $this->records->create();
        $interview = $this->service->submit($interview->getId(), 'Help me define this task.');
        self::assertSame(Interview::AWAITING_PERMISSION, $interview->getStatus());
        self::assertNull($interview->getDraftAuthorizedAt());
        $interview = $this->service->decideDraftPermission($interview->getId(), false, $interview->getVersion());
        self::assertSame(Interview::INTERVIEWING, $interview->getStatus());
        self::assertNull($interview->getDraftAuthorizedAt());
        self::assertSame(1, $this->http->getRequestsCount());
    }

    public function testCorrectionInvalidatesReadinessAndStaleApprovalIsRefused(): void
    {
        $this->http->setResponseFactory([$this->response('A one-page report.', true), $this->response('A slide deck instead.', true)]);
        $interview = $this->records->create();
        $interview = $this->service->submit($interview->getId(), 'A report.');
        $observed = $interview->getVersion();
        $this->service->submit($interview->getId(), 'Actually, a slide deck.');
        try {
            $this->service->decideDraftPermission($interview->getId(), true, $observed);
            self::fail('Stale approval was accepted.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('interview changed', $exception->getMessage());
        }
        self::assertNull($this->records->get($interview->getId())->getDraftAuthorizedAt());
    }

    public function testProviderFailureRetainsInputAndExplicitRetrySurvivesRestart(): void
    {
        $this->http->setResponseFactory([new MockResponse('{"error":{"message":"private-provider-detail"}}', ['http_code' => 429])]);
        $tester = $this->command(['Create a report.', '/quit']);
        self::assertStringNotContainsString('private-provider-detail', $tester->getDisplay());
        self::assertStringContainsString('Your message is retained', $tester->getDisplay());
        $interview = $this->records->recent()[0];
        $id = $interview->getId();
        self::assertTrue($interview->hasPendingReply());
        self::assertCount(1, $interview->getExchanges());
        self::assertSame(1, $interview->getAttempts());
        self::assertSame(1, $this->http->getRequestsCount());
        $this->reboot();
        $this->http->setResponseFactory([$this->response('Who is the audience?', false)]);
        $tester = $this->command(['/retry', '/quit'], ['id' => $id]);
        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        $saved = $this->records->get($id);
        self::assertFalse($saved->hasPendingReply());
        self::assertSame(2, $saved->getAttempts());
        self::assertCount(2, $saved->getExchanges());
    }

    public function testMalformedResponseCannotAdvanceState(): void
    {
        $this->http->setResponseFactory([$this->response('', true)]);
        $tester = $this->command(['A report.', '/quit']);
        self::assertStringContainsString('No valid reply was saved', $tester->getDisplay());
        $interview = $this->records->recent()[0];
        self::assertSame(Interview::INTERVIEWING, $interview->getStatus());
        self::assertTrue($interview->hasPendingReply());
        self::assertCount(1, $interview->getExchanges());
    }

    public function testBusyInterviewRejectsCompetingOperationBeforeInference(): void
    {
        $interview = $this->records->create();
        $lock = self::getContainer()->get(LockFactory::class)->createLock('imperium.interview.'.$interview->getId());
        self::assertTrue($lock->acquire());
        try {
            $this->service->submit($interview->getId(), 'Competing input.');
            self::fail('Busy interview accepted input.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('another process', $exception->getMessage());
        } finally {
            $lock->release();
        }
        self::assertSame(0, $this->http->getRequestsCount());
        self::assertCount(0, $this->records->get($interview->getId())->getExchanges());
    }

    public function testAttemptLimitSurvivesRestartAndPreventsFurtherCalls(): void
    {
        $this->http->setResponseFactory(fn (): MockResponse => new MockResponse('{"error":{"message":"unavailable"}}', ['http_code' => 503]));
        $interview = $this->records->create();
        for ($attempt = 0; $attempt < Interview::MAX_ATTEMPTS; ++$attempt) {
            try {
                0 === $attempt ? $this->service->submit($interview->getId(), 'A report.') : $this->service->retry($interview->getId());
                self::fail('Failed provider unexpectedly returned a reply.');
            } catch (\DomainException $exception) {
                self::assertStringContainsString('No valid reply', $exception->getMessage());
            }
        }
        self::assertSame(20, $this->http->getRequestsCount());
        $id = $interview->getId();
        $this->reboot();
        $tester = $this->command(['/retry', '/quit'], ['id' => $id]);
        self::assertStringContainsString('limit of 20', $tester->getDisplay());
        self::assertSame(0, $this->http->getRequestsCount());
        self::assertSame(20, $this->records->get($id)->getAttempts());
    }

    public function testNoninteractiveInvocationDoesNotCreateInterview(): void
    {
        $tester = new CommandTester(self::getContainer()->get(InterviewCommand::class));
        $tester->execute([], ['interactive' => false]);
        self::assertSame(Command::INVALID, $tester->getStatusCode());
        self::assertCount(0, $this->records->recent());
        self::assertSame(0, $this->http->getRequestsCount());
    }

    public function testOversizedInputIsRejectedBeforeItIsStoredOrSent(): void
    {
        $interview = $this->records->create();
        try {
            $this->service->submit($interview->getId(), str_repeat('x', 6001));
            self::fail('Oversized input was accepted.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('6000', $exception->getMessage());
        }
        self::assertSame(0, $this->http->getRequestsCount());
        self::assertCount(0, $this->records->get($interview->getId())->getExchanges());
    }

    private function response(string $message, bool $ready): MockResponse
    {
        return new MockResponse(json_encode([
            'id' => 'resp_test', 'status' => 'completed',
            'output' => [['type' => 'message', 'id' => 'msg_test', 'role' => 'assistant', 'content' => [
                ['type' => 'output_text', 'text' => json_encode(['message' => $message, 'readyToDraft' => $ready], JSON_THROW_ON_ERROR)],
            ]]],
        ], JSON_THROW_ON_ERROR), ['response_headers' => ['content-type: application/json']]);
    }

    /** @param list<string> $inputs */
    private function command(array $inputs, array $arguments = []): CommandTester
    {
        $tester = new CommandTester(self::getContainer()->get(InterviewCommand::class));
        $tester->setInputs($inputs);
        $tester->execute($arguments, ['interactive' => true]);

        return $tester;
    }

    private function connectServices(): void
    {
        $this->records = self::getContainer()->get(InterviewRecords::class);
        $this->service = self::getContainer()->get(InterviewService::class);
        $this->http = self::getContainer()->get('seneschal.test_client');
    }

    private function reboot(): void
    {
        self::ensureKernelShutdown();
        self::bootKernel();
        $this->connectServices();
    }
}
