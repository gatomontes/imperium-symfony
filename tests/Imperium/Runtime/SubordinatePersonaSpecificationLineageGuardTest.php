<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\SubordinatePersonaSpecificationLineageGuard;
use PHPUnit\Framework\TestCase;

final class SubordinatePersonaSpecificationLineageGuardTest extends TestCase
{
    public function testCurrentVersionPassesAndSupersededVersionIsRefused(): void
    {
        $directory = sys_get_temp_dir().'/imperium-spec-lineage-'.bin2hex(random_bytes(6));
        mkdir($directory, 0770, true);
        $v1 = ['specification_id'=>'subordinate-persona-specification-'.str_repeat('a',20),'specification_version'=>1,'supersedes'=>null];
        $this->write($directory, $v1);
        $guard = new SubordinatePersonaSpecificationLineageGuard($directory);
        $guard->assertCurrent($v1);
        $v2 = ['specification_id'=>'subordinate-persona-specification-'.str_repeat('b',20),'specification_version'=>2,'supersedes'=>['specification_id'=>$v1['specification_id'],'specification_digest'=>$v1['record_digest'],'specification_version'=>1]];
        $this->write($directory, $v2);
        $guard->assertCurrent($v2);
        try {
            $guard->assertCurrent($v1);
            self::fail('Superseded specification was accepted.');
        } catch (\RuntimeException $exception) {
            self::assertSame('F138_SUBORDINATE_SPECIFICATION_SUPERSEDED', $exception->getMessage());
        } finally {
            foreach (glob($directory.'/*.json') ?: [] as $path) unlink($path);
            rmdir($directory);
        }
    }

    private function write(string $directory, array &$record): void
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        file_put_contents($directory.'/'.$record['specification_id'].'.json', json_encode($record, JSON_THROW_ON_ERROR));
    }
}
