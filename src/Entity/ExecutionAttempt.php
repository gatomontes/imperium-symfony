<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'execution_attempt')]
#[ORM\UniqueConstraint(name: 'uniq_execution_authorization', columns: ['authorization_id'])]
class ExecutionAttempt
{
    public const PREPARED = 'prepared';
    public const EFFECT_STARTED = 'effect_started';
    public const SUCCEEDED = 'succeeded';
    public const FAILED = 'failed';
    public const OPERATION_LOCAL_FILE_CREATE = 'local_file_create';

    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Authorization::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Authorization $authorization;

    #[ORM\Column(length: 64)]
    private string $operation = self::OPERATION_LOCAL_FILE_CREATE;

    #[ORM\Column(length: 255)]
    private string $targetPath;

    #[ORM\Column(length: 64)]
    private string $contentSha256;

    #[ORM\Column(length: 32)]
    private string $status = self::PREPARED;

    #[ORM\Column(nullable: true)]
    private ?int $bytesWritten = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $failureCode = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    public function __construct(Authorization $authorization, string $targetPath, string $contentSha256)
    {
        if (Authorization::AUTHORIZED !== $authorization->getStatus()) {
            throw new \DomainException('Authorized scope is required before execution can be prepared.');
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->authorization = $authorization;
        $this->targetPath = $targetPath;
        $this->contentSha256 = $contentSha256;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string { return $this->id; }
    public function getAuthorization(): Authorization { return $this->authorization; }
    public function getOperation(): string { return $this->operation; }
    public function getTargetPath(): string { return $this->targetPath; }
    public function getContentSha256(): string { return $this->contentSha256; }
    public function getStatus(): string { return $this->status; }
    public function getBytesWritten(): ?int { return $this->bytesWritten; }
    public function getFailureCode(): ?string { return $this->failureCode; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getCompletedAt(): ?\DateTimeImmutable { return $this->completedAt; }

    public function startEffect(): void
    {
        if (self::PREPARED !== $this->status) {
            throw new \DomainException('Only a prepared execution attempt can start an effect.');
        }
        $this->status = self::EFFECT_STARTED;
    }

    public function succeed(int $bytesWritten): void
    {
        if (self::EFFECT_STARTED !== $this->status) {
            throw new \DomainException('Only a started execution effect can succeed.');
        }
        $this->status = self::SUCCEEDED;
        $this->bytesWritten = $bytesWritten;
        $this->completedAt = new \DateTimeImmutable();
    }

    public function fail(string $failureCode): void
    {
        if (!in_array($this->status, [self::PREPARED, self::EFFECT_STARTED], true)) {
            throw new \DomainException('Only an unfinished execution attempt can fail.');
        }
        $this->status = self::FAILED;
        $this->failureCode = $failureCode;
        $this->completedAt = new \DateTimeImmutable();
    }
}
