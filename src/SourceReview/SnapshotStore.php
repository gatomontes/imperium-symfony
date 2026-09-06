<?php

declare(strict_types=1);
namespace App\SourceReview;

use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

final readonly class SnapshotStore
{
    public const DIRECTORY = 'var/imperium/mission/source-review/proposals';
    public function __construct(private ImmutableRecordStore $records) {}
    public function prepare(string $inputFile): array
    {
        $input = json_decode(self::readText($inputFile, 262144), true, 64, JSON_THROW_ON_ERROR);
        Proposal::keys($input, ['files', 'expected_behavior_file', 'runtime', 'pricing']);
        $root = dirname(realpath($inputFile)); $files = [];
        if (!is_array($input['files']) || !array_is_list($input['files']) || count($input['files']) > 30) { throw new \RuntimeException('SR_FILE_LIMIT'); }
        foreach ($input['files'] as $path) {
            if (!is_string($path)) { throw new \RuntimeException('SR_PATH_INVALID'); } Proposal::path($path);
            $files[] = ['path' => $path, 'bytes_base64' => base64_encode(self::readText($root.'/'.$path, 131072))];
        }
        Proposal::path($input['expected_behavior_file']);
        $p = Proposal::build($files, self::readText($root.'/'.$input['expected_behavior_file'], 131072), $input['runtime'], $input['pricing']);
        return $this->records->put(self::DIRECTORY, $p['proposal_id'], $p);
    }
    public function get(string $id): array { return Proposal::validate($this->records->read(self::DIRECTORY, $id)); }
    public static function readText(string $path, int $maximum): string
    {
        // Check every component, including the supplied root, before opening. Retain one
        // bounded in-memory read; never reopen source at dispatch. Native custody is an OS premise.
        $full = str_replace('\\', '/', $path);
        if (str_contains($full, '://') || str_starts_with($full, '//') || str_contains(substr($full, 2), ':')) { throw new \RuntimeException('SR_LOCAL_FILE_REQUIRED'); }
        if (realpath($path) === false) { throw new \RuntimeException('SR_INPUT_UNREADABLE'); }
        for ($part = $full; $part !== dirname($part); $part = dirname($part)) {
            if (is_link($part) || (file_exists($part) && realpath($part) === false)) { throw new \RuntimeException('SR_LINK_REFUSED'); }
        }
        $before = lstat($path);
        if ($before === false || ($before['mode'] & 0170000) !== 0100000 || $before['nlink'] > 1) { throw new \RuntimeException('SR_REGULAR_FILE_REQUIRED'); }
        $h = @fopen($path, 'rb');
        if ($h === false) { throw new \RuntimeException('SR_INPUT_UNREADABLE'); }
        try {
            $stat = fstat($h);
            if (($stat['mode'] & 0170000) !== 0100000 || $stat['nlink'] > 1) { throw new \RuntimeException('SR_REGULAR_FILE_REQUIRED'); }
            if ($stat['ino'] !== $before['ino'] || $stat['dev'] !== $before['dev']) { throw new \RuntimeException('SR_INPUT_CHANGED_DURING_OPEN'); }
            $bytes = stream_get_contents($h, $maximum + 1);
            if (!is_string($bytes) || strlen($bytes) > $maximum) { throw new \RuntimeException('SR_INPUT_SIZE'); }
            return Proposal::text($bytes);
        } finally { fclose($h); }
    }
}
