<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Imperium\Runtime\Clavium\GovernanceCognitionInvoker;

final readonly class SymfonyAiSenatorFindingCognitionGateway
    implements SenatorFindingCognitionGateway
{
    public function __construct(private GovernanceCognitionInvoker $cognition) {}

    public function find(
        string $jurisdiction,
        array $assignment,
        array $evidence,
    ): array {
        if (!in_array($jurisdiction, ['practice','governance','consistency','security'], true)) throw new \RuntimeException('S175_SENATOR_FINDING_COGNITION_INVALID');
        $authorityId=(string)($assignment['authority_id']??'');
        $prompt = implode("\n", [
            "Exact attributable finding assignment: " . $this->encode($assignment),
            "Exact jurisdiction-competent evidence: " . $this->encode($evidence),
            "Interpret only this evidence. Do not vote, aggregate scores, suppress disagreement, issue the Senate disposition, or create admission authority.",
            "Return only JSON with exactly: disposition, evidence_references, rationale, severity, limitations, mandatory_failure.",
            "disposition must be PASS, CONCERN, FAIL, or UNRESOLVED. severity must be NONE, LOW, MEDIUM, HIGH, or CRITICAL.",
            "Only Security may set mandatory_failure true; if true, disposition must be FAIL and severity CRITICAL.",
        ]);
        $content=$this->cognition->invoke('senate-persona-confirmation','finding-'.$jurisdiction,$authorityId,'senate.committee.'.$jurisdiction,'issue-persona-finding',[$assignment,$evidence],$prompt);
        if (!is_string($content) || "" === trim($content)) {
            throw new \RuntimeException("S175_SENATOR_FINDING_COGNITION_INVALID");
        }
        $content = trim($content);
        if (str_starts_with($content, chr(96) . chr(96) . chr(96))) {
            $content = preg_replace('/^\x60\x60\x60(?:json)?\s*|\s*\x60\x60\x60$/i', "", $content) ?? $content;
        }
        try {
            $finding = json_decode(trim($content), true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException("S175_SENATOR_FINDING_COGNITION_INVALID", 0, $exception);
        }
        return is_array($finding)
            ? $finding
            : throw new \RuntimeException("S175_SENATOR_FINDING_COGNITION_INVALID");
    }

    private function encode(array $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
