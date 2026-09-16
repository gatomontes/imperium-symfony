<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\FormationJournal as J;

/** Only public observations are exported. Never serializes fixture objects or secrets. */
final class NativeAssignmentProof
{
    public static function refusal(callable $operation): string
    {
        try { $operation(); } catch (\RuntimeException $e) { return $e->getMessage(); }
        throw new \LogicException('Expected a reached refusal');
    }
    public static function appoint(FreshEstablishmentFixture $f, FreshDesignationFixture $d, int $generation): array
    {
        $terms = ['candidate' => $d->m->candidate, 'scope' => $f->store->citadel, 'seat' => $d->seat, 'generation' => $generation];
        return $d->seat === 'courtyard.courtthane'
            ? $f->personnel->appointCourtthane($d->m->candidate, $f->sign('APPOINT_COURTTHANE', $terms))
            : $f->personnel->appointLocksmith($d->m->candidate, $f->sign('APPOINT_FORMATION_LOCKSMITH', $terms));
    }
    public static function export(string $name, array $observation): void
    {
        $path = getenv('PPC9_PUBLIC_EVIDENCE');
        if (!is_string($path) || $path === '') { return; }
        if (!is_dir($path)) { mkdir($path, 0700, true); }
        file_put_contents($path.'/'.$name.'.json', json_encode(['schema' => 'imperium.ppc9-public-observation/v1',
            'name' => $name, 'observed_utc' => gmdate('c'), 'external_ports' => [
                'account' => 'SYNTHETIC', 'access' => 'SYNTHETIC', 'base_model' => 'SYNTHETIC',
                'tokenizer' => 'SYNTHETIC', 'tariff' => 'SYNTHETIC', 'cognition' => 'SYNTHETIC'],
            ...$observation], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
    public static function exportCustody(FreshEstablishmentFixture $f, string $name): void
    {
        $path = getenv('PPC9_PUBLIC_EVIDENCE');
        if (!is_string($path) || $path === '') { return; }
        $head = $f->head();
        if ($head['generation'] > 512) { throw new \LogicException('Public history export bound'); }
        $directory = $path.'/'.$name.'-originals';
        if (!is_dir($directory)) { mkdir($directory, 0700, true); }
        $index = ['head' => $head, 'frames' => [], 'native' => []];
        for ($i = 1; $i <= $head['generation']; ++$i) {
            $filename = sprintf('%012d.json', $i);
            $bytes = (string) file_get_contents($f->root.'/var/imperium/citadel/formation/'.$filename);
            file_put_contents($directory.'/frame-'.$filename, $bytes);
            $index['frames'][] = ['file' => 'frame-'.$filename, 'bytes' => strlen($bytes), 'sha256' => hash('sha256', $bytes)];
        }
        foreach ($f->completion['native_package']['files'] as $relative => $digest) {
            $bytes = (string) file_get_contents($f->root.'/'.$relative); $file = 'native-'.hash('sha256', $relative).'.json';
            file_put_contents($directory.'/'.$file, $bytes);
            $index['native'][] = ['file' => $file, 'original_path' => $relative, 'record_digest' => $digest, 'bytes' => strlen($bytes), 'sha256' => hash('sha256', $bytes)];
        }
        file_put_contents($directory.'/index.json', json_encode($index, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
    /** Retain newly signed original findings under an actual, independently delegated current actor. */
    public static function record(FreshEstablishmentFixture $f, string $role, array $payload): string
    {
        $pair = sodium_crypto_sign_keypair(); $secret = sodium_crypto_sign_secretkey($pair);
        try {
            $terms = ['role' => $role, 'public_key' => base64_encode(sodium_crypto_sign_publickey($pair)),
                'scope' => $f->store->citadel, 'expires_at' => $f->clock->at + 900, 'actor' => $f->personnel->authoritySource($role)];
            $payload['delegation'] = $f->personnel->delegate($terms, $f->sign('DELEGATE_PERSONNEL_EVIDENCE', $terms));
            $payload['expires_at'] = $f->clock->at + 900;
            $envelope = ['payload' => $payload, 'signature' => base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload), $secret))];
            return $f->personnel->record($envelope);
        } finally { sodium_memzero($secret); }
    }
}
