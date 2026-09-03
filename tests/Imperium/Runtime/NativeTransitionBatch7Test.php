<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{NativeState, NativeConsumer, NativeReconstructor, NativeBindingReader, TransitionContract};
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3Contract as V3;

require_once __DIR__.'/NativeTransitionBatch4Test.php';

/** Terminal evidence against the original four material refusal findings. */
final class NativeTransitionBatch7Test extends NativeTransitionBatch4Test
{
    public function testNativeSuccessHasSelectedAdmissionAndAnActuallyConsumedBindingInterpretation(): void
    {
        [$id, $at, $p, $successor] = $this->readyTransition(true);
        self::assertNotNull($p['root_act']['act']['execution_basis']);
        $outcome = (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        $commit = $this->state->get('transitions', $outcome['root']);
        self::assertSame(TransitionContract::WRITE_SET, array_keys($commit['records']));
        self::assertSame(V3::SCHEMA, $commit['records']['v3_admission']['schema']);
        self::assertSame('ADMITTED_PRE_EFFECT', $commit['records']['v3_admission']['status']);
        self::assertSame(NativeState::ref($successor['successor'], 'successor_id'), $commit['records']['v3_admission']['completed_successor']);
        self::assertSame('BOUND_ACTIVE_FOR_EXACT_OPERATION', $outcome['effective_status']);
        $proof = (new NativeReconstructor($this->state))->reconstruct('imperium-test', 'provider-binding', 'email.send', $at);
        self::assertSame('COMMITTED', $proof['classification']);
        self::assertSame($outcome['receipt'], $proof['receipt']);
        self::assertFalse($proof['execution_authority']); self::assertFalse($proof['retry_authorized']);
        foreach (['credential_resolution_permitted', 'provider_invocation_permitted', 'external_io_permitted', 'effect_start_permitted'] as $field) {
            self::assertFalse($commit['records']['v3_admission'][$field]);
        }
        self::assertSame('BOUND_INACTIVE', $this->state->json(NativeState::SOURCES['binding'].'/provider-binding.json')['status']);
        self::assertSame('NOT_IMPLEMENTED', V3::STATUS);
    }

    public function testCopiedPhysicalRootAndChangedPublicAnchorCannotReconstructAuthority(): void
    {
        [$id, $at] = $this->readyTransition();
        (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $files[substr($file->getPathname(), strlen($this->root) + 1)] = file_get_contents($file->getPathname());
        }
        $copy = $this->root.'/copied-instance'; mkdir($copy);
        foreach ($files as $relative => $bytes) {
            $target = $copy.'/'.$relative;
            if (!is_dir(dirname($target))) { mkdir(dirname($target), 0770, true); }
            file_put_contents($target, $bytes);
        }
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', (new NativeReconstructor(new NativeState($copy)))->reconstruct('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
        $this->anchor['public_key'] = str_repeat('0', 64);
        $this->write(NativeState::TRUST.'/identity.json', $this->anchor);
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', (new NativeReconstructor($this->state))->reconstruct('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
    }

    public function testMissingNativeAuthorityCannotLeaveAnActiveReaderProjection(): void
    {
        [$id, $at] = $this->readyTransition();
        (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        unlink($this->root.'/'.NativeState::DIRECTORY.'/authorities/'.$id.'/commit.json');
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', (new NativeReconstructor($this->state))->reconstruct('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
        $this->fails('UNKNOWN_REPLAY_PROHIBITED', fn () => (new NativeBindingReader($this->state))->read('imperium-test', 'provider-binding', 'email.send', $at));
    }

    public function testTerminalDocumentsNameTheCleanBaseAndKeepTheHistoricalRefusal(): void
    {
        $root = dirname(__DIR__, 3);
        foreach (['docs/executable-atomic-transition-native-integration-remediation-terminal-audit-v1.md',
            'docs/handoffs/executable-atomic-transition-native-integration-remediation-terminal-complete.md',
            'docs/delegate-mission-flow.md', 'todo/blackquill-todos.md'] as $path) {
            $doc = file_get_contents($root.'/'.$path);
            self::assertStringContainsString('NATIVE_INTEGRATION_TERMINAL_AUDIT_ACCEPTED_BOUNDED_PRE_EFFECT', $doc);
            foreach (['BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED'] as $marker) { self::assertStringContainsString($marker, $doc); }
        }
        $audit = file_get_contents($root.'/docs/executable-atomic-transition-native-integration-remediation-terminal-audit-v1.md');
        self::assertStringContainsString('88ed24bed037101903356e519f34eb89475844a3', $audit);
        self::assertStringContainsString('9f335e3b00513f842539d82ea0d7955350612115', $audit);
        self::assertStringContainsString('Batch 6A', $audit);
        self::assertStringContainsString('EXECUTABLE_ATOMIC_TRANSITION_TERMINAL_AUDIT_REFUSED_NATIVE_INTEGRATION_ABSENT', $audit);
        $inventory = file_get_contents($root.'/docs/executable-atomic-transition-native-integration-remediation-inventory-v2.md');
        preg_match_all('/^\| N\d{2} \| (EXISTS_CANONICALLY|EXISTS_FRAGMENTED|ABSENT|DEFERRED_BOUNDARY) \|/m', $inventory, $matches);
        self::assertCount(30, $matches[0]);
    }
}
