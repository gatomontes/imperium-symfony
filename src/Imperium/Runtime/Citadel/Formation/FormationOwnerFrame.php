<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

use App\Imperium\Runtime\Persistence\AtomicTransition;

/** Process-local capability. Neither serialized evidence nor a reentrant lock. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class FormationOwnerFrame
{
    private bool $active = true;
    private function __construct(private readonly FormationJournal $journal) {}
    private function __clone() {}
    public function __serialize(): array { throw new \RuntimeException('PPC301_OWNER_FRAME_NOT_SERIALIZABLE'); }
    public function __unserialize(array $data): void { throw new \RuntimeException('PPC301_OWNER_FRAME_NOT_SERIALIZABLE'); }

    /** Only issuance path: acquire the real, unchanged Formation fence first. */
    public static function run(string $root, callable $operation): mixed
    {
        return (new AtomicTransition($root))->run('citadel-formation', static function () use ($root, $operation): mixed {
            $owner = new self(new FormationJournal($root));
            try { return $operation($owner); }
            finally { $owner->active = false; }
        });
    }

    public function assertOwner(FormationJournal $journal): void
    {
        if (!$this->active || !$this->journal->sameOwner($journal)) {
            throw new \RuntimeException('PPC302_LIVE_SAME_ROOT_OWNER_REQUIRED');
        }
    }

    public function frame(): array
    {
        return $this->journal->readInOwner($this);
    }
}
