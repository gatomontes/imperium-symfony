<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;

final readonly class PlanningCommissionRouter
{
    private string $officeDirectory;

    public function __construct(
        private ProceedingStore $store,
        string $projectDir,
    ) {
        $this->officeDirectory = $projectDir.'/var/imperium/offices';
    }

    public function deliver(string $proceedingId): array
    {
        $proceeding = $this->store->find($proceedingId);
        if (null === $proceeding) {
            throw new \RuntimeException('C50_DELIVERY_PROCEEDING_UNKNOWN: Curian proceeding is unavailable.');
        }

        $commissions = $this->store->commissions($proceedingId);
        if ([] === $commissions) {
            throw new \RuntimeException('C51_DELIVERY_COMMISSION_ABSENT: no issued planning commission is available.');
        }

        $deliveries = [];
        foreach ($commissions as $commission) {
            $commissionId = $commission['commission_id'] ?? null;
            $target = $commission['target'] ?? null;
            if (!is_string($commissionId) || !is_string($target)) {
                throw new \RuntimeException('C52_DELIVERY_PACKET_INVALID: commission identity and target are required.');
            }
            if ('imperium.planning-commission/v1' !== ($commission['schema'] ?? null)
                || 'planning-only' !== ($commission['phase'] ?? null)
                || 'ISSUED_PENDING_RECIPIENT' !== ($commission['status'] ?? null)
                || true === ($commission['execution_authority'] ?? null)
                || $proceedingId !== ($commission['proceeding_id'] ?? null)
                || ($proceeding['instance_id'] ?? null) !== ($commission['instance_id'] ?? null)
            ) {
                throw new \RuntimeException('C52_DELIVERY_PACKET_INVALID: only exact non-executing issued planning commissions may be delivered.');
            }

            $office = match ($target) {
                'guildhall.guildmaster' => 'guildhall',
                'armory' => 'armory',
                default => throw new \RuntimeException('C53_DELIVERY_TARGET_CLOSED: no route exists for '.$target.'.'),
            };
            $deliveries[$office] = $this->persistEnvelope($office, $commissionId, [
                'schema' => 'imperium.office-inbox-envelope/v1',
                'delivery_id' => 'delivery-'.substr(hash('sha256', CanonicalJson::encode([$office, $commissionId, $commission['record_digest'] ?? null])), 0, 24),
                'office' => $office,
                'target' => $target,
                'commission_id' => $commissionId,
                'commission_digest' => $commission['record_digest'] ?? null,
                'status' => 'DELIVERED_PENDING_RECIPIENT',
                'recipient_acceptance' => null,
                'execution_authority' => false,
                'packet' => $commission,
            ]);
        }

        return [
            'proceeding_id' => $proceedingId,
            'deliveries' => $deliveries,
            'execution_authority' => false,
        ];
    }

    private function persistEnvelope(string $office, string $commissionId, array $envelope): array
    {
        $directory = $this->officeDirectory.'/'.$office.'/inbox';
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('Office inbox cannot be created.');
        }
        $path = $directory.'/'.$commissionId.'.json';
        $envelope['record_digest'] = hash('sha256', CanonicalJson::encode($envelope));
        if (is_file($path)) {
            $existing = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($envelope)) {
                throw new \RuntimeException('C54_DELIVERY_REPLAY_CONFLICT: Office inbox identity is already bound differently.');
            }

            return $existing;
        }

        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($envelope, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Office inbox delivery cannot be committed atomically.');
        }

        return $envelope;
    }
}
