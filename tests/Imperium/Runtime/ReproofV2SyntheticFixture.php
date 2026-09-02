<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\ReproofV2\Contract;
use App\ReproofV2\Records;
use App\ReproofV2\SourceBundle;
use App\ReproofV2\Runner;

/** In-memory synthetic provenance only. Never invokes the mission CLI or git. */
final class ReproofV2SyntheticFixture
{
    public static function source(): array
    {
        $files = [];
        $directory = [];
        foreach (SourceBundle::PATHS as $path) {
            $bytes = file_get_contents(dirname(__DIR__, 3).'/'.$path);
            $oid = self::oid('blob', $bytes);
            $files[$path] = ['blob' => $oid, 'bytes' => base64_encode($bytes)];
            $parts = explode('/', $path);
            $filename = array_pop($parts);
            $node =& $directory;
            foreach ($parts as $part) { $node[$part] ??= []; $node =& $node[$part]; }
            $node[$filename] = $oid;
            unset($node);
        }
        $trees = [];
        $tree = self::tree($directory, $trees);
        $commit = "tree ".$tree."\nauthor Synthetic Test <synthetic@example.invalid> 0 +0000\ncommitter Synthetic Test <synthetic@example.invalid> 0 +0000\n\nSYNTHETIC_NOT_OPERATIONAL\n";
        ksort($files, SORT_STRING);
        ksort($trees, SORT_STRING);
        return Records::seal(['schema' => Contract::SCHEMAS['source'], 'object_format' => 'sha1',
            'commit' => self::oid('commit', $commit), 'commit_bytes' => base64_encode($commit),
            'trees' => $trees, 'files' => $files, 'manifest_root' => Records::hash(SourceBundle::manifest($files))]);
    }

    public static function package(): array
    {
        $source = self::source();
        return (new Runner())->run('reproof-v2-synthetic-test', $source,
            Records::hash(['synthetic_authorization' => false]),
            hash('sha256', base64_decode($source['files']['src/ReproofV2/Runner.php']['bytes'])));
    }

    private static function tree(array $directory, array &$trees): string
    {
        uksort($directory, static fn ($a, $b) => strcmp($a.(is_array($directory[$a]) ? '/' : ''), $b.(is_array($directory[$b]) ? '/' : '')));
        $bytes = '';
        foreach ($directory as $name => $value) {
            $isTree = is_array($value);
            $oid = $isTree ? self::tree($value, $trees) : $value;
            $bytes .= ($isTree ? '40000' : '100644').' '.$name."\0".hex2bin($oid);
        }
        $oid = self::oid('tree', $bytes);
        $trees[$oid] = base64_encode($bytes);
        return $oid;
    }

    private static function oid(string $type, string $bytes): string
    {
        return hash('sha1', $type.' '.strlen($bytes)."\0".$bytes);
    }
}
