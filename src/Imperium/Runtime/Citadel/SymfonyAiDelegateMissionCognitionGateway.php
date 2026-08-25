<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

final readonly class SymfonyAiDelegateMissionCognitionGateway implements DelegateMissionCognitionGateway
{
    public function __construct(
        private DelegateProviderInvoker $provider,
    ) {
    }

    public function invoke(array $claim, array $activation, array $commission): array
    {
        $runtime = $activation['model']['runtime_binding'] ?? [];
        if (!is_array($runtime) || ['provider', 'platform_service', 'runtime_model'] !== array_keys($runtime)
            || 'deepseek' !== $runtime['provider']
            || 'ai.platform.generic.deepseek' !== $runtime['platform_service']
            || !is_string($runtime['runtime_model']) || '' === trim($runtime['runtime_model'])) {
            throw new \RuntimeException('CT310_DELEGATE_RUNTIME_PLATFORM_UNSUPPORTED');
        }
        $text = trim($this->provider->invoke(
            $claim,
            $runtime['runtime_model'],
            new MessageBag(Message::ofUser($this->prompt($commission))),
            $activation['model']['configuration'] ?? [],
        ));
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;
        }
        try {
            $payload = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('CT311_DELEGATE_PROVIDER_RESULT_NOT_JSON', 0, $exception);
        }
        if (!is_array($payload)) {
            throw new \RuntimeException('CT311_DELEGATE_PROVIDER_RESULT_NOT_JSON');
        }
        return $payload;
    }

    private function prompt(array $commission): string
    {
        $contract = $commission['commission_contract'] ?? [];
        return "Execute exactly one bounded, internal-reasoning-only Delegate mission cognition turn.\n"
            ."Do not use tools, cross a perimeter, take external action, amend the mission, or continue beyond this turn.\n"
            .'Objective: '.($contract['objective'] ?? '')."\n"
            .'Scope: '.json_encode($contract['scope'] ?? [], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n"
            .'Deliverables: '.json_encode($contract['deliverables'] ?? [], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n"
            .'Required inputs: '.json_encode($contract['required_inputs'] ?? [], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n"
            .'Expected outcomes: '.json_encode($contract['expected_outcomes'] ?? [], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n"
            .'Stop conditions: '.json_encode($contract['stop_conditions'] ?? [], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n"
            .'Return only JSON with keys in this exact order: disposition, output, evidence_references, uncertainties, stop_condition_triggered, stop_rationale. '
            .'disposition must be COMPLETED, STOPPED, or FAILED; evidence_references and uncertainties must be arrays; stop_condition_triggered must be boolean; stop_rationale must be a string or null.';
    }
}
