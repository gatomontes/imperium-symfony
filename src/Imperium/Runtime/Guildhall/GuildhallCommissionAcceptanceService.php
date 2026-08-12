<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\CanonicalJson;

final readonly class GuildhallCommissionAcceptanceService
{
    private string $inboxDirectory;
    private string $proceedingDirectory;
    private string $occupancyDirectory;
    private string $acceptanceDirectory;

    public function __construct(string $projectDir)
    {
        $this->inboxDirectory = $projectDir.'/var/imperium/offices/guildhall/inbox';
        $this->proceedingDirectory = $projectDir.'/var/imperium/curia/proceedings';
        $this->occupancyDirectory = $projectDir.'/var/imperium/offices/guildhall/occupancy';
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/guildhall/acceptances';
    }

    public function accept(string $commissionId, string $bindingId): array
    {
        if (!preg_match('/^planning-guildhall-[a-f0-9]{20}$/', $commissionId)) {
            throw new \InvalidArgumentException('G40_COMMISSION_INVALID: exact Guildhall planning commission identity is required.');
        }
        if (!preg_match('/^guildhall-binding-[a-f0-9]{20}$/', $bindingId)) {
            throw new \InvalidArgumentException('G41_BINDING_INVALID: exact Guildhall binding identity is required.');
        }

        $envelope = $this->read($this->inboxDirectory.'/'.$commissionId.'.json', 'G42_COMMISSION_DELIVERY_ABSENT');
        $commission = $envelope['packet'] ?? null;
        if (!is_array($commission)
            || !$this->digestMatches($envelope)
            || !$this->digestMatches($commission)
            || 'imperium.office-inbox-envelope/v1' !== ($envelope['schema'] ?? null)
            || 'guildhall' !== ($envelope['office'] ?? null)
            || 'guildhall.guildmaster' !== ($envelope['target'] ?? null)
            || $commissionId !== ($envelope['commission_id'] ?? null)
            || ($commission['record_digest'] ?? null) !== ($envelope['commission_digest'] ?? null)
            || 'DELIVERED_PENDING_RECIPIENT' !== ($envelope['status'] ?? null)
            || null !== ($envelope['recipient_acceptance'] ?? null)
            || true === ($envelope['execution_authority'] ?? null)
            || 'imperium.planning-commission/v1' !== ($commission['schema'] ?? null)
            || 'planning-only' !== ($commission['phase'] ?? null)
            || 'curia.seneschal' !== ($commission['issuer']['seat'] ?? null)
            || 'guildhall.guildmaster' !== ($commission['target'] ?? null)
            || 'ISSUED_PENDING_RECIPIENT' !== ($commission['status'] ?? null)
            || true === ($commission['execution_authority'] ?? null)
        ) {
            throw new \RuntimeException('G43_COMMISSION_DELIVERY_INVALID: exact unaccepted non-executing Guildhall commission is required.');
        }

        $binding = $this->read($this->occupancyDirectory.'/'.$bindingId.'.json', 'G44_BINDING_ABSENT');
        $guildmaster = $binding['bindings']['guildhall.guildmaster'] ?? null;
        if (!is_array($guildmaster)
            || !$this->digestMatches($binding)
            || 'imperium.guildhall-seat-binding-cohort/v1' !== ($binding['schema'] ?? null)
            || 'guildhall' !== ($binding['office'] ?? null)
            || 'ACTIVE_AWAITING_COMMISSION_ACCEPTANCE' !== ($binding['office_status'] ?? null)
            || true !== ($binding['binding_atomic'] ?? null)
            || 4 !== count($binding['bindings'] ?? [])
            || 'guildhall.guildmaster' !== ($guildmaster['seat'] ?? null)
            || 1 !== ($guildmaster['occupancy_generation'] ?? null)
            || 'BOUND_PENDING_COMMISSION_ACCEPTANCE' !== ($guildmaster['status'] ?? null)
            || true !== ($binding['seat_binding_authority'] ?? null)
            || true === ($binding['recipient_acceptance'] ?? null)
            || true === ($binding['execution_authority'] ?? null)
            || ($commission['instance_id'] ?? null) !== ($binding['instance_id'] ?? null)
        ) {
            throw new \RuntimeException('G45_BINDING_INVALID: exact atomic Guildhall occupancy cohort is required.');
        }

        $summonsId = $binding['source_summons_id'] ?? null;
        $summonsPaths = is_string($summonsId) ? (glob($this->proceedingDirectory.'/*.summons.'.$summonsId.'.json') ?: []) : [];
        if (1 !== count($summonsPaths)) {
            throw new \RuntimeException('G46_ACCEPTANCE_CHAIN_INVALID: exact source summons is unavailable.');
        }
        $summons = $this->read($summonsPaths[0], 'G46_ACCEPTANCE_CHAIN_INVALID');
        if (!$this->digestMatches($summons)
            || ($binding['source_summons_digest'] ?? null) !== ($summons['record_digest'] ?? null)
            || $commissionId !== ($summons['planning_commission_id'] ?? null)
            || ($commission['record_digest'] ?? null) !== ($summons['planning_commission_digest'] ?? null)
            || ($commission['proceeding_id'] ?? null) !== ($summons['proceeding_id'] ?? null)
        ) {
            throw new \RuntimeException('G46_ACCEPTANCE_CHAIN_INVALID: commission, summons, and binding lineage do not agree.');
        }

        $acceptance = [
            'schema' => 'imperium.guildhall-commission-acceptance/v1',
            'acceptance_id' => 'guildhall-acceptance-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $commission['record_digest'], $bindingId, $binding['record_digest'], $guildmaster])), 0, 20),
            'instance_id' => $binding['instance_id'],
            'proceeding_id' => $commission['proceeding_id'],
            'commission_id' => $commissionId,
            'commission_digest' => $commission['record_digest'],
            'delivery_id' => $envelope['delivery_id'],
            'delivery_digest' => $envelope['record_digest'],
            'summons_id' => $summonsId,
            'summons_digest' => $summons['record_digest'],
            'binding_id' => $bindingId,
            'binding_digest' => $binding['record_digest'],
            'actor' => [
                'seat' => 'guildhall.guildmaster',
                'manifestation_id' => $guildmaster['manifestation_id'],
                'occupancy_generation' => $guildmaster['occupancy_generation'],
            ],
            'disposition' => 'ACCEPTED_FOR_INSTITUTIONAL_DELIBERATION',
            'authorized_scope' => [
                'purpose' => $commission['purpose'] ?? null,
                'authorized_resources' => $commission['authorized_resources'] ?? [],
                'expected_products' => $commission['expected_products'] ?? [],
                'forbidden_effects' => $commission['forbidden_effects'] ?? [],
            ],
            'recipient_acceptance' => true,
            'deliberation_authority' => true,
            'personnel_disposition_authority' => true,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'execution_authority' => false,
        ];

        return $this->persist($acceptance);
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function persist(array $acceptance): array
    {
        if (!is_dir($this->acceptanceDirectory) && !mkdir($this->acceptanceDirectory, 0770, true) && !is_dir($this->acceptanceDirectory)) {
            throw new \RuntimeException('Guildhall acceptance directory cannot be created.');
        }
        $acceptance['record_digest'] = hash('sha256', CanonicalJson::encode($acceptance));
        $path = $this->acceptanceDirectory.'/'.$acceptance['acceptance_id'].'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'G47_ACCEPTANCE_REPLAY_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($acceptance)) {
                throw new \RuntimeException('G47_ACCEPTANCE_REPLAY_CONFLICT: acceptance identity is already bound differently.');
            }

            return $existing;
        }
        foreach (glob($this->acceptanceDirectory.'/guildhall-acceptance-*.json') ?: [] as $existingPath) {
            $existing = $this->read($existingPath, 'G47_ACCEPTANCE_REPLAY_CONFLICT');
            if ($acceptance['commission_id'] === ($existing['commission_id'] ?? null)) {
                throw new \RuntimeException('G48_COMMISSION_ALREADY_DISPOSED: commission already has a Guildhall acceptance disposition.');
            }
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($acceptance, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Guildhall acceptance cannot be committed atomically.');
        }

        return $acceptance;
    }
}
