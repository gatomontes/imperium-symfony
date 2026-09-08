<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Authority;

use App\Bootstrap\CanonicalJson;

/** Public-boundary validation. Errors never interpolate supplied/private values. */
final class AuthorityInput
{
    public static function require(bool $condition, string $code = 'CAI001_INPUT_INVALID'): void
    {
        if (!$condition) { throw new \RuntimeException($code); }
    }

    public static function keys(mixed $value, array $keys): void
    {
        self::require(is_array($value));
        $actual = array_keys($value); sort($actual); sort($keys);
        self::require($actual === $keys);
    }

    public static function id(mixed $value): void
    {
        self::require(is_string($value) && 1 === preg_match('/^[a-z0-9][a-z0-9._:@-]{0,179}$/D', $value));
    }

    public static function hex(mixed $value): void
    {
        self::require(is_string($value) && 1 === preg_match('/^[a-f0-9]{64}$/D', $value));
    }

    public static function digest(mixed $value): string { return hash('sha256', CanonicalJson::encode($value)); }

    public static function intact(array $record): void
    {
        $digest = $record['record_digest'] ?? null; unset($record['record_digest']);
        self::hex($digest);
        self::require(hash_equals($digest, self::digest($record)), 'CAI002_DIGEST_MISMATCH');
    }

    public static function seal(array $record): array { return [...$record, 'record_digest' => self::digest($record)]; }

    /** Bounded local file only. Custodian controls parent directories; no network wrappers. */
    public static function read(string $path): array
    {
        self::require(!str_contains($path, '://') && !str_starts_with($path, '\\\\') && !str_starts_with($path, '//'), 'CAI003_FILE_UNAVAILABLE');
        self::require(is_file($path) && !is_link($path), 'CAI003_FILE_UNAVAILABLE');
        $stream = @fopen($path, 'rb');
        self::require(is_resource($stream), 'CAI003_FILE_UNAVAILABLE');
        try { $bytes = stream_get_contents($stream, 1048577); } finally { fclose($stream); }
        self::require(is_string($bytes) && strlen($bytes) <= 1048576, 'CAI004_INPUT_LIMIT');
        try { $value = json_decode($bytes, true, 48, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { throw new \RuntimeException('CAI005_JSON_INVALID'); }
        // JSON is already syntax-checked. Track object keys before PHP can collapse duplicates.
        preg_match_all('/"(?:[^"\\\\]|\\\\.)*"|[{}\[\]:,]/s', $bytes, $matches);
        $stack = [];
        foreach ($matches[0] as $index => $token) {
            if ($token === '{' || $token === '[') { $stack[] = []; }
            elseif ($token === '}' || $token === ']') { array_pop($stack); }
            elseif (str_starts_with($token, '"') && ($matches[0][$index + 1] ?? null) === ':') {
                $key = json_decode($token, true, 2, JSON_THROW_ON_ERROR);
                $depth = count($stack) - 1;
                self::require(!isset($stack[$depth][$key]), 'CAI006_DUPLICATE_JSON_KEY');
                $stack[$depth][$key] = true;
            }
        }
        self::require(is_array($value) && !array_is_list($value), 'CAI005_JSON_INVALID');
        return $value;
    }

    public static function flags(): array { return ['live_ready' => false, 'activation' => false, 'execution_authority' => false]; }
}
