<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Cognition;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

final class BoundedTransientCognitionCaller
{
    public function call(AgentInterface $agent, string $prompt, string $errorPrefix): mixed
    {
        $maximumAttempts = 3;
        for ($attempt = 1; $attempt <= $maximumAttempts; ++$attempt) {
            try {
                $content = $agent->call(new MessageBag(Message::ofUser($prompt)))->getContent();
            } catch (\Throwable $exception) {
                if (!$this->isTimeout($exception)) throw $exception;
                if ($maximumAttempts === $attempt) throw new \RuntimeException($errorPrefix.': PROVIDER_TIMEOUT', 0, $exception);
                $this->backoff($attempt);
                continue;
            }
            if (is_string($content) && '' === trim($content)) {
                if ($maximumAttempts === $attempt) throw new \RuntimeException($errorPrefix.': EMPTY_RESPONSE');
                $this->backoff($attempt);
                continue;
            }
            return $content;
        }
        throw new \RuntimeException($errorPrefix.': TRANSIENT_RETRY_EXHAUSTED');
    }

    private function backoff(int $attempt): void
    {
        usleep(100_000 * $attempt);
    }

    private function isTimeout(\Throwable $exception): bool
    {
        return 1 === preg_match('/(?:idle\s+)?timeout|timed\s+out/i', $exception->getMessage());
    }
}
