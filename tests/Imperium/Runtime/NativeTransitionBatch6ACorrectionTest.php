<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{NativeBindingReader, NativeReconstructor, TransitionStore};

require_once __DIR__.'/NativeTransitionBatch4Test.php';

final class NativeTransitionBatch6ACorrectionTest extends NativeTransitionBatch4Test
{
    public function testOrphanRetirementCannotBePresentedAsUntouchedInactiveBinding(): void
    {
        [, $at] = $this->readyTransition(true);
        $root = NativeBindingReader::root('imperium-test', 'provider-binding', 'email.send');
        $legacy = new TransitionStore($this->root.'/var/imperium/runtime/legacy-provider-transitions/old-store');
        $legacy->locked(fn () => $legacy->put('retirement', ['root' => $root]));
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', (new NativeReconstructor($this->state))->reconstruct('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => (new NativeBindingReader($this->state))->read('imperium-test', 'provider-binding', 'email.send', $at));
    }
}
