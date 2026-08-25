<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\{BootstrapState,CanonicalJson,StateStore};

use App\Imperium\Runtime\Identity\OfficerClass;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionOperationalManifestationAssemblyService
{
    private string $q;
    private string $c;
    private string $a;
    private DelegateMissionOperationalTransitionCoordinator $t;
    public function __construct(#[Autowire('%kernel.project_dir%')]string $root,
    private StateStore $b,
    ?DelegateMissionOperationalTransitionCoordinator $coordinator = null) {
        $this->q = $root.'/var/imperium/offices/conscription/delegate-mission-operational-profile-qualifications';
        $this->c = $root.'/var/imperium/offices/garrison/custody';
        $this->a = $root.'/var/imperium/offices/conscription/delegate-mission-operational-manifestation-assemblies';
        $this->t = $coordinator ?? new DelegateMissionOperationalTransitionCoordinator($root);
    }
    public function assemble(string $id,
    \DateTimeImmutable $at): array {
        if (!preg_match('/^delegate-mission-operational-profile-qualification-[a-f0-9]{20}$/',
        $id)) throw new \InvalidArgumentException('R250_DELEGATE_MISSION_QUALIFICATION_ID_INVALID');
        $q = $this->read($this->q.'/'.$id.'.json',
        'R251_DELEGATE_MISSION_QUALIFICATION_ABSENT');
        foreach (glob($this->a.'/*.json') ? :[] as $p) {
            $x = $this->read($p,
            'R259_DELEGATE_MISSION_ASSEMBLY_CONFLICT');
            if (($x['source_qualification']['id'] ?? null) === $id) return $this->t->recordAssembly($x);
        }
        $c = $this->read($this->c.'/'.($q['custody_lease']['custody_id'] ?? '').'.json',
        'R252_DELEGATE_MISSION_CUSTODY_ABSENT');
        [$instance,
        $r] = $this->recruiter();
        $auth = $q['manifestation_assembly_authority'] ?? [];
        if (!$this->ok($q) ||
        !$this->ok($c) ||
        'imperium.conscription-delegate-mission-operational-profile-qualification/v1' !== ($q['schema'] ?? null) ||
        $instance !== ($q['instance_id'] ?? null) ||
        'DELEGATE_MISSION_PROFILE_OPERATIONALLY_QUALIFIED_PENDING_MANIFESTATION_ASSEMBLY' !== ($q['status'] ?? null) ||
        true !== ($q['profile_installed'] ?? null) ||
        true !== ($q['profile_operationally_qualified'] ?? null) ||
        true !== ($auth['authority_single_use'] ?? null) ||
        true !== ($auth['authority_exercisable'] ?? null) ||
        false !== ($auth['consumed'] ?? null) ||
        ($q['custody_lease']['custody_digest'] ?? null) !== $c['record_digest'] ||
        'ADMITTED_HELD' !== ($c['custody_state'] ?? null) ||
        true !== ($c['available'] ?? null) ||
        true === ($q['mission_seat_binding_authority'] ?? null) ||
        true === ($q['deployment_authority'] ?? null)) throw new \RuntimeException('R253_DELEGATE_MISSION_ASSEMBLY_CHAIN_INVALID');
        $actor = ['seat' => 'conscription.recruiter',
        'manifestation_id' => $r['manifestation_id'],
        'occupancy_generation' => $r['occupancy_generation']];
        $mid = 'delegate-mission-operational-manifestation-'.substr(hash('sha256',
        CanonicalJson::encode([$id,
        $q['record_digest'],
        $q['persona'],
        $q['operational_profile']])),
        0,
        20);
        $m = ['manifestation_id' => $mid,
        'instance_id' => $instance,
        'officer_class' => OfficerClass::Delegate->value,
        'persona' => $q['persona'],
        'profile' => $q['operational_profile'],
        'substrate' => $q['operational_profile']['substrate_contract'],
        'intended_seat' => $q['operational_profile']['intended_seat'],
        'custody_lease' => $q['custody_lease'],
        'status' => 'ASSEMBLED_UNBOUND',
        'seat_bound' => false,
        'operational_use_permitted' => false,
        'tool_access_granted' => false,
        'credentials_granted' => false,
        'perimeter_crossing_authority' => false,
        'external_action_authority' => false,
        'deployment_authority' => false,
        'custody_transfer_authority' => false,
        'execution_authority' => false];
        $bind = 'delegate-mission-seat-binding-authority-'.substr(hash('sha256',
        CanonicalJson::encode([$mid,
        $q['record_digest']])),
        0,
        20);
        $aid = 'delegate-mission-operational-manifestation-assembly-'.substr(hash('sha256',
        CanonicalJson::encode([$id,
        $q['record_digest'],
        $actor,
        $m])),
        0,
        20);
        $record = $this->save($aid,
        ['schema' => 'imperium.conscription-delegate-mission-operational-manifestation-assembly/v1',
        'assembly_id' => $aid,
        'instance_id' => $instance,
        'officer_class' => OfficerClass::Delegate->value,
        'assembler' => $actor,
        'source_qualification' => ['id' => $id,
        'digest' => $q['record_digest']],
        'source_imperator_approval' => $q['source_imperator_approval'],
        'source_senate_disposition' => $q['source_senate_disposition'],
        'source_profile_candidate' => $q['source_profile_candidate'],
        'persona' => $q['persona'],
        'custody_lease' => $q['custody_lease'],
        'manifestation' => $m,
        'manifestation_assembly_authority' => ['id' => $auth['authority_id'],
        'consumed' => true,
        'continuing_authority' => false],
        'assembled_at' => $at->format(DATE_ATOM),
        'status' => 'DELEGATE_MISSION_OPERATIONAL_MANIFESTATION_ASSEMBLED_PENDING_MISSION_SEAT_BINDING',
        'operational_manifestation_assembled' => true,
        'mission_seat_binding_authority' => ['authority_id' => $bind,
        'authority_single_use' => true,
        'authority_exercisable' => true,
        'consumed' => false,
        'continuing_authority' => false],
        'seat_bound' => false,
        'deployment_authority' => false,
        'custody_transfer_authority' => false,
        'operational_use_authority' => false,
        'tool_use_authority' => false,
        'credential_use_authority' => false,
        'perimeter_crossing_authority' => false,
        'external_action_authority' => false,
        'execution_authority' => false,
        'sealed' => true]);
        return $record;
    }
    private function recruiter(): array
    {
        $s = $this->b->read();
        for ($i = count($s['events'] ?? [])-1;$i >= 0;--$i) {
            $r = 'T04' === ($s['events'][$i]['transition'] ?? null) &&
            'SUCCESS' === ($s['events'][$i]['result'] ?? null) ? ($s['events'][$i]['output']['successor'] ?? null) : null;
            if (is_array($r) &&
            'conscription.recruiter' === ($r['seat'] ?? null)) return[(string)$s['binding']['instance_id'],
            $r];
        }
        throw new \RuntimeException('R254_DELEGATE_MISSION_RECRUITER_UNAVAILABLE');
    }
    private function read($p,
    $e): array {
        if (!is_file($p)) throw new \RuntimeException($e);
        return json_decode((string)file_get_contents($p),
        true,
        512,
        JSON_THROW_ON_ERROR);
    }
    private function ok(array $r): bool
    {
        $d = $r['record_digest'] ?? null;
        unset($r['record_digest']);
        return is_string($d) &&
        hash_equals($d,
        hash('sha256',
        CanonicalJson::encode($r)));
    }
    private function save($id,
    array $r): array {
        return $this->t->commitAssembly($id,
        $r);
    }
}
