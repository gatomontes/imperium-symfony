<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use App\Imperium\Runtime\Cognition\BoundedTransientCognitionCaller;
use Symfony\AI\Agent\AgentInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SymfonyAiLegateCognitionGateway implements LegateCognitionGateway
{
    public function __construct(
        #[Autowire(service: 'ai.agent.citadel_legate_deepseek_v4_flash')] private AgentInterface $agent,
        private ?BoundedTransientCognitionCaller $caller = null,
    ) {}

    public function cognize(array $providerActivation): array
    {
        $model = $providerActivation['model']['provider_model_version'] ?? null;
        if (!is_string($model) || (!str_starts_with($model, 'deepseek/deepseek-v4-flash@') && 'deepseek/deepseek-v4-flash' !== $model && 'deepseek-v4-flash' !== $model)) {
            throw new \RuntimeException('CIT401_BOUND_MODEL_GATEWAY_UNAVAILABLE');
        }
        $prompt = implode("\n", [
            'Exact governed provider activation: '.json_encode($providerActivation, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'Perform exactly one internal cognition turn under the supplied Legate identity and exact commission contract.',
            'Use only the sealed contract inputs. Do not use tools, memory, credentials, network, external data, or perform external action or execution.',
            'Return one JSON object with exactly disposition, output, evidence_references, uncertainties, stop_condition_triggered, and stop_rationale.',
            'disposition must be COMPLETED, STOPPED, or REFUSED. output and stop_rationale must be non-empty strings. evidence_references and uncertainties must be arrays of non-empty strings. stop_condition_triggered must be boolean and must be true for STOPPED or REFUSED.',
        ]);
        $content = ($this->caller ?? new BoundedTransientCognitionCaller())->call($this->agent, $prompt, 'CIT402_CITADEL_LEGATE_COGNITION_PROVIDER_FAILURE');
        if (!is_string($content)) throw new \RuntimeException('CIT403_CITADEL_LEGATE_COGNITION_RESPONSE_INVALID');
        $content=trim($content);if(str_starts_with($content,'```'))$content=preg_replace('/^```(?:json)?\s*|\s*```$/i','',$content)??$content;
        try{$result=json_decode(trim($content),true,16,JSON_THROW_ON_ERROR);}catch(\JsonException $error){throw new \RuntimeException('CIT403_CITADEL_LEGATE_COGNITION_RESPONSE_INVALID',0,$error);}
        return is_array($result)?$result:throw new \RuntimeException('CIT403_CITADEL_LEGATE_COGNITION_RESPONSE_INVALID');
    }
}
