<?php

namespace App\Entity;

use App\Repository\InterviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: InterviewRepository::class)]
class Interview
{
    public const INTERVIEWING = 'interviewing';
    public const AWAITING_PERMISSION = 'awaiting_draft_permission';
    public const DRAFT_AUTHORIZED = 'draft_authorized';
    public const PERMISSION_QUESTION = 'I am ready to draft a proposal. Do you approve?';
    public const MAX_ATTEMPTS = 20;
    public const MAX_MESSAGE_LENGTH = 6000;
    public const MAX_CONTEXT_LENGTH = 80000;

    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private string $id;

    #[ORM\Column(length: 32)]
    private string $status = self::INTERVIEWING;

    /** @var list<array{role: string, text: string, at: string}> */
    #[ORM\Column(type: Types::JSON)]
    private array $exchanges = [];

    #[ORM\Column]
    private bool $pendingReply = false;

    #[ORM\Column]
    private int $attempts = 0;

    #[ORM\Version]
    #[ORM\Column(type: Types::INTEGER)]
    private int $version = 1;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $draftAuthorizedAt = null;

    public function __construct()
    {
        $this->id = Uuid::v7()->toRfc4122();
        $this->createdAt = $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getStatus(): string
    {
        return $this->status;
    }
    public function hasPendingReply(): bool
    {
        return $this->pendingReply;
    }
    public function getAttempts(): int
    {
        return $this->attempts;
    }
    public function getVersion(): int
    {
        return $this->version;
    }
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function getDraftAuthorizedAt(): ?\DateTimeImmutable
    {
        return $this->draftAuthorizedAt;
    }

    /** @return list<array{role: string, text: string, at: string}> */
    public function getExchanges(): array
    {
        return $this->exchanges;
    }

    public function submit(string $text): void
    {
        $text = trim($text);
        if ('' === $text || mb_strlen($text) > self::MAX_MESSAGE_LENGTH) {
            throw new \DomainException('Enter between 1 and 6000 characters.');
        }
        if (self::DRAFT_AUTHORIZED === $this->status) {
            throw new \DomainException('Drafting permission is already recorded; this interview is closed.');
        }
        if ($this->pendingReply) {
            throw new \DomainException('A saved message is awaiting a reply. Use /retry before adding another message.');
        }
        if ($this->attempts >= self::MAX_ATTEMPTS) {
            throw new \DomainException('The interview has reached its limit of 20 model attempts.');
        }
        $length = array_sum(array_map(static fn (array $entry): int => mb_strlen($entry['text']), $this->exchanges));
        if ($length + mb_strlen($text) > self::MAX_CONTEXT_LENGTH) {
            throw new \DomainException('This interview has reached its context limit. Its record remains available.');
        }
        $this->status = self::INTERVIEWING;
        $this->append('user', $text);
        $this->pendingReply = true;
    }

    public function beginAttempt(): void
    {
        if (!$this->pendingReply || self::DRAFT_AUTHORIZED === $this->status) {
            throw new \DomainException('There is no pending reply to retry.');
        }
        if ($this->attempts >= self::MAX_ATTEMPTS) {
            throw new \DomainException('The interview has reached its limit of 20 model attempts.');
        }
        ++$this->attempts;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function receive(string $text, bool $ready): void
    {
        if (!$this->pendingReply) {
            throw new \LogicException('No reply was requested.');
        }
        $this->append('assistant', $text);
        $this->pendingReply = false;
        $this->status = $ready ? self::AWAITING_PERMISSION : self::INTERVIEWING;
    }

    public function decideDraftPermission(bool $approve): void
    {
        if (self::AWAITING_PERMISSION !== $this->status || $this->pendingReply) {
            throw new \DomainException('Seneschal has not requested permission to draft.');
        }
        $this->append('decision', $approve ? 'Operator granted permission to draft a proposal.' : 'Operator declined permission to draft. Continue clarification.');
        $this->status = $approve ? self::DRAFT_AUTHORIZED : self::INTERVIEWING;
        if ($approve) {
            $this->draftAuthorizedAt = $this->updatedAt;
        }
    }

    private function append(string $role, string $text): void
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->exchanges[] = ['role' => $role, 'text' => $text, 'at' => $this->updatedAt->format(\DateTimeInterface::ATOM)];
    }
}
