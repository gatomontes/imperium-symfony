<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Native exact pre-effect transition entrypoint; accepts only the durable authority id. */
final readonly class NativeConsumer
{
    public function __construct(private NativeState $state, private ?\Closure $clock = null, private ?\Closure $checkpoint = null) {}

    public function execute(string $authorityId): array
    {
        return $this->state->locked(function () use ($authorityId): array {
            $at = $this->now();
            $authority = (new NativeAuthority($this->state))->load($authorityId, $at);
            if (null === $authority['decision']['successor']) { throw new \RuntimeException('NIR_EXACT_SUCCESSOR_REQUIRED'); }
            $p = (new NativePrincipal($this->state))->load($authority['principal']['id'], $at);
            $root = NativeBindingReader::root($p['instance_id'], $p['provider_binding']['id'], $p['operation']);
            if (null !== $this->state->get('transitions', $root)) { throw new \RuntimeException('NIR_ALREADY_COMMITTED_READ_ONLY_REPLAY'); }
            if (null !== $this->state->get('journals', $root)) { throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED'); }
            return (new NativeMigration($this->state, $this->checkpoint))->locked($p['instance_id'], function (array $manifest, array $legacy) use ($p, $root, $authorityId, $authority, $at): array {
                $admission = new NativeAdmission($this->state);
                $admission->records($authorityId, $at);
                $journal = NativeState::seal(['schema' => 'imperium.la-cortine.native-transition-journal/v1', 'journal_id' => 'journal-'.$root,
                    'root' => $root, 'storage' => $this->state->identity(), 'authority' => NativeState::ref($authority['authority'], 'authority_id'),
                    'manifest_digest' => TransitionContract::digest($manifest), 'legacy_storage_identities' => array_keys($legacy),
                    'prepared_at' => $at, 'state' => 'PREPARED_NO_AUTHORITY_CONSUMED']);
                $this->state->put('journals', $root, $journal);
                foreach ($legacy as $identity => $store) {
                    $store->put('retirement', ['schema' => 'imperium.native-transition-legacy-retirement/v1', 'legacy_storage' => $identity,
                        'native_storage' => $this->state->identity(), 'root' => $root, 'journal' => NativeState::ref($journal, 'journal_id'),
                        'retry_authorized' => false]);
                }
                $commitAt = $this->now();
                $records = $admission->records($authorityId, $commitAt);
                $commit = NativeState::seal(['schema' => 'imperium.la-cortine.native-transition-commit/v1', 'root' => $root,
                    'authority_id' => $authorityId, 'journal' => NativeState::ref($journal, 'journal_id'),
                    'records' => $records, 'committed_at' => $commitAt]);
                $this->state->put('transitions', $root, $commit, function () use ($authorityId): void {
                    // Final native/time revalidation after pending bytes are flushed, immediately before rename.
                    (new NativeAdmission($this->state))->records($authorityId, $this->now());
                });
                return (new NativeBindingReader($this->state))->read($p['instance_id'], $p['provider_binding']['id'], $p['operation'], $commitAt);
            });
        });
    }

    private function now(): int { return null === $this->clock ? time() : ($this->clock)(); }
}
