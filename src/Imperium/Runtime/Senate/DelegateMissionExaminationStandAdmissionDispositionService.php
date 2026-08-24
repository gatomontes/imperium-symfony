<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionExaminationStandAdmissionDispositionService
{
    private const array DISPOSITIONS = ['ADMITTED', 'REFUSED'];

    private string $deliveries;
    private string $candidates;
    private string $custody;
    private string $occupancy;
    private string $admissions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->deliveries = $root.'/var/imperium/offices/senate/delegate-mission-examination-manifestation-intake';
        $this->candidates = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-candidates';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->occupancy = $root.'/var/imperium/offices/senate/occupancy';
        $this->admissions = $root.'/var/imperium/offices/senate/delegate-mission-examination-stand-admission-dispositions';
    }

    public function decide(string $deliveryId, string $bindingId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^delegate-mission-examination-manifestation-delivery-[a-f0-9]{20}$/', $deliveryId)) {
            throw new \InvalidArgumentException('S520_DELEGATE_MISSION_EXAMINATION_DELIVERY_ID_INVALID');
        }
        if (!preg_match('/^senate-bailiff-binding-[a-f0-9]{20}$/', $bindingId)) {
            throw new \InvalidArgumentException('S521_DELEGATE_MISSION_BAILIFF_BINDING_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $rationale = trim($rationale);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $rationale) {
            throw new \InvalidArgumentException('S522_DELEGATE_MISSION_STAND_ADMISSION_DISPOSITION_INVALID');
        }

        $delivery = $this->read($this->deliveries.'/'.$deliveryId.'.json', 'S523_DELEGATE_MISSION_EXAMINATION_DELIVERY_ABSENT');
        $candidate = $this->source($delivery, 'source_profile_candidate', $this->candidates, 'imperium.laboratorium-delegate-mission-profile-candidate/v1', 'candidate_id');
        $custodyId = $delivery['custody_lease']['custody_id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'S524_DELEGATE_MISSION_EXAMINATION_CUSTODY_ABSENT');
        $binding = $this->read($this->occupancy.'/'.$bindingId.'.json', 'S525_DELEGATE_MISSION_BAILIFF_UNAVAILABLE');
        $this->validate($deliveryId, $bindingId, $delivery, $candidate, $custody, $binding);

        foreach (glob($this->admissions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'S529_DELEGATE_MISSION_STAND_ADMISSION_CONFLICT');
            if (($prior['source_delivery']['id'] ?? null) === $deliveryId) {
                if (($prior['source_delivery']['digest'] ?? null) === $delivery['record_digest']
                    && ($prior['bailiff']['binding_id'] ?? null) === $bindingId
                    && ($prior['disposition'] ?? null) === $disposition
                    && ($prior['rationale'] ?? null) === $rationale) {
                    return $prior;
                }
                throw new \RuntimeException('S529_DELEGATE_MISSION_STAND_ADMISSION_CONFLICT');
            }
        }

        $admitted = 'ADMITTED' === $disposition;
        $id = 'delegate-mission-examination-stand-admission-disposition-'.substr(hash('sha256', CanonicalJson::encode([$deliveryId, $delivery['record_digest'], $bindingId, $binding['record_digest'], $disposition, $rationale])), 0, 20);
        $openingAuthority = null;
        if ($admitted) {
            $openingAuthority = [
                'authority_id' => 'delegate-mission-senate-examination-opening-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $delivery['manifestation']['manifestation_id'], $candidate['record_digest']])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => 'senate.lord-speaker',
                'purpose' => 'OPEN_ONE_BOUNDED_DELEGATE_PROFILE_EXAMINATION',
                'manifestation_id' => $delivery['manifestation']['manifestation_id'],
                'candidate_digest' => $candidate['record_digest'],
                'consumed' => false,
                'continuing_authority' => false,
            ];
        }

        return $this->save($id, [
            'schema' => 'imperium.senate-delegate-mission-examination-stand-admission-disposition/v1',
            'disposition_id' => $id,
            'instance_id' => $delivery['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'bailiff' => [
                'seat' => 'senate.bailiff',
                'officer_class' => OfficerClass::Legate->value,
                'binding_id' => $bindingId,
                'binding_digest' => $binding['record_digest'],
                'manifestation_id' => $binding['manifestation_id'],
                'occupancy_generation' => $binding['occupancy_generation'],
            ],
            'source_delivery' => ['id' => $deliveryId, 'digest' => $delivery['record_digest']],
            'source_authorization' => $delivery['source_authorization'],
            'source_profile_candidate' => ['id' => $candidate['candidate_id'], 'digest' => $candidate['record_digest']],
            'source_reservation_disposition' => $delivery['source_reservation_disposition'],
            'custody_lease' => $delivery['custody_lease'],
            'manifestation' => $delivery['manifestation'],
            'stand' => 'senate.stand',
            'disposition' => $disposition,
            'rationale' => $rationale,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'senate_stand_intake_disposition_authority' => ['id' => $delivery['senate_stand_intake_disposition_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'stand_admission' => $admitted,
            'proceeding_security_active' => $admitted,
            'senate_examination_opening_authority' => $openingAuthority,
            'status' => $admitted ? 'DELEGATE_MISSION_EXAMINATION_MANIFESTATION_ADMITTED_SECURED_PENDING_EXAMINATION_OPENING' : 'DELEGATE_MISSION_EXAMINATION_MANIFESTATION_REFUSED_AT_STAND_NO_AUTHORITY',
            'examination_opened' => false,
            'senate_examination_authority' => false,
            'examination_cognition_authority' => false,
            'testimony_authority' => false,
            'findings_authority' => false,
            'profile_approval_authority' => false,
            'profile_activation_authority' => false,
            'profile_installation_authority' => false,
            'operational_profile_installation_authority' => false,
            'manifestation_assembly_authority' => false,
            'seat_binding_authority' => false,
            'mission_seat_binding_authority' => false,
            'deployment_authority' => false,
            'operational_use_authority' => false,
            'cognition_authority' => false,
            'provider_invocation_authority' => false,
            'data_access_authority' => false,
            'tool_use_authority' => false,
            'credential_use_authority' => false,
            'perimeter_crossing_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'mission_plan_amendment_authority' => false,
            'follow_up_commission_authority' => false,
            'continuing_turn_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validate(string $deliveryId, string $bindingId, array $delivery, array $candidate, array $custody, array $binding): void
    {
        $authority = $delivery['senate_stand_intake_disposition_authority'] ?? null;
        $manifestation = $delivery['manifestation'] ?? null;
        if (!$this->valid($delivery) || !$this->valid($custody) || !$this->valid($binding)
            || 'imperium.conscription-senate-delegate-mission-examination-manifestation-delivery/v1' !== ($delivery['schema'] ?? null)
            || $deliveryId !== ($delivery['delivery_id'] ?? null)
            || 'senate.bailiff' !== ($delivery['recipient']['seat'] ?? null)
            || 'senate.stand.intake' !== ($delivery['recipient']['surface'] ?? null)
            || true !== ($delivery['recipient']['acceptance_pending'] ?? null)
            || 'DELEGATE_MISSION_EXAMINATION_MANIFESTATION_ASSEMBLED_DELIVERED_PENDING_SENATE_STAND_INTAKE' !== ($delivery['status'] ?? null)
            || true === ($delivery['senate_stand_accepted'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || 'senate.bailiff' !== ($authority['holder'] ?? null)
            || 'DECIDE_INTAKE_OF_ONE_EXAMINATION_ONLY_DELEGATE_MANIFESTATION' !== ($authority['purpose'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || true !== ($delivery['examination_profile_installed'] ?? null)
            || true !== ($delivery['examination_manifestation_assembled'] ?? null)
            || true !== ($delivery['examination_manifestation_delivered'] ?? null)
            || !is_array($manifestation)
            || OfficerClass::Delegate->value !== ($manifestation['officer_class'] ?? null)
            || 'EXAMINATION_ONLY' !== ($manifestation['profile']['installation_class'] ?? null)
            || ($manifestation['profile']['candidate_digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || 'SENATE_EXAMINATION_ONLY' !== ($manifestation['purpose'] ?? null)
            || 'senate.stand' !== ($manifestation['target'] ?? null)
            || false !== ($manifestation['mission_seat_bound'] ?? null)
            || false !== ($manifestation['operational_use_permitted'] ?? null)
            || false !== ($manifestation['cognition_permitted'] ?? null)
            || false !== ($manifestation['credentials_granted'] ?? null)
            || false !== ($manifestation['tool_access_granted'] ?? null)
            || false !== ($manifestation['data_access_granted'] ?? null)
            || false !== ($manifestation['external_action_authority'] ?? null)
            || false !== ($manifestation['execution_authority'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($delivery['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null)
            || ($delivery['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)
            || true !== ($custody['available'] ?? null)
            || !in_array($binding['schema'] ?? null, ['imperium.senate-bailiff-occupancy/v1', 'imperium.operator-root-seat-occupancy/v1'], true)
            || $bindingId !== ($binding['binding_id'] ?? null)
            || 'senate' !== ($binding['office'] ?? null)
            || 'senate.bailiff' !== ($binding['seat'] ?? null)
            || OfficerClass::Legate->value !== ($binding['officer_class'] ?? null)
            || 'ACTIVE' !== ($binding['status'] ?? null)
            || true !== ($binding['binding_atomic'] ?? null)
            || ($delivery['instance_id'] ?? null) !== ($binding['instance_id'] ?? null)
            || true !== ($binding['delegate_examination_stand_intake_disposition_authority'] ?? null)
            || true !== ($binding['proceeding_security_authority'] ?? null)
            || true === ($binding['execution_authority'] ?? null)
            || true === ($delivery['senate_examination_authority'] ?? null)
            || true === ($delivery['execution_authority'] ?? null)
            || true !== ($delivery['sealed'] ?? null)
            || true !== ($candidate['sealed'] ?? null)) {
            throw new \RuntimeException('S526_DELEGATE_MISSION_EXAMINATION_STAND_INTAKE_INVALID');
        }
    }

    private function source(array $record, string $field, string $directory, string $schema, string $idField): array
    {
        $source = $record[$field] ?? null;
        if (!is_array($source) || !is_string($source['id'] ?? null) || !is_string($source['digest'] ?? null)) {
            throw new \RuntimeException('S526_DELEGATE_MISSION_EXAMINATION_STAND_INTAKE_INVALID');
        }
        $result = $this->read($directory.'/'.$source['id'].'.json', 'S526_DELEGATE_MISSION_EXAMINATION_STAND_INTAKE_INVALID');
        if (!$this->valid($result) || ($result['record_digest'] ?? null) !== $source['digest'] || ($result['schema'] ?? null) !== $schema || ($result[$idField] ?? null) !== $source['id']) {
            throw new \RuntimeException('S526_DELEGATE_MISSION_EXAMINATION_STAND_INTAKE_INVALID');
        }

        return $result;
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function valid(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function save(string $id, array $record): array
    {
        if (!is_dir($this->admissions) && !mkdir($this->admissions, 0770, true) && !is_dir($this->admissions)) {
            throw new \RuntimeException('S527_DELEGATE_MISSION_STAND_ADMISSION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->admissions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'S529_DELEGATE_MISSION_STAND_ADMISSION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('S529_DELEGATE_MISSION_STAND_ADMISSION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('S527_DELEGATE_MISSION_STAND_ADMISSION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
