<?php

declare(strict_types=1);

namespace App\Bootstrap;

final readonly class MasterMason
{
    public function __construct(private StateStore $store)
    {
    }

    public function executeThroughProvisionalRecruiter(ValidationReceipt $receipt, string $instanceId): array
    {
        return $this->store->locked(function () use ($receipt, $instanceId): array {
            $record = $this->store->read();
            if (null !== $record) {
                $this->assertSameBinding($record, $receipt, $instanceId);
            } else {
                $record = $this->initialRecord($receipt, $instanceId);
            }

            $state = BootstrapState::from($record['state']);
            if (BootstrapState::Uninitialized === $state) {
                $record = $this->transition($record, 'T01', BootstrapState::ManifestBound, [
                    'binding_digest' => hash('sha256', CanonicalJson::encode($record['binding'])),
                ]);
            }
            if (BootstrapState::ManifestBound === BootstrapState::from($record['state'])) {
                $record = $this->transition($record, 'T02', BootstrapState::ConscriptionActive, [
                    'runtime_id' => $instanceId.'.office.conscription',
                    'mode' => 'mechanical-interface-only',
                ]);
            }
            if (BootstrapState::ConscriptionActive === BootstrapState::from($record['state'])) {
                $record = $this->transition($record, 'T03', BootstrapState::ProvisionalRecruiterBound, [
                    'manifestation_id' => $instanceId.'.officer.provisional-recruiter.1',
                    'seat' => 'conscription.recruiter',
                    'occupancy_generation' => 1,
                    'authority' => 'succession-only',
                ]);
            }

            return $record;
        });
    }

    private function initialRecord(ValidationReceipt $receipt, string $instanceId): array
    {
        return [
            'schema' => 'imperium.bootstrap-state/v1',
            'state' => BootstrapState::Uninitialized->value,
            'generation' => 0,
            'binding' => [
                'instance_id' => $instanceId,
                'manifest_id' => $receipt->manifestId,
                'charter_generation' => $receipt->charterGeneration,
                'artifact_set_digest' => $receipt->artifactSetDigest,
                'launcher_digest' => $receipt->launcherDigest,
                'mastermason_digest' => $receipt->masterMasonDigest,
            ],
            'events' => [],
        ];
    }

    private function transition(array $record, string $transition, BootstrapState $to, array $output): array
    {
        ++$record['generation'];
        $record['state'] = $to->value;
        $record['events'][] = [
            'transition' => $transition,
            'result' => 'SUCCESS',
            'state' => $to->value,
            'generation' => $record['generation'],
            'occurred_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'transaction_id' => hash('sha256', $record['binding']['instance_id'].'|'.$record['binding']['manifest_id']),
            'output' => $output,
        ];
        $this->store->write($record);
        return $record;
    }

    private function assertSameBinding(array $record, ValidationReceipt $receipt, string $instanceId): void
    {
        $expected = [$instanceId, $receipt->manifestId, $receipt->charterGeneration, $receipt->artifactSetDigest];
        $observed = [
            $record['binding']['instance_id'] ?? null,
            $record['binding']['manifest_id'] ?? null,
            $record['binding']['charter_generation'] ?? null,
            $record['binding']['artifact_set_digest'] ?? null,
        ];
        if ($expected !== $observed) {
            throw new \RuntimeException('B02_INSTANCE_EXISTS: persisted instance binding conflicts with this activation.');
        }
    }
}
