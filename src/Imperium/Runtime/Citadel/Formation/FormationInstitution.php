<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Resolve existing institutional incumbents; this adapter never installs one. */
final readonly class FormationInstitution
{
    public const SEATS = ['garrison' => 'garrison.constable', 'guildhall' => 'guildhall.guildmaster',
        'laboratorium' => 'laboratorium.alchemist', 'senate' => 'senate.lord-speaker',
        'conscription' => 'conscription.recruiter', 'senate-consistency' => 'senate.committee.consistency',
        'senate-governance' => 'senate.committee.governance', 'senate-practice' => 'senate.committee.practice',
        'senate-security' => 'senate.committee.security'];

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root) {}

    public function actor(string $role): array
    {
        return $this->witness($role)['actor'];
    }

    /** Retain exact public provenance when publication observes current incumbency. */
    public function witness(string $role): array
    {
        $seat = self::SEATS[$role] ?? throw new \RuntimeException('CMF120_INSTITUTION_UNSUPPORTED');
        $validator = new RecordReferenceValidator($this->root);
        $matches = [];
        $office = explode('.', $seat)[0];
        foreach (glob($this->root.'/var/imperium/offices/'.$office.'/occupancy/*.json') ?: [] as $path) {
            $record = $validator->requireIntact($validator->read($path, 'CMF121_INSTITUTION_UNAVAILABLE'), 'CMF122_INSTITUTION_CHAIN_INVALID');
            if (($record['seat'] ?? null) !== $seat || ($record['status'] ?? null) !== 'ACTIVE') { continue; }
            $id = $record['source_installation_id'] ?? '';
            if (!preg_match('/^operator-root-installation-[a-f0-9]{20}$/D', $id)) {
                throw new \RuntimeException('CMF123_GOVERNED_SUCCESSOR_ADAPTER_REQUIRED');
            }
            $source = $validator->requireIntact($validator->read($this->root.'/var/imperium/operator-root/installations/'.$id.'.json', 'CMF121_INSTITUTION_UNAVAILABLE'), 'CMF122_INSTITUTION_CHAIN_INVALID');
            $binding = $record;
            unset($binding['record_digest'], $binding['source_installation_digest']);
            if (($source['record_digest'] ?? null) !== ($record['source_installation_digest'] ?? null)
                || FormationJournal::digest($binding) !== FormationJournal::digest($source['binding'] ?? [])
                || ($source['schema'] ?? null) !== 'imperium.operator-root-personnel-installation-record/v2'
                || ($source['provenance'] ?? null) !== 'OPERATOR_ROOT_INSTALLATION'
                || ($source['instance_id'] ?? null) !== ($record['instance_id'] ?? null)) {
                throw new \RuntimeException('CMF122_INSTITUTION_CHAIN_INVALID');
            }
            $actor = ['instance_id' => $record['instance_id'], 'seat' => $seat,
                'manifestation_id' => $record['manifestation_id'], 'occupancy_generation' => $record['occupancy_generation'],
                'binding_id' => $record['binding_id'], 'binding_digest' => $record['record_digest'],
                'installation_digest' => $source['record_digest']];
            $matches[] = ['actor' => $actor, 'occupancy' => $record, 'installation' => $source];
        }
        if (count($matches) !== 1) { throw new \RuntimeException('CMF121_INSTITUTION_UNAVAILABLE'); }
        return $matches[0];
    }
}
