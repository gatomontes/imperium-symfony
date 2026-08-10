<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

use App\Imperium\Runtime\LaCortine\SortieManifest;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

final readonly class SymfonyAiSortieCognitionGateway implements SortieCognitionGateway
{
    public function __construct(private AgentInterface $agent)
    {
    }

    public function execute(SortieManifest $manifest): SortieCognitionResult
    {
        if ([] !== $manifest->toolIds || [] !== $manifest->capabilityIds) {
            throw new \RuntimeException('SORTIE_AI_TOOLS_UNBOUND: the first Symfony AI sortie adapter permits cognition only; tool and capability execution require explicit boundary adapters.');
        }

        $message = new MessageBag(
            Message::ofUser($this->taskMessage($manifest)),
        );

        $result = $this->agent->call($message);
        $content = $result->getContent();
        if (!is_string($content) || '' === trim($content)) {
            throw new \RuntimeException('SORTIE_AI_EMPTY_RESULT: cognition provider returned no usable text payload.');
        }

        return new SortieCognitionResult(
            $content,
            ['ai.platform.generic.deepseek'],
            [],
            [],
            new \DateTimeImmutable(),
        );
    }

    private function taskMessage(SortieManifest $manifest): string
    {
        $destinations = [] === $manifest->destinations
            ? 'none'
            : implode(', ', $manifest->destinations);

        return implode("\n", [
            'Objective: '.$manifest->objective,
            'Permitted destination context: '.$destinations,
            'Context digest: '.$manifest->contextDigest,
            'Expected return contract: '.$manifest->expectedReturnContract,
            '',
            'Perform only the objective above. No tools or credential-bearing capabilities are available in this sortie.',
        ]);
    }
}
