<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\DecisionIntegrity;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DecisionIntegrityRecordStore
{
    private const string SURFACES = 'var/imperium/decision-integrity/surfaces';
    private const string DECISIONS = 'var/imperium/decision-integrity/records';

    private ImmutableRecordStore $records;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $root,
        private InstitutionalDecisionSurfaceValidator $surfaceValidator = new InstitutionalDecisionSurfaceValidator(),
        private DefensibleDecisionRecordValidator $decisionValidator = new DefensibleDecisionRecordValidator(),
        ?ImmutableRecordStore $records = null,
    ) {
        $this->records = $records ?? new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function putSurface(array $surface): array
    {
        $this->surfaceValidator->validate($surface, false);
        $sealed = $this->records->put(self::SURFACES, (string) $surface['surface_id'], $surface);
        $this->surfaceValidator->validate($sealed);

        return $sealed;
    }

    public function putDecision(array $decision): array
    {
        $this->decisionValidator->validate($decision, false);
        $surface = $this->records->read(self::SURFACES, (string) $decision['source_decision_surface']['id']);
        $this->surfaceValidator->validate($surface);
        if (($surface['record_digest'] ?? null) !== $decision['source_decision_surface']['digest']
            || ($surface['instance_id'] ?? null) !== $decision['instance_id']
            || ($surface['proceeding_id'] ?? null) !== $decision['proceeding_id']) {
            throw new \RuntimeException('DI140_DECISION_SURFACE_LINEAGE_INVALID');
        }
        $supersedes = $decision['supersession']['supersedes'];
        if (null !== $supersedes) {
            if (($supersedes['id'] ?? null) === $decision['decision_record_id']) {
                throw new \RuntimeException('DI141_SUPERSESSION_LINEAGE_INVALID');
            }
            $prior = $this->records->read(self::DECISIONS, (string) $supersedes['id']);
            $this->decisionValidator->validate($prior);
            if (($prior['record_digest'] ?? null) !== $supersedes['digest']
                || ($prior['instance_id'] ?? null) !== $decision['instance_id']
                || ($prior['proceeding_id'] ?? null) !== $decision['proceeding_id']) {
                throw new \RuntimeException('DI141_SUPERSESSION_LINEAGE_INVALID');
            }
        }
        $sealed = $this->records->put(self::DECISIONS, (string) $decision['decision_record_id'], $decision);
        $this->decisionValidator->validate($sealed);

        return $sealed;
    }

    public function readSurface(string $id): array
    {
        $surface = $this->records->read(self::SURFACES, $id);
        $this->surfaceValidator->validate($surface);

        return $surface;
    }

    public function readDecision(string $id): array
    {
        $decision = $this->records->read(self::DECISIONS, $id);
        $this->decisionValidator->validate($decision);

        return $decision;
    }
}
