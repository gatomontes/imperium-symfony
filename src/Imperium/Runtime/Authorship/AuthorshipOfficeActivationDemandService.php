<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Authorship;

use App\Bootstrap\CanonicalJson;

final readonly class AuthorshipOfficeActivationDemandService
{
    private string $demandDirectory;

    public function __construct(private string $projectDir)
    {
        $this->demandDirectory = $projectDir.'/var/imperium/mastermason/spawning-requests';
    }

    public function demand(string $office, string $commissionId): array
    {
        $configuration = match ($office) {
            'hagiography' => ['target' => 'hagiography.sanctographer', 'profile' => 'offices/hagiography/profile-sanctographer.md', 'class' => 'EVIDENCE_DERIVED_PERSONA_SECTIONS'],
            'studium' => ['target' => 'studium.chancellor', 'profile' => 'offices/studium/profile-chancellor.md', 'class' => 'PERSONA_GOVERNANCE_DOCTRINE_SECTIONS'],
            default => throw new \InvalidArgumentException('A10_OFFICE_INVALID: exact specialized authorship Office is required.'),
        };
        if (!preg_match('/^authorship-'.$office.'-[a-f0-9]{20}$/', $commissionId)) throw new \InvalidArgumentException('A11_COMMISSION_INVALID: exact '.$office.' authorship commission identity is required.');
        $commission = $this->read($this->projectDir.'/var/imperium/offices/'.$office.'/inbox/'.$commissionId.'.json', 'A12_COMMISSION_ABSENT');
        if (!$this->digestMatches($commission) || $commissionId !== ($commission['commission_id'] ?? null)
            || 'imperium.specialized-authorship-commission/v1' !== ($commission['schema'] ?? null)
            || $office !== ($commission['office'] ?? null) || $configuration['target'] !== ($commission['target_seat'] ?? null)
            || $configuration['class'] !== ($commission['authorship_class'] ?? null)
            || 'ISSUED_PENDING_RECIPIENT' !== ($commission['status'] ?? null) || true !== ($commission['authorship_authority'] ?? null)
            || null !== ($commission['recipient_acceptance'] ?? null) || true === ($commission['persona_selection_authority'] ?? null)
            || true === ($commission['persona_assembly_authority'] ?? null) || true === ($commission['spawning_authority'] ?? null)
            || true === ($commission['admission_authority'] ?? null) || true === ($commission['seat_binding_authority'] ?? null)
            || true === ($commission['execution_authority'] ?? null)) {
            throw new \RuntimeException('A13_COMMISSION_INVALID: exact unaccepted non-executing '.$office.' authorship commission is required.');
        }
        $requiredSeats = [[
            'seat' => $configuration['target'], 'profile' => $configuration['profile'], 'activation_policy' => 'resident',
            'status' => 'BLOCKED_PENDING_CANONICAL_STAFF_ARTIFACTS',
        ]];
        $demandId = $office.'-activation-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $commission['record_digest'], $requiredSeats])), 0, 20);
        return $this->persist($demandId, [
            'schema' => 'imperium.office-activation-demand/v1', 'demand_id' => $demandId,
            'requester' => $office.'.inbox-router', 'recipient' => 'mastermason', 'office' => $office,
            'commission_id' => $commissionId, 'commission_digest' => $commission['record_digest'],
            'instance_id' => $commission['instance_id'], 'production_case_id' => $commission['production_case_id'],
            'required_seats' => $requiredSeats,
            'missing_prerequisites' => ['canonical resident Persona artifact', 'versioned current/active resident Profile and lifecycle attestations', 'generic Officer substrate qualification by Conscription', 'atomic resident Seat binding'],
            'status' => 'CANONICAL_STAFF_ARTIFACTS_REQUIRED', 'authorship_authority' => true,
            'authorship_authority_exercisable' => false, 'mission_persona_selection_required' => false,
            'subordinate_staff_resolution_pending' => true, 'spawning_authority' => false, 'seat_binding_authority' => false,
            'recipient_acceptance' => false, 'execution_authority' => false,
        ]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(string $demandId, array $demand): array
    {
        if (!is_dir($this->demandDirectory) && !mkdir($this->demandDirectory, 0770, true) && !is_dir($this->demandDirectory)) throw new \RuntimeException('MasterMason spawning-request directory cannot be created.');
        $demand['record_digest'] = hash('sha256', CanonicalJson::encode($demand)); $path = $this->demandDirectory.'/'.$demandId.'.json';
        if (is_file($path)) { $existing = $this->read($path, 'A14_ACTIVATION_REPLAY_CONFLICT'); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($demand)) throw new \RuntimeException('A14_ACTIVATION_REPLAY_CONFLICT: activation demand identity is already bound differently.'); return $existing; }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($demand, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Authorship Office activation demand cannot be committed atomically.'); }
        return $demand;
    }
}
