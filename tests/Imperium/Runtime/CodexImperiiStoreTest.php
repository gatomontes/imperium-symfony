<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\CodexImperiiStore;
use PHPUnit\Framework\TestCase;

final class CodexImperiiStoreTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-codex-test-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testCodexInitializesAdvancesAndReplaysExactly(): void
    {
        $store = $this->store();
        $first = $this->folium(1, 'qualification');
        $second = $this->folium(2, 'assembly');

        $initialized = $store->initialize('mission-1', 'qualification', [$first]);
        self::assertSame(1, $initialized['generation']);
        self::assertSame($initialized, $store->initialize('mission-1', 'qualification', [$first]));

        $advanced = $store->advance('mission-1', 'qualification', 'assembly', [$second]);
        self::assertSame(2, $advanced['generation']);
        self::assertSame('assembly', $advanced['current_checkpoint']);
        self::assertSame([$first, $second], $advanced['folia']);
        self::assertSame($advanced, $store->advance('mission-1', 'qualification', 'assembly', [$second]));
        self::assertSame($advanced, $store->read());
    }

    public function testAdvanceRejectsStaleCheckpoint(): void
    {
        $store = $this->store();
        $store->initialize('mission-1', 'qualification', [$this->folium(1, 'qualification')]);

        $this->expectExceptionMessage('CDI103_CODEX_CHECKPOINT_MISMATCH');
        $store->advance('mission-1', 'wrong', 'assembly', [$this->folium(2, 'assembly')]);
    }

    public function testAdvanceRejectsOmissionReorderingAndSubstitution(): void
    {
        $store = $this->store();
        $first = $this->folium(1, 'qualification');
        $second = $this->folium(2, 'assembly');
        $store->initialize('mission-1', 'qualification', [$first]);
        $store->advance('mission-1', 'qualification', 'assembly', [$second]);

        $this->expectExceptionMessage('CDI109_CODEX_CONFLICT');
        $store->advance('mission-1', 'qualification', 'assembly', [$this->folium(2, 'substituted')]);
    }

    public function testReplayRejectsAnOmittedFoliumFromOriginalBatch(): void
    {
        $store = $this->store();
        $store->initialize('mission-1', 'qualification', [$this->folium(1, 'qualification')]);
        $assembly = $this->folium(2, 'assembly');
        $binding = $this->folium(3, 'binding');
        $store->advance('mission-1', 'qualification', 'bound', [$assembly, $binding]);

        $this->expectExceptionMessage('CDI109_CODEX_CONFLICT');
        $store->advance('mission-1', 'qualification', 'bound', [$binding]);
    }

    public function testAdvanceRejectsNonContiguousSequence(): void
    {
        $store = $this->store();
        $store->initialize('mission-1', 'qualification', [$this->folium(1, 'qualification')]);

        $this->expectExceptionMessage('CDI104_FOLIA_INVALID');
        $store->advance('mission-1', 'qualification', 'assembly', [$this->folium(3, 'assembly')]);
    }

    private function store(): CodexImperiiStore
    {
        return new CodexImperiiStore($this->root, new AtomicTransition($this->root));
    }

    private function folium(int $sequence, string $name): array
    {
        return [
            'digest' => hash('sha256', $name),
            'folium_id' => 'folium-'.$name,
            'folium_schema' => 'imperium.'.$name.'/v1',
            'office' => 'Conscription',
            'relation' => 'proves-'.$name,
            'sequence' => $sequence,
            'storage_reference' => 'var/imperium/'.$name.'.json',
        ];
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
