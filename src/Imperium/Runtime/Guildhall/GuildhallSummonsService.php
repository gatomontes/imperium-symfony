<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Curia\ProceedingStore;

final readonly class GuildhallSummonsService
{
    private string $caseDirectory;
    private string $demandDirectory;
    private string $guildhallInbox;
    private string $proceedingDirectory;
    private string $conscriptionInbox;

    public function __construct(
        string $projectDir,
        private StateStore $bootstrap,
        private ProceedingStore $proceedings,
        private CanonicalGuildhallStaffRegistry $staff,
    ) {
        $this->caseDirectory = $projectDir.'/var/imperium/mastermason/activation-cases';
        $this->demandDirectory = $projectDir.'/var/imperium/mastermason/spawning-requests';
        $this->guildhallInbox = $projectDir.'/var/imperium/offices/guildhall/inbox';
        $this->proceedingDirectory = $projectDir.'/var/imperium/curia/proceedings';
        $this->conscriptionInbox = $projectDir.'/var/imperium/offices/conscription/inbox';
    }

    public function summon(string $caseId): array
    {
        if (!preg_match('/^guildhall-provisioning-[a-f0-9]{20}$/', $caseId)) {
            throw new \InvalidArgumentException('M40_PROVISIONING_CASE_INVALID: exact Guildhall provisioning case identity is required.');
        }
        $case = $this->read($this->caseDirectory.'/'.$caseId.'.json', 'M41_PROVISIONING_CASE_ABSENT');
        if (!$this->digestMatches($case)
            || $caseId !== ($case['case_id'] ?? null)
            || 'CANONICAL_STAFF_READY' !== ($case['status'] ?? null)
            || true === ($case['activation_request_recorded'] ?? null)
            || true === ($case['spawning_authority'] ?? null)
            || true === ($case['recipient_acceptance'] ?? null)
            || true === ($case['execution_authority'] ?? null)
            || CanonicalJson::encode($this->staff->current()) !== CanonicalJson::encode($case['canonical_staff_package'] ?? null)
        ) {
            throw new \RuntimeException('M42_PROVISIONING_CASE_INVALID: exact ready non-authorizing Guildhall provisioning case is required.');
        }
        $demandId = $case['activation_demand_id'] ?? null;
        if (!is_string($demandId)) {
            throw new \RuntimeException('M42_PROVISIONING_CASE_INVALID: activation demand reference is absent.');
        }
        $demand = $this->read($this->demandDirectory.'/'.$demandId.'.json', 'M43_ACTIVATION_DEMAND_ABSENT');
        if (!$this->digestMatches($demand)
            || ($case['activation_demand_digest'] ?? null) !== ($demand['record_digest'] ?? null)
            || 'PROFILE_DEFINITIONS_READY' !== ($demand['status'] ?? null)
        ) {
            throw new \RuntimeException('M44_ACTIVATION_CHAIN_INVALID: provisioning case does not bind the exact activation demand.');
        }
        $commissionId = $demand['commission_id'] ?? null;
        if (!is_string($commissionId)) {
            throw new \RuntimeException('M44_ACTIVATION_CHAIN_INVALID: Guildhall planning commission reference is absent.');
        }
        $delivery = $this->read($this->guildhallInbox.'/'.$commissionId.'.json', 'M45_GUILDHALL_DELIVERY_ABSENT');
        $commission = $delivery['packet'] ?? null;
        $proceedingId = is_array($commission) ? ($commission['proceeding_id'] ?? null) : null;
        if (!is_array($commission)
            || !is_string($proceedingId)
            || !$this->digestMatches($delivery)
            || !$this->digestMatches($commission)
            || ($demand['delivery_id'] ?? null) !== ($delivery['delivery_id'] ?? null)
            || ($demand['delivery_digest'] ?? null) !== ($delivery['record_digest'] ?? null)
            || ($delivery['commission_digest'] ?? null) !== ($commission['record_digest'] ?? null)
            || $commissionId !== ($commission['commission_id'] ?? null)
            || 'curia.seneschal' !== ($commission['issuer']['seat'] ?? null)
            || 'guildhall.guildmaster' !== ($commission['target'] ?? null)
            || true === ($commission['execution_authority'] ?? null)
        ) {
            throw new \RuntimeException('M46_SENESCHAL_COMMISSION_INVALID: exact non-executing Seneschal-issued Guildhall commission is required.');
        }

        $proceeding = $this->proceedings->find($proceedingId);
        $runtime = $this->readyRuntime();
        if (!is_array($proceeding)
            || !$this->digestMatches($proceeding)
            || ($proceeding['instance_id'] ?? null) !== ($runtime['instance_id'] ?? null)
        ) {
            throw new \RuntimeException('M47_CURIAL_PROCEEDING_INVALID: exact current Curial proceeding is unavailable.');
        }
        $seneschal = $this->occupant($runtime, 'seneschal');
        $chamberlain = $this->occupant($runtime, 'chamberlain');
        if (!$this->sameOccupant($seneschal, $proceeding['seneschal']['occupant'] ?? null)
            || !$this->sameOccupant($chamberlain, $proceeding['chamberlain']['occupant'] ?? null)
        ) {
            throw new \RuntimeException('M48_CURIAL_OCCUPANCY_CHANGED: Seneschal or Chamberlain no longer matches the proceeding.');
        }

        $identity = [$caseId, $case['record_digest'], $commissionId, $commission['record_digest'], $seneschal, $chamberlain];
        $summonsId = 'guildhall-summons-'.substr(hash('sha256', CanonicalJson::encode($identity)), 0, 20);
        $summons = [
            'schema' => 'imperium.guildhall-summons/v1',
            'summons_id' => $summonsId,
            'proceeding_id' => $proceedingId,
            'instance_id' => $runtime['instance_id'],
            'provisioning_case_id' => $caseId,
            'provisioning_case_digest' => $case['record_digest'],
            'planning_commission_id' => $commissionId,
            'planning_commission_digest' => $commission['record_digest'],
            'trigger' => 'curia.personnel_question',
            'seneschal' => ['disposition' => 'GUILDHALL_ACTIVATION_REQUESTED', 'occupant' => $seneschal],
            'chamberlain' => ['disposition' => 'GUILDHALL_SUMMONS_RECORDED_AND_ROUTED', 'occupant' => $chamberlain],
            'mastermason' => ['disposition' => 'EXACT_SUMMONS_VALIDATED', 'charter_route' => 'curia.seneschal→curia.chamberlain→mastermason→conscription'],
            'canonical_staff_package' => $this->staff->current(),
            'spawning_authority' => true,
            'spawning_authority_scope' => 'four exact Guildhall manifestations for this provisioning case only',
            'recipient_acceptance' => false,
            'execution_authority' => false,
        ];
        $summons = $this->persist($this->proceedingDirectory.'/'.$proceedingId.'.summons.'.$summonsId.'.json', $summons, 'M49_SUMMONS_REPLAY_CONFLICT');

        $commissions = [];
        foreach ($this->staff->members() as $member) {
            $commissionIdentity = [$summonsId, $summons['record_digest'], $member];
            $constructionId = 'guildhall-construction-'.substr(hash('sha256', CanonicalJson::encode($commissionIdentity)), 0, 20);
            $commissions[] = $this->persist($this->conscriptionInbox.'/'.$constructionId.'.json', [
                'schema' => 'imperium.construction-commission/v1',
                'commission_id' => $constructionId,
                'issuer' => 'mastermason',
                'source_summons_id' => $summonsId,
                'source_summons_digest' => $summons['record_digest'],
                'instance_id' => $runtime['instance_id'],
                'office' => 'guildhall',
                'target_seat' => $member['seat'],
                'persona' => $member['persona'],
                'profile' => $member['profile'],
                'qualification_contract' => $member['qualification_contract'],
                'substrate' => 'generic-officer@1.0.0',
                'status' => 'ISSUED_PENDING_CONSCRIPTION',
                'spawning_authority' => true,
                'authority_scope' => 'instantiate and qualify one manifestation for the exact target Seat',
                'seat_binding_authority' => false,
                'recipient_acceptance' => false,
                'execution_authority' => false,
            ], 'M50_CONSTRUCTION_COMMISSION_REPLAY_CONFLICT');
        }

        return ['summons' => $summons, 'commissions' => $commissions];
    }

    private function readyRuntime(): array
    {
        $bootstrap = $this->bootstrap->read();
        if (!is_array($bootstrap) || BootstrapState::CuriaReady->value !== ($bootstrap['state'] ?? null)) {
            throw new \RuntimeException('M51_CURIA_NOT_READY: Guildhall summons requires CURIA_READY.');
        }
        for ($index = count($bootstrap['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $bootstrap['events'][$index];
            if ('T10' === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null)) {
                $runtime = $event['output']['runtime'] ?? null;
                if (is_array($runtime) && true === ($runtime['addressable'] ?? null)) {
                    $runtime['instance_id'] = $bootstrap['binding']['instance_id'] ?? null;

                    return $runtime;
                }
            }
        }
        throw new \RuntimeException('M51_CURIA_NOT_READY: current Curia runtime receipt is unavailable.');
    }

    private function occupant(array $runtime, string $role): array
    {
        $occupant = $runtime['occupants'][$role] ?? null;
        if (!is_array($occupant) || 'active' !== ($occupant['status'] ?? null)) {
            throw new \RuntimeException('M52_CURIAL_SEAT_UNAVAILABLE: curia.'.$role.' is not actively occupied.');
        }

        return ['seat' => 'curia.'.$role, 'manifestation_id' => $occupant['manifestation_id'], 'occupancy_generation' => $occupant['occupancy_generation']];
    }

    private function sameOccupant(array $expected, mixed $actual): bool
    {
        return is_array($actual) && CanonicalJson::encode($expected) === CanonicalJson::encode($actual);
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

    private function persist(string $path, array $record, string $conflict): array
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('Runtime record directory cannot be created.');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        if (is_file($path)) {
            $existing = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException($conflict);
            }

            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Runtime record cannot be committed atomically.');
        }

        return $record;
    }
}
