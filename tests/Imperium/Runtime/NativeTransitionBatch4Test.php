<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{NativeState, NativeSuccessor, NativeAuthority, NativeConsumer, NativeBindingReader, TransitionStore, TransitionAuthority, TransitionContract};

require_once __DIR__.'/NativeTransitionBatch3Test.php';

class NativeTransitionBatch4Test extends NativeTransitionBatch3Test
{
    public function testAtomicCommitIsConsumedByActualBindingReaderAndLegacyCannotFollow(): void
    {
        [$authorityId, $at] = $this->readyTransition(true);
        $result = (new NativeConsumer($this->state, static fn () => $at))->execute($authorityId);
        self::assertSame('BOUND_ACTIVE_FOR_EXACT_OPERATION', $result['effective_status']);
        self::assertSame('COMMITTED_PRE_EFFECT', $result['receipt']['outcome']);
        $reader = new NativeBindingReader($this->state);
        self::assertSame($result, $reader->read('imperium-test', 'provider-binding', 'email.send', $at));
        self::assertSame('BOUND_INACTIVE', $reader->read('imperium-test', 'provider-binding', 'other.operation', $at)['effective_status']);
        $this->fails('NIR_ALREADY_COMMITTED_READ_ONLY_REPLAY', fn () => (new NativeConsumer($this->state, static fn () => $at))->execute($authorityId));
        $legacy = new TransitionStore($this->root.'/var/imperium/runtime/legacy-provider-transitions/old-store');
        $this->fails('EAT_NATIVE_PROTOCOL_RETIRED_NO_RETRY', fn () => (new TransitionAuthority($legacy, str_repeat('a', 64)))->issue());
        $commit = $this->state->get('transitions', $result['root']);
        self::assertSame(TransitionContract::WRITE_SET, array_keys($commit['records']));
        self::assertSame('BOUND_INACTIVE', $this->state->json(NativeState::SOURCES['binding'].'/provider-binding.json')['status']);
    }

    public function testExistingLegacyOutcomeRefusesBeforeNativeJournal(): void
    {
        [$authorityId, $at] = $this->readyTransition(true);
        $legacy = new TransitionStore($this->root.'/var/imperium/runtime/legacy-provider-transitions/old-store');
        $legacy->locked(fn () => $legacy->put('journal', ['state' => 'UNKNOWN_OLD_ATTEMPT']));
        $this->fails('NIR_LEGACY_STATE_NOT_EMPTY', fn () => (new NativeConsumer($this->state, static fn () => $at))->execute($authorityId));
        self::assertSame([], $this->state->ids('journals'));
        self::assertSame([], $this->state->ids('transitions'));
    }

    public function testExpiryAfterPendingFlushPreventsPublicationAndProhibitsReplay(): void
    {
        [$authorityId, $at] = $this->readyTransition();
        $clock = [$at, $at, $at + 601];
        $this->fails('NIR_ROOT_INELIGIBLE', fn () => (new NativeConsumer($this->state, static function () use (&$clock): int { return array_shift($clock); }))->execute($authorityId));
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => (new NativeConsumer($this->state, static fn () => $at))->execute($authorityId));
        self::assertFileDoesNotExist($this->root.'/'.NativeState::DIRECTORY.'/transitions/'.NativeBindingReader::root('imperium-test', 'provider-binding', 'email.send').'/commit.json');
    }

    protected function readyTransition(bool $legacy = false): array
    {
        [$p, $a, $at] = $this->nativeInputs();
        $s = (new NativeSuccessor($this->state, static fn () => $at))->create($p['principal_version_id'], NativeState::ref($a, 'principal_activation_id'));
        $authority = (new NativeAuthority($this->state, static fn () => $at))->issue($p['principal_version_id'], $s['successor']['successor_id']);
        $directories = [];
        if ($legacy) {
            $directories[] = 'var/imperium/runtime/legacy-provider-transitions/old-store';
            mkdir($this->root.'/'.$directories[0], 0770, true);
        }
        $this->write(NativeState::TRUST.'/migration.json', ['schema' => 'imperium.operator-root.transition-migration-inventory/v1',
            'storage' => $this->state->identity(), 'instance' => 'imperium-test', 'inventory_complete' => true, 'legacy_directories' => $directories]);
        return [$authority['authority']['authority_id'], $at, $p, $s];
    }
}
