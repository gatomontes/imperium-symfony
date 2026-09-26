<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'mission_authorization')]
#[ORM\UniqueConstraint(name: 'uniq_authorization_proposal_version', columns: ['proposal_id', 'version'])]
class Authorization
{
    public const PENDING = 'pending';
    public const AUTHORIZED = 'authorized';
    public const REFUSED = 'refused';

    #[ORM\Id]
    #[ORM\Column(length: 36)]
    private string $id;

    #[ORM\ManyToOne(targetEntity: Proposal::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Proposal $proposal;

    #[ORM\Column]
    private int $version;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $resources;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $effects;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $limits;

    /** @var array{capability:string,effect:string,root:string,visibility:string,allowedExtensions:list<string>,maxFiles:int,overwrite:bool,maxBytes:int}|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $executionScope = null;

    #[ORM\Column(length: 32)]
    private string $status = self::PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $decidedAt = null;

    /**
     * @param list<string> $effects
     * @param array{capability:string,effect:string,root:string,visibility:string,allowedExtensions:list<string>,maxFiles:int,overwrite:bool,maxBytes:int}|null $executionScope
     */
    public function __construct(Proposal $proposal, int $version, array $effects, ?array $executionScope = null)
    {
        if (Proposal::APPROVED !== $proposal->getStatus()) {
            throw new \DomainException('Proposal approval is required before requesting resource or effect authorization.');
        }

        $this->id = Uuid::v7()->toRfc4122();
        if ($version < 1) {
            throw new \InvalidArgumentException('Authorization version must be positive.');
        }
        $this->proposal = $proposal;
        $this->version = $version;
        $content = $proposal->getContent();
        $this->resources = $content['resourceRequirements'];
        $this->effects = $effects;
        $this->limits = $content['limits'];
        $this->executionScope = $executionScope;
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

    public function getVersion(): int
    {
        return $this->version;
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

    /** @return array{capability:string,effect:string,root:string,visibility:string,allowedExtensions:list<string>,maxFiles:int,overwrite:bool,maxBytes:int}|null */
    public function getExecutionScope(): ?array
    {
        return $this->executionScope;
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
