<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Imperium\Runtime\Clavium\GovernanceCognitionInvoker;

final readonly class SymfonyAiSeneschalCognitionGateway implements SeneschalCognitionGateway
{
    public function __construct(private GovernanceCognitionInvoker $invoker)
    {
    }

    public function decide(string $authorityId, string $request, array $context): array
    {
        throw new \RuntimeException('CMF112_NEW_REQUEST_USES_CITADEL_INTAKE');
    }

    public function advance(string $authorityId, array $proceeding, array $priorTurns, string $imperatorResponse, array $context): array
    {
        throw new \RuntimeException('CMF113_NEW_PLANNING_REQUIRES_CITADEL_AUTHORITY');
    }

    private function invoke(string $result, array $allowedDispositions): array
    {
        if (!is_string($result) || '' === trim($result)) {
            throw new \RuntimeException('C10_SENESCHAL_EMPTY: cognition returned no disposition.');
        }

        try {
            $decision = json_decode($this->extractJson($result), true, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('C11_SENESCHAL_CONTRACT_INVALID: disposition is not valid JSON.', 0, $exception);
        }
        if (!is_array($decision)) {
            throw new \RuntimeException('C11_SENESCHAL_CONTRACT_INVALID: disposition must be an object.');
        }
        $keys = array_keys($decision);
        sort($keys, SORT_STRING);
        if (['authorization_required', 'decision', 'disposition', 'mission_plan', 'question', 'resource_demands'] !== $keys
            || !in_array($decision['disposition'] ?? null, $allowedDispositions, true)
            || !is_string($decision['decision'] ?? null)
            || '' === trim($decision['decision'])
            || !(null === ($decision['question'] ?? null) || is_string($decision['question']))
            || !is_array($decision['resource_demands'] ?? null)
            || !is_bool($decision['authorization_required'] ?? null)
        ) {
            throw new \RuntimeException('C11_SENESCHAL_CONTRACT_INVALID: disposition violates its return contract.');
        }
        if ('CLARIFICATION_REQUIRED' === $decision['disposition'] && (null === $decision['question'] || '' === trim($decision['question']))) {
            throw new \RuntimeException('C11_SENESCHAL_CONTRACT_INVALID: clarification requires exactly one question.');
        }
        if ('CLARIFICATION_REQUIRED' !== $decision['disposition'] && null !== $decision['question']) {
            throw new \RuntimeException('C11_SENESCHAL_CONTRACT_INVALID: only clarification may include a question.');
        }
        if ('AUTHORIZATION_REQUIRED' === $decision['disposition'] && !$decision['authorization_required']) {
            throw new \RuntimeException('C11_SENESCHAL_CONTRACT_INVALID: authorization disposition must declare the requirement.');
        }
        if ('MISSION_PLAN_DRAFTED' === $decision['disposition']) {
            $this->validateMissionPlan($decision['mission_plan'] ?? null);
        } elseif (null !== ($decision['mission_plan'] ?? null)) {
            throw new \RuntimeException('C11_SENESCHAL_CONTRACT_INVALID: only a drafted-plan disposition may include a Mission Plan.');
        }
        foreach ($decision['resource_demands'] as $demand) {
            if (!is_string($demand) || '' === trim($demand)) {
                throw new \RuntimeException('C11_SENESCHAL_CONTRACT_INVALID: resource demands must be non-empty strings.');
            }
        }

        return $decision;
    }

    private function validateMissionPlan(mixed $plan): void
    {
        if (!is_array($plan)) {
            throw new \RuntimeException('C12_MISSION_PLAN_INVALID: structured Mission Plan is required.');
        }
        $keys = array_keys($plan);
        sort($keys, SORT_STRING);
        $expected = ['capability_requirements', 'constraints', 'data_requirements', 'deliverables', 'objective', 'office_participation', 'required_inputs', 'scope', 'stop_conditions', 'tool_requirements'];
        if ($expected !== $keys || !is_string($plan['objective'] ?? null) || '' === trim($plan['objective'])) {
            throw new \RuntimeException('C12_MISSION_PLAN_INVALID: Mission Plan fields are incomplete.');
        }
        foreach ($expected as $field) {
            if ('objective' === $field) {
                continue;
            }
            if (!is_array($plan[$field])) {
                throw new \RuntimeException('C12_MISSION_PLAN_INVALID: '.$field.' must be an array.');
            }
            foreach ($plan[$field] as $item) {
                if (!is_string($item) || '' === trim($item)) {
                    throw new \RuntimeException('C12_MISSION_PLAN_INVALID: '.$field.' entries must be explicit strings.');
                }
            }
        }
    }

    private function extractJson(string $content): string
    {
        $content = trim($content);
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content) ?? $content;
        }

        return trim($content);
    }
}
