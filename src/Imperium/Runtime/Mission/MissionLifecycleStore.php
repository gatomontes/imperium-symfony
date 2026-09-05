<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;

/** One atomic durable record holds current state and every consumed transition nonce. */
final readonly class MissionLifecycleStore
{
    private AtomicTransition $atomic;
    private string $directory;

    public function __construct(private string $root)
    {
        $this->atomic = new AtomicTransition($root);
        $this->directory = $root.'/var/imperium/runtime/canonical-mission/lifecycles';
    }

    public function consume(MissionCapability $capability, AuthenticatedMissionAuthorization $authorization, \DateTimeImmutable $at): array
    {
        $missionId = $authorization->mission->id();
        return $this->atomic->run('canonical-mission-lifecycle:'.hash('sha256', $missionId), function () use ($capability, $authorization, $at, $missionId): array {
            $path = $this->path($missionId);
            $record = is_file($path) ? $this->read($path) : $this->initial($authorization);
            $nonce = $capability->get('nonce');
            if (isset($record['consumed_nonces'][$nonce])) { throw new \RuntimeException('MIS420_CAPABILITY_CONSUMED'); }
            if ($record['state'] !== $capability->get('required_state')) { throw new \RuntimeException('MIS421_MISSION_REQUIRED_STATE_MISMATCH'); }
            if ($record['authorization_id'] !== $authorization->authorizationId
                || $record['authorization_digest'] !== $authorization->authorizationDigest
                || $record['dossier_digest'] !== $authorization->dossierDigest
                || $record['mission_digest'] !== $authorization->mission->digest()) {
                throw new \RuntimeException('MIS422_MISSION_LIFECYCLE_BINDING_MISMATCH');
            }
            $consumption = [
                'capability_id' => $capability->get('capability_id'),
                'nonce' => $nonce,
                'action' => $capability->get('action'),
                'actor' => $capability->get('actor'),
                'target' => $capability->get('target'),
                'issuer' => $capability->get('issuer'),
                'required_state' => $capability->get('required_state'),
                'resulting_state' => $capability->get('resulting_state'),
                'consumed_at' => $at->format(DATE_ATOM),
            ];
            $record['state'] = $capability->get('resulting_state');
            $record['consumed_nonces'][$nonce] = $capability->get('capability_id');
            $record['transition_history'][] = $consumption;
            $record['updated_at'] = $at->format(DATE_ATOM);
            $this->write($path, $record);
            return $this->read($path);
        });
    }

    public function readMission(string $missionId): array
    {
        return $this->read($this->path($missionId));
    }

    private function initial(AuthenticatedMissionAuthorization $authorization): array
    {
        return [
            'schema' => 'imperium.canonical-mission-lifecycle/v1',
            'mission_id' => $authorization->mission->id(),
            'authorization_id' => $authorization->authorizationId,
            'authorization_digest' => $authorization->authorizationDigest,
            'dossier_id' => $authorization->dossierId,
            'dossier_digest' => $authorization->dossierDigest,
            'mission_digest' => $authorization->mission->digest(),
            'state' => 'AUTHORIZED',
            'consumed_nonces' => [],
            'transition_history' => [],
            'updated_at' => $authorization->approvedAt,
            'sealed' => true,
        ];
    }

    private function path(string $missionId): string
    {
        if (1 !== preg_match('/^[a-z0-9][a-z0-9-]{7,120}$/', $missionId)) {
            throw new \RuntimeException('MIS423_MISSION_LIFECYCLE_ID_INVALID');
        }
        return $this->directory.'/'.$missionId.'.json';
    }

    private function read(string $path): array
    {
        if (!is_file($path)) { throw new \RuntimeException('MIS424_MISSION_LIFECYCLE_ABSENT'); }
        try { $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { throw new \RuntimeException('MIS425_MISSION_LIFECYCLE_TAMPERED'); }
        $digest = $record['record_digest'] ?? null; unset($record['record_digest']);
        if (!is_string($digest) || !hash_equals($digest, hash('sha256', CanonicalJson::encode($record)))) {
            throw new \RuntimeException('MIS425_MISSION_LIFECYCLE_TAMPERED');
        }
        $record['record_digest'] = $digest;
        return $record;
    }

    private function write(string $path, array $record): void
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0770, true) && !is_dir(dirname($path))) {
            throw new \RuntimeException('MIS426_MISSION_LIFECYCLE_COMMIT_FAILED');
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('MIS426_MISSION_LIFECYCLE_COMMIT_FAILED');
        }
    }
}
