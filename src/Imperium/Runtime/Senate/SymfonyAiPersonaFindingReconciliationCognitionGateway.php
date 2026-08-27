<?php declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Imperium\Runtime\Clavium\GovernanceCognitionInvoker;

final readonly class SymfonyAiPersonaFindingReconciliationCognitionGateway implements PersonaFindingReconciliationCognitionGateway
{
    public function __construct(private GovernanceCognitionInvoker $cognition) {}

    public function reconcile(array $authority, array $findings): array
    {
        $prompt = implode("\n", [
            'Exact bounded Persona reconciliation authority: '.json_encode($authority, JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
            'Four admitted sealed jurisdictional findings: '.json_encode($findings, JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
            'Reconcile agreement and disagreement without changing a finding, voting, counting, averaging, scoring, aggregating, suppressing dissent, recommending a disposition, or issuing a disposition.',
            'Preserve any mandatory Security blocking condition explicitly and unchanged.',
            'Return only JSON with exactly: finding_references, agreements, disagreements, attribution_treatment, severity_treatment, limitations, uncertainties, rationale. The first seven are arrays of non-empty strings; rationale is one non-empty string.',
            'Copy all four supplied available_finding_references exactly and in order into finding_references.',
        ]);
        $content = $this->cognition->invoke('senate-persona-confirmation', 'reconciliation', (string) ($authority['reconciliation_authority_id'] ?? ''), 'senate.lord-speaker', 'reconcile-persona-findings', [$authority, $findings], $prompt);
        if (!is_string($content) || '' === trim($content)) throw new \RuntimeException('S900_PERSONA_RECONCILIATION_COGNITION_INVALID');
        $content = trim($content);
        if (str_starts_with($content, '```')) $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        try { $result = json_decode(trim($content), true, 16, JSON_THROW_ON_ERROR); } catch (\JsonException $exception) { throw new \RuntimeException('S900_PERSONA_RECONCILIATION_COGNITION_INVALID', 0, $exception); }
        if (!is_array($result) || array_is_list($result)) throw new \RuntimeException('S900_PERSONA_RECONCILIATION_COGNITION_INVALID');
        $keys = array_keys($result); sort($keys, SORT_STRING);
        if (['agreements', 'attribution_treatment', 'disagreements', 'finding_references', 'limitations', 'rationale', 'severity_treatment', 'uncertainties'] !== $keys || !is_string($result['rationale']) || '' === trim($result['rationale'])) throw new \RuntimeException('S900_PERSONA_RECONCILIATION_COGNITION_INVALID');
        foreach (['finding_references', 'agreements', 'disagreements', 'attribution_treatment', 'severity_treatment', 'limitations', 'uncertainties'] as $field) {
            if (!is_array($result[$field]) || !array_is_list($result[$field])) throw new \RuntimeException('S900_PERSONA_RECONCILIATION_COGNITION_INVALID');
            foreach ($result[$field] as $value) if (!is_string($value) || '' === trim($value)) throw new \RuntimeException('S900_PERSONA_RECONCILIATION_COGNITION_INVALID');
        }
        return $result;
    }
}
