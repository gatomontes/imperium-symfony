<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Foundry\CanonicalFoundryStaffRegistry;

final readonly class ArtificerConscriptionService
{
    private string $commissionDirectory;
    private string $caseDirectory;
    private string $deliveryDirectory;

    public function __construct(string $projectDir, private StateStore $bootstrap, private CanonicalFoundryStaffRegistry $staff, private GenericOfficerSubstrateRegistry $substrate)
    {
        $this->commissionDirectory = $projectDir.'/var/imperium/offices/conscription/inbox';
        $this->caseDirectory = $projectDir.'/var/imperium/mastermason/activation-cases';
        $this->deliveryDirectory = $projectDir.'/var/imperium/mastermason/qualified-manifestations';
    }

    public function fulfill(string $commissionId): array
    {
        if (!preg_match('/^artificer-construction-[a-f0-9]{20}$/', $commissionId)) throw new \InvalidArgumentException('F40_ARTIFICER_COMMISSION_INVALID: exact Artificer construction commission identity is required.');
        $commission = $this->read($this->commissionDirectory.'/'.$commissionId.'.json', 'F41_ARTIFICER_COMMISSION_ABSENT');
        $member = $this->staff->member();
        $substrate = $this->substrate->current();
        if (!$this->digestMatches($commission) || $commissionId !== ($commission['commission_id'] ?? null)
            || 'imperium.construction-commission/v1' !== ($commission['schema'] ?? null) || 'mastermason' !== ($commission['issuer'] ?? null)
            || 'foundry' !== ($commission['office'] ?? null) || 'foundry.artificer' !== ($commission['target_seat'] ?? null)
            || 'ISSUED_PENDING_CONSCRIPTION' !== ($commission['status'] ?? null) || true !== ($commission['spawning_authority'] ?? null)
            || true === ($commission['foundry_construction_authority'] ?? null) || true === ($commission['seat_binding_authority'] ?? null)
            || true === ($commission['recipient_acceptance'] ?? null) || true === ($commission['execution_authority'] ?? null)
            || CanonicalJson::encode($member['persona']) !== CanonicalJson::encode($commission['persona'] ?? null)
            || CanonicalJson::encode($member['profile']) !== CanonicalJson::encode($commission['profile'] ?? null)
            || CanonicalJson::encode($member['qualification_contract']) !== CanonicalJson::encode($commission['qualification_contract'] ?? null)
            || CanonicalJson::encode($substrate) !== CanonicalJson::encode($commission['substrate'] ?? null)) {
            throw new \RuntimeException('F42_ARTIFICER_COMMISSION_INVALID: exact canonical spawning-only Artificer commission is required.');
        }
        $caseId = $commission['source_provisioning_case_id'] ?? null;
        if (!is_string($caseId)) throw new \RuntimeException('F43_ARTIFICER_PROVISIONING_CHAIN_INVALID: source provisioning case is absent.');
        $case = $this->read($this->caseDirectory.'/'.$caseId.'.json', 'F43_ARTIFICER_PROVISIONING_CHAIN_INVALID');
        if (!$this->digestMatches($case) || ($commission['source_provisioning_case_digest'] ?? null) !== ($case['record_digest'] ?? null)
            || 'CANONICAL_STAFF_READY' !== ($case['status'] ?? null) || 'foundry.artificer' !== ($case['seat'] ?? null)) {
            throw new \RuntimeException('F43_ARTIFICER_PROVISIONING_CHAIN_INVALID: commission does not bind the exact ready provisioning case.');
        }
        [$instanceId, $recruiter] = $this->ordinaryRecruiter();
        $manifestationId = $instanceId.'.officer.foundry.artificer.'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $commission['record_digest'], $member])), 0, 12);
        $qualification = [
            'disposition_id' => 'qualification-'.substr(hash('sha256', CanonicalJson::encode([$manifestationId, $commission['record_digest'], $recruiter])), 0, 20),
            'actor' => ['seat' => 'conscription.recruiter', 'manifestation_id' => $recruiter['manifestation_id'], 'occupancy_generation' => $recruiter['occupancy_generation']],
            'candidate_id' => $manifestationId, 'qualification_contract' => $member['qualification_contract'], 'disposition' => 'QUALIFIED',
            'checks' => ['exact_persona_lineage' => true, 'exact_profile_installation' => true, 'approval_and_current_active_chain' => true, 'declared_authority_restraint' => true, 'version_and_provenance_preservation' => true],
        ];
        $packet = [
            'schema' => 'imperium.qualified-manifestation-packet/v1',
            'delivery_id' => 'qualified-delivery-'.substr(hash('sha256', CanonicalJson::encode([$manifestationId, $qualification])), 0, 20),
            'source_provisioning_case_id' => $caseId,
            'commission' => ['id' => $commissionId, 'digest' => $commission['record_digest'], 'consumed' => true],
            'candidate' => ['manifestation_id' => $manifestationId, 'instance_id' => $instanceId, 'persona' => $member['persona'], 'profile' => $member['profile'],
                'substrate_instance' => ['instance_id' => $manifestationId.'.substrate', 'substrate' => $substrate, 'status' => 'PROFILE_INSTALLED'],
                'target_seat' => 'foundry.artificer', 'target_occupancy_generation' => 1, 'status' => 'QUALIFIED_UNBOUND'],
            'qualification' => $qualification, 'qualification_digest' => hash('sha256', CanonicalJson::encode($qualification)), 'sealed' => true,
            'foundry_construction_authority' => false, 'seat_binding_authority' => false, 'recipient_acceptance' => false, 'execution_authority' => false,
        ];
        return ['commission_id' => $commissionId, 'recruiter' => $recruiter, 'delivery' => $this->persist($packet)];
    }

    private function ordinaryRecruiter(): array
    {
        $bootstrap = $this->bootstrap->read();
        if (!is_array($bootstrap) || BootstrapState::CuriaReady->value !== ($bootstrap['state'] ?? null)) throw new \RuntimeException('F44_RECRUITER_UNAVAILABLE: Conscription requires CURIA_READY and the ordinary Recruiter.');
        for ($index = count($bootstrap['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $bootstrap['events'][$index];
            $recruiter = 'T04' === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null) ? ($event['output']['successor'] ?? null) : null;
            if (is_array($recruiter) && 'conscription.recruiter' === ($recruiter['seat'] ?? null) && 'ordinary-recruiter' === ($recruiter['authority'] ?? null) && 2 === ($recruiter['occupancy_generation'] ?? null)) return [$bootstrap['binding']['instance_id'] ?? null, $recruiter];
        }
        throw new \RuntimeException('F44_RECRUITER_UNAVAILABLE: ordinary Recruiter occupancy receipt is absent.');
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(array $packet): array
    {
        if (!is_dir($this->deliveryDirectory) && !mkdir($this->deliveryDirectory, 0770, true) && !is_dir($this->deliveryDirectory)) throw new \RuntimeException('Qualified-manifestation delivery directory cannot be created.');
        $packet['record_digest'] = hash('sha256', CanonicalJson::encode($packet)); $path = $this->deliveryDirectory.'/'.$packet['delivery_id'].'.json';
        if (is_file($path)) { $existing = $this->read($path, 'F45_QUALIFIED_PACKET_ABSENT'); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($packet)) throw new \RuntimeException('F46_QUALIFIED_PACKET_REPLAY_CONFLICT'); return $existing; }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($packet, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Qualified Artificer packet cannot be committed atomically.'); }
        return $packet;
    }
}
