<?php

namespace App\Curia;

use App\Atheneum\InterviewRecords;
use App\Entity\Interview;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class InterviewService
{
    public function __construct(
        private InterviewRecords $records,
        private Seneschal $seneschal,
        private LockFactory $lockFactory,
    ) {
    }

    public function submit(string $id, string $text): Interview
    {
        return $this->withLock($id, function (Interview $interview) use ($text): void {
            $interview->submit($text);
            // Preserve operator input even if credentials are absent or inference fails.
            $this->records->save($interview);
            $this->requestReply($interview);
        });
    }

    public function retry(string $id): Interview
    {
        return $this->withLock($id, $this->requestReply(...));
    }

    public function decideDraftPermission(string $id, bool $approve, int $observedVersion): Interview
    {
        return $this->withLock($id, static function (Interview $interview) use ($approve, $observedVersion): void {
            if ($interview->getVersion() !== $observedVersion) {
                throw new \DomainException('The interview changed. Review the current exchange before deciding.');
            }
            $interview->decideDraftPermission($approve);
        });
    }

    public function delete(string $id): void
    {
        $this->withLock($id, $this->records->delete(...), save: false);
    }

    private function requestReply(Interview $interview): void
    {
        $this->seneschal->assertConfigured();
        $interview->beginAttempt();
        // Reserve the attempt before inference; a terminated process cannot reset the limit.
        $this->records->save($interview);
        try {
            $reply = $this->seneschal->reply($interview);
        } catch (\Throwable $error) {
            throw new \DomainException('No valid reply was saved. '.$this->failureHint($error).' Your message is retained. Use /retry explicitly; another attempt may incur provider charges.');
        }
        $text = trim($reply->message);
        if ($reply->readyToDraft) {
            $text .= "\n\n".Interview::PERMISSION_QUESTION;
        }
        $interview->receive($text, $reply->readyToDraft, $reply->alias);
    }

    private function failureHint(\Throwable $error): string
    {
        // Match types/status only. Provider messages may contain private data.
        do {
            if ($error instanceof InvalidSeneschalReply) {
                return $error->getMessage();
            }
            if ($error instanceof \JsonException) {
                return 'Reply format: DeepSeek returned invalid JSON.';
            }
            if ($error instanceof HttpExceptionInterface) {
                $status = $error->getResponse()->getStatusCode();
                $hint = match ($status) {
                    401 => 'Check DEEPSEEK_API_KEY in .env.local.',
                    402 => 'Check your DeepSeek API balance.',
                    403 => 'Check API access permissions on your DeepSeek account.',
                    404 => 'Check the configured DeepSeek model and endpoint.',
                    400, 422 => 'DeepSeek rejected the request; check the configured model and request options.',
                    429 => 'DeepSeek is rate-limiting requests. Wait before retrying.',
                    default => $status >= 500 ? 'DeepSeek is unavailable. Try again later.' : 'The provider returned an HTTP error.',
                };

                return 'HTTP '.$status.'. '.$hint;
            }
            if ($error instanceof TransportExceptionInterface) {
                return 'Could not reach DeepSeek. Check your connection and PHP TLS/certificate configuration.';
            }
            if ($error instanceof \JsonException || $error instanceof SerializerException || $error instanceof \UnexpectedValueException) {
                return 'The reply was empty or did not match the expected interview format.';
            }
            $previous = $error->getPrevious();
            if (null === $previous) {
                $class = new \ReflectionClass($error);

                return 'Reply processing failed ('.($class->isAnonymous() ? 'unknown error' : $class->getShortName()).').';
            }
            $error = $previous;
        } while (true);
    }

    /** @param callable(Interview): void $operation */
    private function withLock(string $id, callable $operation, bool $save = true): Interview
    {
        $lock = $this->lockFactory->createLock('imperium.interview.'.$id);
        if (!$lock->acquire()) {
            throw new \DomainException('This interview is being updated by another process. Try again after it finishes.');
        }
        try {
            $interview = $this->records->get($id);
            $operation($interview);
            if ($save) {
                $this->records->save($interview);
            }

            return $interview;
        } finally {
            $lock->release();
        }
    }
}
