<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Operator-pinned grant custody; hashes alone without the configured pin grant nothing. */
final readonly class TransitionAuthority
{
    public function __construct(private TransitionStore $store, private string $grantPin, private ?\Closure $clock = null)
    {
    }

    public function grant(): array
    {
        $grant = $this->store->read('grant');
        if (null === $grant) { throw new \RuntimeException('EAT_GRANT_ABSENT'); }
        TransitionContract::grant($grant, $this->grantPin);
        if ($grant['storage'] !== $this->store->identity()) { throw new \RuntimeException('EAT_STORAGE_ROOT_SUBSTITUTION'); }
        return $grant;
    }

    public function expected(array $grant): array
    {
        $decision = ['schema' => TransitionContract::SCHEMA.'/decision',
            'principal' => $grant['principal'], 'generation' => $grant['generation'],
            'principal_activation' => $grant['principal_activation'], 'scope' => TransitionContract::SCOPE,
            'grant' => TransitionContract::digest($grant), 'root' => TransitionContract::root($grant),
            'disposition' => 'AUTHORIZED', 'continuing_authority' => false];
        return ['schema' => TransitionContract::SCHEMA.'/authority',
            'grant' => TransitionContract::digest($grant), 'root' => TransitionContract::root($grant),
            'decision' => $decision, 'consumer' => TransitionContract::CONSUMER, 'authority_single_use' => true];
    }

    /** Mechanical issuance from an already provisioned exact Operator Root grant. */
    public function issue(): array
    {
        return $this->store->locked(function (): array {
            $at = null === $this->clock ? time() : ($this->clock)();
            $grant = $this->grant();
            TransitionContract::current($grant, $at);
            $this->assertNotRevoked();
            if (null !== $this->store->read('journal') || $this->store->pending('journal')
                || $this->store->pending('commit') || $this->store->pending('refusal')
                || null !== $this->store->read('commit') || null !== $this->store->read('refusal')) {
                throw new \RuntimeException('EAT_ISSUANCE_AFTER_ATTEMPT_REFUSED');
            }
            return $this->store->put('authority', $this->expected($grant));
        });
    }

    public function assertNotRevoked(): void
    {
        if (null !== $this->store->read('revocation') || $this->store->pending('revocation')) {
            throw new \RuntimeException('EAT_AUTHORITY_REVOKED');
        }
    }

    /** Configured custodian action, serialized against the exact consumer. */
    public function revoke(): void
    {
        $this->store->locked(function (): void {
            $grant = $this->grant();
            if (null !== $this->store->read('commit') || $this->store->pending('commit')) {
                throw new \RuntimeException('EAT_COMMIT_PRECLUDES_REVOCATION');
            }
            $this->store->put('revocation', ['grant' => TransitionContract::digest($grant), 'revoked' => true]);
        });
    }
}
