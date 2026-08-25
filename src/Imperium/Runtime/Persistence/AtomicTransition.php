<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Persistence;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AtomicTransition
{
    private string $locks;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->locks = $root.'/var/imperium/runtime/transition-locks';
    }

    public function run(string $scope, callable $transition): mixed
    {
        if (!preg_match('/^[a-z0-9][a-z0-9._:-]{2,180}$/', $scope)) {
            throw new \InvalidArgumentException('PST100_ATOMIC_TRANSITION_SCOPE_INVALID');
        }
        if (!is_dir($this->locks) && !mkdir($this->locks, 0770, true) && !is_dir($this->locks)) {
            throw new \RuntimeException('PST101_ATOMIC_TRANSITION_LOCK_STORAGE_FAILED');
        }
        $path = $this->locks.'/'.hash('sha256', $scope).'.lock';
        $handle = fopen($path, 'c+');
        if (false === $handle || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw new \RuntimeException('PST102_ATOMIC_TRANSITION_LOCK_FAILED');
        }

        try {
            return $transition();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
