<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'proposal')]
#[ORM\UniqueConstraint(name: 'uniq_proposal_interview_version', columns: ['interview_id', 'version'])]
class Proposal
{
    public const DRAFT = 'draft';

    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Interview::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Interview $interview;

    #[ORM\Column]
    private int $version;

    #[ORM\Column]
    private int $sourceInterviewVersion;

    #[ORM\Column(length: 32)]
    private string $status = self::DRAFT;

    /** @var array{objective:string,deliverable:string,steps:list<string>,acceptanceCriteria:list<string>,resourceRequirements:list<string>,limits:list<string>,unresolvedAssumptions:list<string>} */
    #[ORM\Column(type: Types::JSON)]
    private array $content;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @param array{objective:string,deliverable:string,steps:list<string>,acceptanceCriteria:list<string>,resourceRequirements:list<string>,limits:list<string>,unresolvedAssumptions:list<string>} $content */
    public function __construct(Interview $interview, int $version, int $sourceInterviewVersion, array $content)
    {
        if (Interview::DRAFT_AUTHORIZED !== $interview->getStatus()) {
            throw new \DomainException('Drafting permission is required before a proposal can be saved.');
        }
        if ($version < 1) {
            throw new \InvalidArgumentException('Proposal version must be positive.');
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->interview = $interview;
        $this->version = $version;
        $this->sourceInterviewVersion = $sourceInterviewVersion;
        $this->content = $content;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getInterview(): Interview
    {
        return $this->interview;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getSourceInterviewVersion(): int
    {
        return $this->sourceInterviewVersion;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    /** @return array{objective:string,deliverable:string,steps:list<string>,acceptanceCriteria:list<string>,resourceRequirements:list<string>,limits:list<string>,unresolvedAssumptions:list<string>} */
    public function getContent(): array
    {
        return $this->content;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
