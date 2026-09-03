<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{NativeBindingReader, NativeConsumer, NativeState};

require_once __DIR__.'/NativeTransitionBatch4Test.php';

class CanonicalConsumerCorrectionBatch1Test extends NativeTransitionBatch4Test
{
    public function testInterpretationSeparatesCurrentHistoricalInactiveAndUnrelatedWithoutWrites(): void
    {
        [$id, $at] = $this->readyTransition();
        $reader = new NativeBindingReader($this->state);
        $descriptor = $this->state->json(NativeState::SOURCES['binding'].'/provider-binding.json');
        self::assertSame('BOUND_INACTIVE', $reader->interpret('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
        self::assertSame('UNRELATED_OPERATION', $reader->interpret('imperium-test', 'provider-binding', 'other.operation', $at)['classification']);
        $reader->assertLegacy($descriptor);
        (new NativeConsumer($this->state, static fn () => $at))->execute($id);
        $before = $this->files();
        $current = $reader->interpret('imperium-test', 'provider-binding', 'email.send', $at);
        self::assertSame('COMMITTED_CURRENT', $current['classification']);
        self::assertFalse($current['provider_effect_permitted']);
        self::assertFalse($current['retry_authorized']);
        self::assertSame('COMMITTED_NOT_CURRENT', $reader->interpret('imperium-test', 'provider-binding', 'email.send', $at + 601)['classification']);
        $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => $reader->assertLegacy($descriptor));
        self::assertSame($before, $this->files());
        self::assertSame($descriptor, $this->state->json(NativeState::SOURCES['binding'].'/provider-binding.json'));
    }

    public function testPendingAndCorruptionNeverFallBackToInactive(): void
    {
        [$id, $at] = $this->readyTransition();
        $root = NativeBindingReader::root('imperium-test', 'provider-binding', 'email.send');
        $path = $this->root.'/'.NativeState::DIRECTORY.'/journals/'.$root;
        mkdir($path, 0770, true);
        file_put_contents($path.'/commit.pending', '{}');
        $reader = new NativeBindingReader($this->state);
        self::assertSame('INCOMPLETE', $reader->interpret('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
        $descriptor = $this->state->json(NativeState::SOURCES['binding'].'/provider-binding.json');
        $this->fails('CCI_NATIVE_STATE_PRECLUDES_LEGACY', fn () => $reader->assertLegacy($descriptor));
        $descriptor['status'] = 'BOUND_ACTIVE';
        $this->write(NativeState::SOURCES['binding'].'/provider-binding.json', $descriptor);
        self::assertSame('CORRUPT', $reader->interpret('imperium-test', 'provider-binding', 'email.send', $at)['classification']);
    }

    protected function files(): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $files[$file->getPathname()] = hash_file('sha256', $file->getPathname());
        }
        ksort($files);
        return $files;
    }
}
