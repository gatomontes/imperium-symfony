<?php

namespace App\Curia;

use App\Atheneum\InterviewRecords;
use App\Entity\Interview;
use Symfony\Component\Lock\LockFactory;

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

    private function requestReply(Interview $interview): void
    {
        $this->seneschal->assertConfigured();
        $interview->beginAttempt();
        // Reserve the attempt before inference; a terminated process cannot reset the limit.
        $this->records->save($interview);
        try {
            $reply = $this->seneschal->reply($interview);
        } catch (\Throwable) {
            // Do not print provider exceptions, request bodies, or credentials in the CLI.
            throw new \DomainException('No valid reply was saved. Your message is retained. Use /retry explicitly; another attempt may incur provider charges.');
        }
        $text = trim($reply->message);
        if ($reply->readyToDraft) {
            $text .= "\n\n".Interview::PERMISSION_QUESTION;
        }
        $interview->receive($text, $reply->readyToDraft);
    }

    /** @param callable(Interview): void $operation */
    private function withLock(string $id, callable $operation): Interview
    {
        $lock = $this->lockFactory->createLock('imperium.interview.'.$id);
        if (!$lock->acquire()) {
            throw new \DomainException('This interview is being updated by another process. Try again after it finishes.');
        }
        try {
            $interview = $this->records->get($id);
            $operation($interview);
            $this->records->save($interview);

            return $interview;
        } finally {
            $lock->release();
        }
    }
}
