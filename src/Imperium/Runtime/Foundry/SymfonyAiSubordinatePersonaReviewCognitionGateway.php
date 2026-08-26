<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Imperium\Runtime\Clavium\GovernanceCognitionInvoker;


final readonly class SymfonyAiSubordinatePersonaReviewCognitionGateway
    implements SubordinatePersonaReviewCognitionGateway
{
    public function __construct(private GovernanceCognitionInvoker $invoker) {}

    public function review(
        array $candidate,
        array $specification,
        array $case,
    ): array {
        $prompt = implode("\n", [
            "Exact assembled Persona candidate: " .
            json_encode(
                $candidate,
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ),
            "Exact current Persona specification: " .
            json_encode(
                $specification,
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ),
            "Exact construction case: " .
            json_encode($case, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            "Perform Foundry completeness and boundary review only. Confirm the candidate satisfies the specification, preserves Hagiography versus Studium authorship boundaries, carries complete lineage, and contains no unresolved question that prevents adversarial examination.",
            "Do not approve or admit the Persona, create or approve a Profile, spawn, bind, or execute.",
            "Return only one JSON object with exactly: disposition, findings, unresolved_blockers, adversarial_review_brief.",
            "disposition must be READY_FOR_ADVERSARIAL_REVIEW or REVISION_REQUIRED. findings and unresolved_blockers must be arrays of explicit non-empty strings. adversarial_review_brief must be a non-empty string. READY_FOR_ADVERSARIAL_REVIEW requires unresolved_blockers to be empty.",
        ]);
        $content = $this->invoker->invoke('foundry', 'persona-review', (string) ($candidate['candidate_id'] ?? ''), 'foundry.artificer', 'review-persona', [$candidate, $specification, $case], $prompt);
        if (!is_string($content) || "" === trim($content)) {
            throw new \RuntimeException("F139_PERSONA_REVIEW_COGNITION_EMPTY");
        }
        $content = trim($content);
        if (str_starts_with($content, "```")) {
            $content =
                preg_replace('/^```(?:json)?\s*|\s*```$/i', "", $content) ??
                $content;
        }
        try {
            $decision = json_decode(
                trim($content),
                true,
                32,
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $exception) {
            throw new \RuntimeException(
                "F140_PERSONA_REVIEW_COGNITION_INVALID",
                0,
                $exception,
            );
        }
        return is_array($decision)
            ? $decision
            : throw new \RuntimeException(
                "F140_PERSONA_REVIEW_COGNITION_INVALID",
            );
    }
}
