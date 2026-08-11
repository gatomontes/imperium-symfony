<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Guildhall\CanonicalGuildhallStaffRegistry;

final readonly class GuildhallConscriptionService
{
    private string $proceedingDirectory;
    private string $commissionDirectory;
    private string $deliveryDirectory;

    public function __construct(
        string $projectDir,
        private StateStore $bootstrap,
        private CanonicalGuildhallStaffRegistry $staff,
        private GenericOfficerSubstrateRegistry $substrate,
    ) {
        $this->proceedingDirectory = $projectDir.'/var/imperium/curia/proceedings';
        $this->commissionDirectory = $projectDir.'/var/imperium/offices/conscription/inbox';
        $this->deliveryDirectory = $projectDir.'/var/imperium/mastermason/qualified-manifestations';
    }

    public function fulfill(string $summonsId): array
    {
        if (!preg_match('/^guildhall-summons-[a-f0-9]{20}$/', $summonsId)) {
            throw new \InvalidArgumentException('R20_GUILDHALL_SUMMONS_INVALID: exact Guildhall summons identity is required.');
        }
        $paths = glob($this->proceedingDirectory.'/*.summons.'.$summonsId.'.json') ?: [];
        if (1 !== count($paths)) {
            throw new \RuntimeException('R21_GUILDHALL_SUMMONS_ABSENT: one exact recorded Guildhall summons is required.');
        }
        $summons = $this->read($paths[0]);
        $staffPackage = $this->staff->current();
        if (!$this->digestMatches($summons)
            || $summonsId !== ($summons['summons_id'] ?? null)
            || true !== ($summons['spawning_authority'] ?? null)
            || true === ($summons['recipient_acceptance'] ?? null)
            || true === ($summons['execution_authority'] ?? null)
            || CanonicalJson::encode($staffPackage) !== CanonicalJson::encode($summons['canonical_staff_package'] ?? null)
            || CanonicalJson::encode($this->substrate->current()) !== CanonicalJson::encode($summons['generic_officer_substrate'] ?? null)
            || 'EXACT_SUMMONS_VALIDATED' !== ($summons['mastermason']['disposition'] ?? null)
        ) {
            throw new \RuntimeException('R22_GUILDHALL_SUMMONS_INVALID: exact validated non-executing summons is required.');
        }
        [$instanceId, $recruiter] = $this->ordinaryRecruiter();
        if ($instanceId !== ($summons['instance_id'] ?? null)) {
            throw new \RuntimeException('R23_INSTANCE_MISMATCH: summons targets another Imperium instance.');
        }

        $commissions = [];
        foreach (glob($this->commissionDirectory.'/guildhall-construction-*.json') ?: [] as $path) {
            $commission = $this->read($path);
            if ($summonsId === ($commission['source_summons_id'] ?? null)) {
                $commissions[$commission['target_seat'] ?? ''] = $commission;
            }
        }
        $members = [];
        foreach ($this->staff->members() as $member) {
            $members[$member['seat']] = $member;
        }
        $memberSeats = array_keys($members);
        $commissionSeats = array_keys($commissions);
        sort($memberSeats, SORT_STRING);
        sort($commissionSeats, SORT_STRING);
        if (4 !== count($commissions) || $memberSeats !== $commissionSeats) {
            throw new \RuntimeException('R24_CONSTRUCTION_SET_INVALID: four exact ordered Guildhall construction commissions are required.');
        }

        $deliveries = [];
        foreach ($members as $seat => $member) {
            $commission = $commissions[$seat];
            if (!$this->digestMatches($commission)
                || 'imperium.construction-commission/v1' !== ($commission['schema'] ?? null)
                || 'mastermason' !== ($commission['issuer'] ?? null)
                || ($summons['record_digest'] ?? null) !== ($commission['source_summons_digest'] ?? null)
                || $instanceId !== ($commission['instance_id'] ?? null)
                || 'ISSUED_PENDING_CONSCRIPTION' !== ($commission['status'] ?? null)
                || true !== ($commission['spawning_authority'] ?? null)
                || true === ($commission['seat_binding_authority'] ?? null)
                || true === ($commission['execution_authority'] ?? null)
                || CanonicalJson::encode($member['persona']) !== CanonicalJson::encode($commission['persona'] ?? null)
                || CanonicalJson::encode($member['profile']) !== CanonicalJson::encode($commission['profile'] ?? null)
                || CanonicalJson::encode($member['qualification_contract']) !== CanonicalJson::encode($commission['qualification_contract'] ?? null)
                || CanonicalJson::encode($this->substrate->current()) !== CanonicalJson::encode($commission['substrate'] ?? null)
            ) {
                throw new \RuntimeException('R25_CONSTRUCTION_COMMISSION_INVALID: '.$seat.' commission is invalid.');
            }

            $manifestationId = $instanceId.'.officer.'.$seat.'.'.substr(hash('sha256', CanonicalJson::encode([$summonsId, $commission['commission_id'], $member])), 0, 12);
            $qualification = [
                'disposition_id' => 'qualification-'.substr(hash('sha256', CanonicalJson::encode([$manifestationId, $commission['record_digest'], $recruiter])), 0, 20),
                'actor' => ['seat' => 'conscription.recruiter', 'manifestation_id' => $recruiter['manifestation_id'], 'occupancy_generation' => $recruiter['occupancy_generation']],
                'candidate_id' => $manifestationId,
                'qualification_contract' => $member['qualification_contract'],
                'disposition' => 'QUALIFIED',
                'checks' => [
                    'exact_persona_lineage' => true,
                    'exact_profile_installation' => true,
                    'approval_and_current_active_chain' => true,
                    'declared_authority_restraint' => true,
                    'version_and_provenance_preservation' => true,
                ],
            ];
            $packet = [
                'schema' => 'imperium.qualified-manifestation-packet/v1',
                'delivery_id' => 'qualified-delivery-'.substr(hash('sha256', CanonicalJson::encode([$manifestationId, $qualification])), 0, 20),
                'source_summons_id' => $summonsId,
                'source_summons_digest' => $summons['record_digest'],
                'commission' => ['id' => $commission['commission_id'], 'digest' => $commission['record_digest'], 'consumed' => true],
                'candidate' => [
                    'manifestation_id' => $manifestationId,
                    'instance_id' => $instanceId,
                    'persona' => $member['persona'],
                    'profile' => $member['profile'],
                    'substrate_instance' => [
                        'instance_id' => $manifestationId.'.substrate',
                        'substrate' => $this->substrate->current(),
                        'status' => 'PROFILE_INSTALLED',
                    ],
                    'target_seat' => $seat,
                    'target_occupancy_generation' => 1,
                    'status' => 'QUALIFIED_UNBOUND',
                ],
                'qualification' => $qualification,
                'qualification_digest' => hash('sha256', CanonicalJson::encode($qualification)),
                'sealed' => true,
                'seat_binding_authority' => false,
                'recipient_acceptance' => false,
                'execution_authority' => false,
            ];
            $deliveries[] = $this->persist($this->deliveryDirectory.'/'.$packet['delivery_id'].'.json', $packet);
        }

        return ['summons_id' => $summonsId, 'recruiter' => $recruiter, 'deliveries' => $deliveries];
    }

    private function ordinaryRecruiter(): array
    {
        $bootstrap = $this->bootstrap->read();
        if (!is_array($bootstrap) || BootstrapState::CuriaReady->value !== ($bootstrap['state'] ?? null)) {
            throw new \RuntimeException('R26_RECRUITER_UNAVAILABLE: Conscription requires CURIA_READY and the ordinary Recruiter.');
        }
        for ($index = count($bootstrap['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $bootstrap['events'][$index];
            if ('T04' === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null)) {
                $recruiter = $event['output']['successor'] ?? null;
                if (is_array($recruiter)
                    && 'conscription.recruiter' === ($recruiter['seat'] ?? null)
                    && 'ordinary-recruiter' === ($recruiter['authority'] ?? null)
                    && 2 === ($recruiter['occupancy_generation'] ?? null)
                ) {
                    return [$bootstrap['binding']['instance_id'] ?? null, $recruiter];
                }
            }
        }
        throw new \RuntimeException('R26_RECRUITER_UNAVAILABLE: ordinary Recruiter occupancy receipt is absent.');
    }

    private function read(string $path): array
    {
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function persist(string $path, array $packet): array
    {
        if (!is_dir($this->deliveryDirectory) && !mkdir($this->deliveryDirectory, 0770, true) && !is_dir($this->deliveryDirectory)) {
            throw new \RuntimeException('Qualified-manifestation delivery directory cannot be created.');
        }
        $packet['record_digest'] = hash('sha256', CanonicalJson::encode($packet));
        if (is_file($path)) {
            $existing = $this->read($path);
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($packet)) {
                throw new \RuntimeException('R27_QUALIFIED_PACKET_REPLAY_CONFLICT: delivery identity is already bound differently.');
            }

            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($packet, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Qualified-manifestation packet cannot be committed atomically.');
        }

        return $packet;
    }
}
