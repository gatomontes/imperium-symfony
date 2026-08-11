<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\CanonicalJson;

final readonly class GuildhallActivationDemandService
{
    private string $guildhallInbox;
    private string $demandDirectory;

    public function __construct(string $projectDir)
    {
        $this->guildhallInbox = $projectDir.'/var/imperium/offices/guildhall/inbox';
        $this->demandDirectory = $projectDir.'/var/imperium/mastermason/spawning-requests';
    }

    public function demand(string $commissionId): array
    {
        if (!preg_match('/^planning-guildhall-[a-f0-9]{20}$/', $commissionId)) {
            throw new \InvalidArgumentException('G10_ACTIVATION_COMMISSION_INVALID: exact Guildhall planning commission identity is required.');
        }
        $envelopePath = $this->guildhallInbox.'/'.$commissionId.'.json';
        if (!is_file($envelopePath)) {
            throw new \RuntimeException('G11_ACTIVATION_DELIVERY_ABSENT: delivered Guildhall commission is unavailable.');
        }
        $envelope = json_decode((string) file_get_contents($envelopePath), true, 512, JSON_THROW_ON_ERROR);
        $packet = $envelope['packet'] ?? null;
        if (!is_array($packet)
            || !$this->digestMatches($envelope)
            || !$this->digestMatches($packet)
            || 'imperium.office-inbox-envelope/v1' !== ($envelope['schema'] ?? null)
            || 'guildhall' !== ($envelope['office'] ?? null)
            || 'guildhall.guildmaster' !== ($envelope['target'] ?? null)
            || 'DELIVERED_PENDING_RECIPIENT' !== ($envelope['status'] ?? null)
            || null !== ($envelope['recipient_acceptance'] ?? null)
            || true === ($envelope['execution_authority'] ?? null)
            || $commissionId !== ($packet['commission_id'] ?? null)
            || ($envelope['commission_digest'] ?? null) !== ($packet['record_digest'] ?? null)
        ) {
            throw new \RuntimeException('G12_ACTIVATION_DELIVERY_INVALID: exact unaccepted Guildhall delivery is required.');
        }

        $seats = [
            ['seat' => 'guildhall.guildmaster', 'profile' => 'guildhall.guildmaster', 'profile_source' => 'offices/guildhall/profile-guildmaster.md'],
            ['seat' => 'guildhall.committee.disciplinary-fit', 'profile' => 'guildhall.committee.disciplinary-fit', 'profile_source' => 'offices/guildhall/profile-committee-disciplinary-fit.md'],
            ['seat' => 'guildhall.committee.composition', 'profile' => 'guildhall.committee.composition', 'profile_source' => 'offices/guildhall/profile-committee-composition.md'],
            ['seat' => 'guildhall.committee.boundary-challenge', 'profile' => 'guildhall.committee.boundary-challenge', 'profile_source' => 'offices/guildhall/profile-committee-boundary-challenge.md'],
        ];
        $identity = [$commissionId, $envelope['record_digest'] ?? null, array_column($seats, 'seat')];
        $demandId = 'guildhall-activation-'.substr(hash('sha256', CanonicalJson::encode($identity)), 0, 20);
        $demand = [
            'schema' => 'imperium.office-activation-demand/v1',
            'demand_id' => $demandId,
            'requester' => 'guildhall.inbox-router',
            'recipient' => 'mastermason',
            'office' => 'guildhall',
            'commission_id' => $commissionId,
            'delivery_id' => $envelope['delivery_id'] ?? null,
            'delivery_digest' => $envelope['record_digest'] ?? null,
            'required_seats' => $seats,
            'missing_prerequisites' => [
                'immutable Profile artifacts for all required Seats',
                'current/active Profile attestations and approval chains',
                'MasterMason spawning authorization',
                'Conscription qualification packets',
                'atomic Seat bindings',
            ],
            'status' => 'BLOCKED_PROFILE_ARTIFACTS',
            'spawning_authority' => false,
            'recipient_acceptance' => false,
            'execution_authority' => false,
        ];

        return $this->persist($demandId, $demand);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function persist(string $demandId, array $demand): array
    {
        if (!is_dir($this->demandDirectory) && !mkdir($this->demandDirectory, 0770, true) && !is_dir($this->demandDirectory)) {
            throw new \RuntimeException('MasterMason spawning-request directory cannot be created.');
        }
        $path = $this->demandDirectory.'/'.$demandId.'.json';
        $demand['record_digest'] = hash('sha256', CanonicalJson::encode($demand));
        if (is_file($path)) {
            $existing = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($demand)) {
                throw new \RuntimeException('G13_ACTIVATION_REPLAY_CONFLICT: activation demand identity is already bound differently.');
            }

            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($demand, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Guildhall activation demand cannot be committed atomically.');
        }

        return $demand;
    }
}
