<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use PHPUnit\Framework\TestCase;

final class RecordReferenceValidatorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-reference-validator-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testReadsAndResolvesExactDigestBoundReference(): void
    {
        $record = $this->write('var/imperium/evidence/source-123.json', ['source_id' => 'source-123']);
        $validator = new RecordReferenceValidator($this->root);

        self::assertSame($record, $validator->read($this->root.'/var/imperium/evidence/source-123.json', 'ABSENT'));
        self::assertSame($record, $validator->resolve(
            $this->root.'/var/imperium/evidence',
            ['id' => 'source-123', 'digest' => $record['record_digest']],
            'ABSENT',
            'MISMATCH',
        ));
        self::assertSame($record, $validator->requireIntact($record, 'TAMPERED'));
    }

    public function testDigestSubstitutionFailsWithCallerVocabulary(): void
    {
        $this->write('var/imperium/evidence/source-123.json', ['source_id' => 'source-123']);

        $this->expectExceptionMessage('CALLER_CHAIN_INVALID');
        (new RecordReferenceValidator($this->root))->resolve(
            $this->root.'/var/imperium/evidence',
            ['id' => 'source-123', 'digest' => str_repeat('f', 64)],
            'ABSENT',
            'CALLER_CHAIN_INVALID',
        );
    }

    public function testTamperFailsWithCallerVocabulary(): void
    {
        $record = $this->write('var/imperium/evidence/source-123.json', ['source_id' => 'source-123']);
        $record['source_id'] = 'substituted';

        $this->expectExceptionMessage('CALLER_TAMPER_ERROR');
        (new RecordReferenceValidator($this->root))->requireIntact($record, 'CALLER_TAMPER_ERROR');
    }

    public function testIntactSupportsExplicitDigestPrefixWithoutWeakeningDefault(): void
    {
        $record = ['source_id' => 'source-123'];
        $record['record_digest'] = 'sha256:'.hash('sha256', CanonicalJson::encode($record));
        $validator = new RecordReferenceValidator($this->root);

        self::assertTrue($validator->isIntact($record, 'sha256:'));
        self::assertFalse($validator->isIntact($record));
    }

    public function testResolvedIdentitySubstitutionFailsWithCallerVocabulary(): void
    {
        $record = $this->write('var/imperium/evidence/source-123.json', [
            'source_id' => 'substituted-source',
        ]);

        $this->expectExceptionMessage('CALLER_REFERENCE_INVALID');
        (new RecordReferenceValidator($this->root))->resolve(
            $this->root.'/var/imperium/evidence',
            ['id' => 'source-123', 'digest' => $record['record_digest']],
            'ABSENT',
            'CALLER_REFERENCE_INVALID',
            'source_id',
        );
    }

    public function testResolvedRecordTamperFailsWithCallerVocabulary(): void
    {
        $record = $this->write('var/imperium/evidence/source-123.json', ['source_id' => 'source-123']);
        $tampered = $record;
        $tampered['source_id'] = 'tampered';
        file_put_contents(
            $this->root.'/var/imperium/evidence/source-123.json',
            json_encode($tampered, JSON_THROW_ON_ERROR),
        );

        $this->expectExceptionMessage('CALLER_REFERENCE_INVALID');
        (new RecordReferenceValidator($this->root))->resolve(
            $this->root.'/var/imperium/evidence',
            ['id' => 'source-123', 'digest' => $record['record_digest']],
            'ABSENT',
            'CALLER_REFERENCE_INVALID',
            'source_id',
        );
    }

    public function testPathEscapeIsRejected(): void
    {
        $this->expectExceptionMessage('PST131_RECORD_PATH_INVALID');
        (new RecordReferenceValidator($this->root))->read($this->root.'/var/imperium/../secret.json', 'ABSENT');
    }

    private function write(string $path, array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $absolute = $this->root.'/'.$path;
        if (!is_dir(dirname($absolute))) {
            mkdir(dirname($absolute), 0770, true);
        }
        file_put_contents($absolute, json_encode($record, JSON_THROW_ON_ERROR));

        return $record;
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
