<?php

declare(strict_types=1);

namespace App\Bootstrap;

final readonly class MasterMason
{
    public function __construct(private StateStore $store)
    {
    }

    public function executeThroughOrdinaryRecruiter(ValidationReceipt $receipt, string $instanceId): array
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
            if (BootstrapState::ProvisionalRecruiterBound === BootstrapState::from($record['state'])) {
                $record = $this->installOrdinaryRecruiter($record, $receipt, $instanceId);
            }

            return $record;
        });
    }

    private function installOrdinaryRecruiter(array $record, ValidationReceipt $receipt, string $instanceId): array
    {
        $provisional = $this->lastSuccessfulOutput($record, 'T03');
        if (
            ($provisional['seat'] ?? null) !== 'conscription.recruiter'
            || ($provisional['authority'] ?? null) !== 'succession-only'
            || ($provisional['occupancy_generation'] ?? null) !== 1
        ) {
            throw new \RuntimeException('B30_PROVISIONAL_RECRUITER_CHANGED: T03 occupancy no longer matches its receipt.');
        }

        $commissionRecord = $receipt->manifest['unsigned_payload']['primordial']['succession_commission'] ?? null;
        $commission = $receipt->successionCommission;
        if (!is_array($commissionRecord) || !isset($commissionRecord['digest'])) {
            throw new \RuntimeException('B31_SUCCESSION_COMMISSION_MISMATCH: no pinned commission is present.');
        }

        $commissionId = 'primordial.recruiter-succession.1';
        $expectedCommission = [
            $commissionId,
            true,
            'charter-development-1',
            'conscription.recruiter',
            'conscription.recruiter.ordinary@1.0.0',
            'generic-officer.ordinary-recruiter@1.0.0',
            'qualification.conscription.recruiter.ordinary.v1',
            1,
        ];
        $observedCommission = [
            $commission['id'] ?? null,
            $commission['single_use'] ?? null,
            $commission['charter_generation'] ?? null,
            $commission['target']['seat'] ?? null,
            $commission['target']['profile'] ?? null,
            $commission['target']['substrate'] ?? null,
            $commission['target']['qualification_contract'] ?? null,
            $commission['constraints']['expected_occupancy_generation'] ?? null,
        ];
        if ($expectedCommission !== $observedCommission) {
            throw new \RuntimeException('B31_SUCCESSION_COMMISSION_MISMATCH: pinned commission contents are invalid.');
        }

        $successorId = $instanceId.'.officer.ordinary-recruiter.1';
        if ($successorId === ($provisional['manifestation_id'] ?? null)) {
            throw new \RuntimeException('B32_SUCCESSOR_QUALIFICATION_FAILED: successor identity is not distinct.');
        }

        $qualificationPacket = [
            'commission_id' => $commissionId,
            'commission_digest' => $commissionRecord['digest'],
            'candidate_id' => $successorId,
            'profile' => 'conscription.recruiter.ordinary@1.0.0',
            'substrate' => 'generic-officer.ordinary-recruiter@1.0.0',
            'qualification_contract' => 'qualification.conscription.recruiter.ordinary.v1',
            'checks' => [
                'exact_profile_installation' => true,
                'declared_authority_restraint' => true,
                'version_and_provenance_preservation' => true,
            ],
        ];

        return $this->transition($record, 'T04', BootstrapState::OrdinaryRecruiterBound, [
            'commission' => [
                'id' => $commissionId,
                'digest' => $commissionRecord['digest'],
                'consumed' => true,
            ],
            'retired' => [
                'manifestation_id' => $provisional['manifestation_id'],
                'seat' => $provisional['seat'],
                'occupancy_generation' => $provisional['occupancy_generation'],
                'disposition' => 'retired-after-succession',
            ],
            'successor' => [
                'manifestation_id' => $successorId,
                'seat' => 'conscription.recruiter',
                'occupancy_generation' => 2,
                'authority' => 'ordinary-recruiter',
                'predecessor' => $provisional['manifestation_id'],
            ],
            'qualification_packet' => $qualificationPacket,
            'qualification_packet_digest' => hash('sha256', CanonicalJson::encode($qualificationPacket)),
        ]);
    }

    private function lastSuccessfulOutput(array $record, string $transition): array
    {
        for ($index = count($record['events']) - 1; $index >= 0; --$index) {
            $event = $record['events'][$index];
            if (($event['transition'] ?? null) === $transition && ($event['result'] ?? null) === 'SUCCESS') {
                return is_array($event['output'] ?? null) ? $event['output'] : [];
            }
        }

        throw new \RuntimeException(sprintf('Missing successful %s receipt.', $transition));
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
