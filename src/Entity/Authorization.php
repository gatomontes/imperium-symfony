<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'authorization')]
#[ORM\UniqueConstraint(name: 'uniq_authorization_proposal', columns: ['proposal_id'])]
class Authorization
{
    public const PENDING = 'pending';
    public const AUTHORIZED = 'authorized';
    public const REFUSED = 'refused';

    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private string $id;

    #[ORM\OneToOne(targetEntity: Proposal::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Proposal $proposal;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $resources;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $effects;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $limits;

    #[ORM\Column(length: 32)]
    private string $status = self::PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $decidedAt = null;

    /** @param list<string> $effects */
    public function __construct(Proposal $proposal, array $effects)
    {
        if (Proposal::APPROVED !== $proposal->getStatus()) {
            throw new \DomainException('Proposal approval is required before requesting resource or effect authorization.');
        }

        $this->id = Uuid::v7()->toRfc4122();
        $this->proposal = $proposal;
        $content = $proposal->getContent();
        $this->resources = $content['resourceRequirements'];
        $this->effects = $effects;
        $this->limits = $content['limits'];
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProposal(): Proposal
    {
        return $this->proposal;
    }

    /** @return list<string> */
    public function getResources(): array
    {
        return $this->resources;
    }

    /** @return list<string> */
    public function getEffects(): array
    {
        return $this->effects;
    }

    /** @return list<string> */
    public function getLimits(): array
    {
        return $this->limits;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getDecidedAt(): ?\DateTimeImmutable
    {
        return $this->decidedAt;
    }

    public function decide(bool $authorize): void
    {
        if (self::PENDING !== $this->status) {
            throw new \DomainException('This authorization request has already been decided.');
        }

        $this->status = $authorize ? self::AUTHORIZED : self::REFUSED;
        $this->decidedAt = new \DateTimeImmutable();
    }
}
