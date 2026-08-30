<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderEffectPrincipalBindingActivationPreparationBatch0Test extends TestCase
{
    public function testPreparationRequiresSeparateOrderedAuthorities(): void
    {
        $inventory = $this->document(
            'docs/provider-effect-principal-binding-activation-preparation-inventory.md',
        );

        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_SEPARATE_ORDERED_ACTIVATION_AUTHORITIES_REQUIRED',
            'not one authority',
            'separate operation-scoped authority',
            'Principal activation completes before binding activation begins',
            'must not nest',
            'mutually exclusive immutable winners',
        ] as $finding) {
            self::assertNotFalse(stripos($inventory, $finding), $finding);
        }
    }

    public function testPreparationClassifiesCanonicalFragmentedAbsentAndDeferredBoundaries(): void
    {
        $inventory = $this->document(
            'docs/provider-effect-principal-binding-activation-preparation-inventory.md',
        );

        foreach ([
            'EXISTS_CANONICALLY',
            'EXISTS_FRAGMENTED',
            'ABSENT',
            'DEFERRED_BOUNDARY',
            'ATTESTED_INERT',
            'BOUND_INACTIVE',
            'Live-capable binding sufficiency',
            'Binding activation authority and producer',
            'Live-call contract',
            'Live-consumer adoption',
        ] as $finding) {
            self::assertNotFalse(stripos($inventory, $finding), $finding);
        }
    }

    public function testPreparationPreservesAtomicCrashReplayAndLifecycleRequirements(): void
    {
        $inventory = $this->document(
            'docs/provider-effect-principal-binding-activation-preparation-inventory.md',
        );

        foreach ([
            'consume-to-commit',
            'Competing principal callers',
            'Principal expiry or revocation',
            'Competing binding callers or revocation',
            'Corrupt reconstruction',
            'Process restart',
            'UNKNOWN_REPLAY_PROHIBITED',
            'read only',
        ] as $finding) {
            self::assertNotFalse(stripos($inventory, $finding), $finding);
        }
    }

    public function testPreparationExcludesSecretsCapabilitiesAndRuntimeEffects(): void
    {
        $inventory = $this->document(
            'docs/provider-effect-principal-binding-activation-preparation-inventory.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-effect-principal-binding-activation-preparation-batch-0-complete.md',
        );

        foreach ([
            'credential bytes',
            'credential references',
            'environment-variable names',
            'process-local capability identity',
            'No runtime behavior changed',
            'No principal or binding was activated',
            'no provider was invoked',
            'Iron Gate and Lazaretto remained closed',
        ] as $boundary) {
            self::assertNotFalse(stripos($inventory.' '.$handoff, $boundary), $boundary);
        }
    }

    public function testBatchOneGateIsPrincipalOnlyAndProviderClosed(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-effect-principal-binding-activation-preparation-batch-0-complete.md',
        );

        foreach ([
            'Only Batch 1 may next be considered',
            'atomic production transition',
            'existing principal-activation decision',
            'disposable caller-supplied local fixtures',
            'No principal or binding was activated',
            'no live-call contract was defined',
            'no external I/O',
        ] as $gate) {
            self::assertNotFalse(stripos($handoff, $gate), $gate);
        }
    }

    private function document(string $path): string
    {
        return (string) preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(dirname(__DIR__, 3).'/'.$path),
        );
    }
}
