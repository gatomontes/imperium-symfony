<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Imperium\Runtime\Clavium\GovernanceCognitionInvoker;


final readonly class SymfonyAiAdversarialPersonaReviewCognitionGateway
    implements AdversarialPersonaReviewCognitionGateway
{
    public function __construct(private GovernanceCognitionInvoker $invoker) {}

    public function review(
        array $candidate,
        array $specification,
        array $case,
        array $acceptance,
    ): array {
        $prompt = implode("\n", [
            "Exact accepted review assignment: " .
            json_encode(
                $acceptance,
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ),
            "Exact assembled Persona candidate: " .
            json_encode(
                $candidate,
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ),
            "Exact current versioned specification: " .
            json_encode(
                $specification,
                JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ),
            "Exact construction case: " .
            json_encode($case, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            "Pressure-test compliance, internal contradiction, unsupported claims, boundary violations, missing evidence duties, and failure behavior. Preserve the clarification and full supersession lineage. Do not invent defects merely to appear adversarial.",
            "Return only one JSON object with exactly: disposition, findings, required_corrections, rationale. disposition must be PASSED or RETURN_TO_FOUNDRY. PASSED requires required_corrections to be empty. RETURN_TO_FOUNDRY requires at least one explicit correction.",
        ]);
        $content = $this->invoker->invoke('foundry', 'adversarial-persona-review', (string) ($acceptance['acceptance_id'] ?? ''), 'foundry.reviewer.adversarial', 'adversarial-review-persona', [$candidate, $specification, $case, $acceptance], $prompt);
        if (!is_string($content) || "" === trim($content)) {
            throw new \RuntimeException(
                "F168_ADVERSARIAL_REVIEW_COGNITION_EMPTY",
            );
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
                "F169_ADVERSARIAL_REVIEW_COGNITION_INVALID",
                0,
                $exception,
            );
        }
        return is_array($decision)
            ? $decision
            : throw new \RuntimeException(
                "F169_ADVERSARIAL_REVIEW_COGNITION_INVALID",
            );
    }
}
