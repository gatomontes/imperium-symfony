<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderBindingSuccessorProductionAdoptionFixtureStore
{
    public const string DECISIONS =
        'var/imperium/evidence/provider-binding-successor-production-adoption/v2-decisions';
    public const string AUTHORITIES =
        'var/imperium/evidence/provider-binding-successor-production-adoption/v2-authorities';
    public const string ADOPTION_TARGETS =
        'var/imperium/evidence/provider-binding-successor-production-adoption/adoption-targets';

    private ImmutableRecordStore $records;
    private ProviderBindingSuccessorProductionAdoptionContractValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->validator = new ProviderBindingSuccessorProductionAdoptionContractValidator();
    }

    public function putDecision(array $decision, mixed ...$lineage): array
    {
        $this->validator->assertDecision($decision, ...$lineage);

        return $this->records->put(self::DECISIONS, $this->rootId($decision), $decision);
    }

    public function putAuthority(array $authority, mixed ...$lineage): array
    {
        $this->validator->assertAuthority($authority, ...$lineage);

        return $this->records->put(self::AUTHORITIES, $this->rootId($authority), $authority);
    }

    public function putAdoptionTarget(array $target, mixed ...$lineage): array
    {
        $this->validator->assertAdoptionTarget($target, ...$lineage);

        return $this->records->put(
            self::ADOPTION_TARGETS,
            $this->rootId($target),
            $target,
        );
    }

    public function readDecision(string $id): array
    {
        return $this->records->read(self::DECISIONS, $id);
    }

    public function readAuthority(string $id): array
    {
        return $this->records->read(self::AUTHORITIES, $id);
    }

    public function readAdoptionTarget(string $id): array
    {
        return $this->records->read(self::ADOPTION_TARGETS, $id);
    }

    private function rootId(array $record): string
    {
        $root = $record['replay_contention_root']
            ?? $record['successor_creation_authority_issuance_target']['replay_contention_root']
            ?? null;
        $rootId = is_array($root) ? ($root['root_id'] ?? null) : null;
        if (!is_string($rootId)) {
            throw new \RuntimeException('PBA730_REPLAY_CONTENTION_ROOT_INVALID');
        }

        return $rootId;
    }
}
