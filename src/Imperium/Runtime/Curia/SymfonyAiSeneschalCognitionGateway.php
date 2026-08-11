<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

final readonly class SymfonyAiSeneschalCognitionGateway implements SeneschalCognitionGateway
{
    public function __construct(private AgentInterface $agent)
    {
    }

    public function decide(string $request, array $context): array
    {
        $message = new MessageBag(Message::ofUser(implode("\n", [
            'Imperator request: '.$request,
            'Instance: '.($context['instance_id'] ?? 'unknown'),
            'Proceeding: '.($context['proceeding_id'] ?? 'unknown'),
            '',
            'Return one JSON object with exactly these keys:',
            'disposition: ADMITTED_FOR_PLANNING or CLARIFICATION_REQUIRED or REFUSED',
            'decision: a concise executive disposition',
            'question: null or exactly one question',
            'resource_demands: an array of explicitly identified planning resource categories',
            'authorization_required: boolean; true only when a listed resource demand requires Imperator authorization now',
            'Do not claim that any resource, mission, tool, credential, research, or execution has been authorized.',
        ])));
        $result = $this->agent->call($message)->getContent();
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
        if (['authorization_required', 'decision', 'disposition', 'question', 'resource_demands'] !== $keys
            || !in_array($decision['disposition'] ?? null, ['ADMITTED_FOR_PLANNING', 'CLARIFICATION_REQUIRED', 'REFUSED'], true)
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
        foreach ($decision['resource_demands'] as $demand) {
            if (!is_string($demand) || '' === trim($demand)) {
                throw new \RuntimeException('C11_SENESCHAL_CONTRACT_INVALID: resource demands must be non-empty strings.');
            }
        }

        return $decision;
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
