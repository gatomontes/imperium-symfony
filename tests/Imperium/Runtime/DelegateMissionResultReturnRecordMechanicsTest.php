<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\DelegateMissionResultReturnRecordMechanics;
use PHPUnit\Framework\TestCase;

final class DelegateMissionResultReturnRecordMechanicsTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-result-return-mechanics-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testDispositionAndReturnAuthorizationReplayExactly(): void
    {
        $mechanics = new DelegateMissionResultReturnRecordMechanics($this->root);
        $disposition = $mechanics->saveDisposition('disposition-123', ['status' => 'DISPOSED']);
        $authorization = $mechanics->saveReturnAuthorization('authorization-123', ['status' => 'AUTHORIZED']);

        self::assertSame($disposition, $mechanics->saveDisposition('disposition-123', ['status' => 'DISPOSED']));
        self::assertSame($authorization, $mechanics->saveReturnAuthorization('authorization-123', ['status' => 'AUTHORIZED']));
        self::assertTrue($mechanics->isIntact($disposition));
        self::assertTrue($mechanics->isIntact($authorization));
    }

    public function testDispositionConflictKeepsEstablishedVocabulary(): void
    {
        $mechanics = new DelegateMissionResultReturnRecordMechanics($this->root);
        $mechanics->saveDisposition('disposition-123', ['status' => 'FIRST']);

        $this->expectExceptionMessage('C309_DELEGATE_RESULT_DISPOSITION_CONFLICT');
        $mechanics->saveDisposition('disposition-123', ['status' => 'CHANGED']);
    }

    public function testReturnAuthorizationConflictKeepsEstablishedVocabulary(): void
    {
        $mechanics = new DelegateMissionResultReturnRecordMechanics($this->root);
        $mechanics->saveReturnAuthorization('authorization-123', ['status' => 'FIRST']);

        $this->expectExceptionMessage('C319_DELEGATE_RETURN_AUTHORIZATION_CONFLICT');
        $mechanics->saveReturnAuthorization('authorization-123', ['status' => 'CHANGED']);
    }

    public function testSourceRejectsDigestSubstitution(): void
    {
        $directory = $this->root.'/var/imperium/source';
        mkdir($directory, 0770, true);
        $record = ['source_id' => 'source-123'];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        file_put_contents($directory.'/source-123.json', json_encode($record, JSON_THROW_ON_ERROR));

        $this->expectExceptionMessage('CHAIN_INVALID');
        (new DelegateMissionResultReturnRecordMechanics($this->root))->source(
            $directory,
            ['id' => 'source-123', 'digest' => str_repeat('f', 64)],
            'ABSENT',
            'CHAIN_INVALID',
        );
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
