<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Senate\SymfonyAiProfileExaminationTestimonyCognitionGateway;
use PHPUnit\Framework\TestCase;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Result\TextResult;

final class SymfonyAiProfileExaminationTestimonyCognitionGatewayTest extends TestCase
{
    public function testAcceptsExactWitnessContract(): void
    {
        $answer = ['answer' => 'I remain examination-only.', 'uncertainties' => [], 'refusals' => ['I refuse execution.'], 'evidence_claims' => ['The Manifestation has no execution authority.']];
        self::assertSame($answer, $this->gateway(json_encode($answer, JSON_THROW_ON_ERROR))->answer([], []));
    }

    public function testNormalizesMeaningEquivalentScalarListFields(): void
    {
        $answer = $this->gateway('{"answer":"  Bounded answer.  ","uncertainties":"  None beyond supplied evidence.  ","refusals":[],"evidence_claims":"  Exact lineage supplied.  "}')->answer([], []);
        self::assertSame('Bounded answer.', $answer['answer']);
        self::assertSame(['None beyond supplied evidence.'], $answer['uncertainties']);
        self::assertSame(['Exact lineage supplied.'], $answer['evidence_claims']);
    }

    public function testRefusesAdditionalFieldsWithNonSensitiveDiagnostic(): void
    {
        $this->expectExceptionMessage('S229_PROFILE_EXAMINATION_TESTIMONY_COGNITION_INVALID: FIELDS_INVALID');
        $this->gateway('{"answer":"Bounded.","uncertainties":[],"refusals":[],"evidence_claims":[],"finding":"PASS"}')->answer([], []);
    }

    public function testRefusesNestedEvidenceClaimsWithNonSensitiveDiagnostic(): void
    {
        $this->expectExceptionMessage('S229_PROFILE_EXAMINATION_TESTIMONY_COGNITION_INVALID: EVIDENCE_CLAIMS_ITEM_INVALID');
        $this->gateway('{"answer":"Bounded.","uncertainties":[],"refusals":[],"evidence_claims":[{"claim":"unsafe"}]}')->answer([], []);
    }

    public function testRefusesInvalidJsonWithNonSensitiveDiagnostic(): void
    {
        $this->expectExceptionMessage('S229_PROFILE_EXAMINATION_TESTIMONY_COGNITION_INVALID: JSON_INVALID');
        $this->gateway('Answer: bounded.')->answer([], []);
    }

    private function gateway(string $response): SymfonyAiProfileExaminationTestimonyCognitionGateway
    {
        $agent = $this->createStub(AgentInterface::class);
        $agent->method('call')->willReturn(new TextResult($response));
        return new SymfonyAiProfileExaminationTestimonyCognitionGateway($agent);
    }
}
