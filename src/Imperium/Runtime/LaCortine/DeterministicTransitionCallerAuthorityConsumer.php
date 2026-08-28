<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DeterministicTransitionCallerAuthorityConsumer
{
    private RecordReferenceValidator $validator;
    private AuthorityConsumptionStore $consumptions;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
        $atomic = new AtomicTransition($root);
        $this->consumptions = new AuthorityConsumptionStore(new ImmutableRecordStore($root, $atomic), $atomic);
    }

    public function consume(string $authorityId, string $transition, array $target, string $consumer, \DateTimeImmutable $at): array
    {
        if (!preg_match('/^deterministic-transition-caller-authority-[a-f0-9]{20}$/', $authorityId)
            || !in_array($transition, DeterministicTransitionCallerAuthorityContract::TRANSITIONS, true)
            || DeterministicTransitionCallerAuthorityContract::REQUIRED_REFERENCE_FIELDS !== array_keys($target)
            || !is_string($target['id']) || '' === trim($target['id'])
            || !is_string($target['digest']) || !preg_match('/^[a-f0-9]{64}$/', $target['digest'])
            || '' === trim($consumer)) {
            throw new \InvalidArgumentException('IGA110_CALLER_AUTHORITY_CONSUMPTION_INPUT_INVALID');
        }

        $authority = $this->validator->read($this->root.'/'.DeterministicTransitionCallerAuthorityIssuanceService::AUTHORITIES.'/'.$authorityId.'.json', 'IGA111_CALLER_AUTHORITY_ABSENT');
        if (!$this->validator->isIntact($authority)
            || DeterministicTransitionCallerAuthorityContract::REQUIRED_FIELDS !== array_keys($authority)
            || DeterministicTransitionCallerAuthorityContract::SCHEMA !== ($authority['schema'] ?? null)
            || $authorityId !== ($authority['authority_id'] ?? null)
            || $transition !== ($authority['permitted_transition'] ?? null)
            || $target !== ($authority['target'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || false !== ($authority['continuing_authority'] ?? null)
            || true !== ($authority['sealed'] ?? null)
            || new \DateTimeImmutable((string) $authority['issued_at']) > $at
            || new \DateTimeImmutable((string) $authority['expires_at']) <= $at) {
            throw new \RuntimeException('IGA112_CALLER_AUTHORITY_INVALID');
        }

        $principal = $authority['principal'] ?? null;
        $source = $authority['source'] ?? null;
        if (!is_array($principal) || DeterministicTransitionCallerAuthorityContract::REQUIRED_PRINCIPAL_FIELDS !== array_keys($principal)
            || !is_array($source) || DeterministicTransitionCallerAuthorityContract::REQUIRED_REFERENCE_FIELDS !== array_keys($source)) {
            throw new \RuntimeException('IGA112_CALLER_AUTHORITY_INVALID');
        }
        $sourcePath = 'curia' === $principal['office']
            ? 'var/imperium/offices/curia/occupancy/'.$source['id'].'.json'
            : DeterministicTransitionCallerAuthorityIssuanceService::IMPERATOR_PRINCIPALS.'/'.$source['id'].'.json';
        $current = $this->validator->read($this->root.'/'.$sourcePath, 'IGA113_CALLER_PRINCIPAL_ABSENT');
        $generation = 'curia' === $principal['office'] ? ($current['occupancy_generation'] ?? null) : ($current['principal_generation'] ?? null);
        if (!$this->validator->isIntact($current)
            || $source['digest'] !== ($current['record_digest'] ?? null)
            || $authority['instance_id'] !== ($current['instance_id'] ?? null)
            || $principal['binding_id'] !== ($current['binding_id'] ?? null)
            || $principal['generation'] !== $generation
            || 'ACTIVE' !== ($current['status'] ?? null)) {
            throw new \RuntimeException('IGA114_CALLER_PRINCIPAL_STALE');
        }

        return $this->consumptions->consume($authorityId, (string) $source['id'], (string) $source['digest'], $consumer, $at);
    }
}
