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

        return $this->records->put(self::DECISIONS, $decision['decision_id'], $decision);
    }

    public function putAuthority(array $authority, mixed ...$lineage): array
    {
        $this->validator->assertAuthority($authority, ...$lineage);

        return $this->records->put(self::AUTHORITIES, $authority['authority_id'], $authority);
    }

    public function putAdoptionTarget(array $target, mixed ...$lineage): array
    {
        $this->validator->assertAdoptionTarget($target, ...$lineage);

        return $this->records->put(
            self::ADOPTION_TARGETS,
            $target['adoption_target_id'],
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
}
