<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Laboratorium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionProfileDerivationCommissionDispositionService
{
    private const array DISPOSITIONS = ['ACCEPTED', 'REFUSED'];

    private string $commissions;
    private string $acceptances;
    private string $decisions;
    private string $reservations;
    private string $custody;
    private string $occupancy;
    private string $dispositions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->commissions = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-derivation-commission-inbox';
        $this->acceptances = $root.'/var/imperium/offices/conscription/delegate-mission-profile-derivation-acceptances';
        $this->decisions = $root.'/var/imperium/imperator/delegate-mission-profile-scope-decisions';
        $this->reservations = $root.'/var/imperium/offices/garrison/delegate-mission-persona-reservation-dispositions';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->occupancy = $root.'/var/imperium/offices/laboratorium/occupancy';
        $this->dispositions = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-derivation-commission-dispositions';
    }

    public function decide(string $commissionRequestId, string $bindingId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-derivation-commission-request-[a-f0-9]{20}$/', $commissionRequestId)) {
            throw new \InvalidArgumentException('L510_DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_ID_INVALID');
        }
        if (!preg_match('/^laboratorium-alchemist-binding-[a-f0-9]{20}$/', $bindingId)) {
            throw new \InvalidArgumentException('L511_DELEGATE_MISSION_ALCHEMIST_BINDING_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $rationale = trim($rationale);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $rationale) {
            throw new \InvalidArgumentException('L512_DELEGATE_MISSION_PROFILE_DERIVATION_DISPOSITION_INVALID');
        }

        $commission = $this->read($this->commissions.'/'.$commissionRequestId.'.json', 'L513_DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_ABSENT');
        $acceptance = $this->source($commission, 'source_acceptance', $this->acceptances, 'imperium.conscription-delegate-mission-profile-derivation-acceptance/v1', 'acceptance_id');
        $decision = $this->source($commission, 'source_decision', $this->decisions, 'imperium.imperator-delegate-mission-profile-scope-decision/v1', 'decision_id');
        $reservation = $this->source($commission, 'source_reservation_disposition', $this->reservations, 'imperium.garrison-delegate-mission-persona-reservation-disposition/v1', 'disposition_id');
        $custodyId = $commission['custody_lease']['custody_id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'L514_DELEGATE_MISSION_PROFILE_DERIVATION_CUSTODY_ABSENT');
        $binding = $this->read($this->occupancy.'/'.$bindingId.'.json', 'L515_DELEGATE_MISSION_ALCHEMIST_UNAVAILABLE');
        $this->validate($commissionRequestId, $bindingId, $commission, $acceptance, $decision, $reservation, $custody, $binding);

        foreach (glob($this->dispositions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'L519_DELEGATE_MISSION_PROFILE_DERIVATION_DISPOSITION_CONFLICT');
            if (($prior['source_commission']['id'] ?? null) === $commissionRequestId) {
                if (($prior['source_commission']['digest'] ?? null) === $commission['record_digest']
                    && ($prior['alchemist']['binding_id'] ?? null) === $bindingId
                    && ($prior['disposition'] ?? null) === $disposition
                    && ($prior['rationale'] ?? null) === $rationale) {
                    return $prior;
                }
                throw new \RuntimeException('L519_DELEGATE_MISSION_PROFILE_DERIVATION_DISPOSITION_CONFLICT');
            }
        }

        $accepted = 'ACCEPTED' === $disposition;
        $id = 'delegate-mission-profile-derivation-commission-disposition-'.substr(hash('sha256', CanonicalJson::encode([$commissionRequestId, $commission['record_digest'], $bindingId, $binding['record_digest'], $disposition, $rationale])), 0, 20);
        $derivationAuthority = null;
        if ($accepted) {
            $derivationAuthority = [
                'authority_id' => 'delegate-mission-exact-profile-derivation-authority-'.substr(hash('sha256', CanonicalJson::encode([$id, $commission['record_digest'], $commission['profile_scope']])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => 'laboratorium.alchemist',
                'purpose' => 'DERIVE_ONE_EXACT_DELEGATE_MISSION_PROFILE_CANDIDATE',
                'profile_scope_digest' => hash('sha256', CanonicalJson::encode($commission['profile_scope'])),
                'custody_digest' => $custody['record_digest'],
                'consumed' => false,
                'continuing_authority' => false,
            ];
        }

        return $this->save($id, [
            'schema' => 'imperium.laboratorium-delegate-mission-profile-derivation-commission-disposition/v1',
            'disposition_id' => $id,
            'instance_id' => $commission['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'alchemist' => [
                'seat' => 'laboratorium.alchemist',
                'officer_class' => OfficerClass::Legate->value,
                'binding_id' => $bindingId,
                'binding_digest' => $binding['record_digest'],
                'manifestation_id' => $binding['manifestation_id'],
                'occupancy_generation' => $binding['occupancy_generation'],
            ],
            'source_commission' => ['id' => $commissionRequestId, 'digest' => $commission['record_digest']],
            'source_conscription_acceptance' => ['id' => $acceptance['acceptance_id'], 'digest' => $acceptance['record_digest']],
            'source_imperator_decision' => ['id' => $decision['decision_id'], 'digest' => $decision['record_digest']],
            'source_reservation_disposition' => ['id' => $reservation['disposition_id'], 'digest' => $reservation['record_digest']],
            'persona' => $commission['persona'],
            'custody_lease' => $commission['custody_lease'],
            'profile_scope' => $commission['profile_scope'],
            'imperator_limitations' => $commission['imperator_limitations'],
            'commission_scope' => $commission['commission_scope'],
            'return_destination' => $commission['return_destination'],
            'disposition' => $disposition,
            'rationale' => $rationale,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'laboratorium_acceptance_disposition_authority' => ['id' => $commission['laboratorium_acceptance_disposition_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'recipient_acceptance' => $accepted,
            'profile_derivation_authority' => $derivationAuthority,
            'profile_derivation_authority_exercisable' => $accepted,
            'profile_derived' => false,
            'profile_candidate_created' => false,
            'status' => $accepted ? 'DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION' : 'DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_REFUSED_NO_AUTHORITY',
            'custody_transfer_authority' => false,
            'persona_substitution_authority' => false,
            'profile_instantiation_authority' => false,
            'profile_activation_authority' => false,
            'profile_examination_authority' => false,
            'profile_approval_authority' => false,
            'profile_installation_authority' => false,
            'manifestation_assembly_authority' => false,
            'seat_binding_authority' => false,
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

    private function validate(string $commissionId, string $bindingId, array $commission, array $acceptance, array $decision, array $reservation, array $custody, array $binding): void
    {
        $authority = $commission['laboratorium_acceptance_disposition_authority'] ?? null;
        if (!$this->valid($commission) || !$this->valid($custody) || !$this->valid($binding)
            || 'imperium.conscription-laboratorium-delegate-mission-profile-derivation-commission-request/v1' !== ($commission['schema'] ?? null)
            || $commissionId !== ($commission['request_id'] ?? null)
            || OfficerClass::Delegate->value !== ($commission['officer_class'] ?? null)
            || 'laboratorium.alchemist' !== ($commission['recipient']['seat'] ?? null)
            || true !== ($commission['recipient']['acceptance_pending'] ?? null)
            || 'DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_REQUESTED_PENDING_ALCHEMIST_ACCEPTANCE' !== ($commission['status'] ?? null)
            || false !== ($commission['recipient_acceptance'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || 'laboratorium.alchemist' !== ($authority['holder'] ?? null)
            || 'DECIDE_ONE_EXACT_DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION' !== ($authority['purpose'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || true !== ($commission['profile_derivation_authority'] ?? null)
            || false !== ($commission['profile_derivation_authority_exercisable'] ?? null)
            || true === ($commission['profile_derived'] ?? null)
            || 'ACCEPTED' !== ($acceptance['disposition'] ?? null)
            || true !== ($acceptance['profile_derivation_authority']['consumed'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || 'RESERVED' !== ($reservation['disposition'] ?? null)
            || true !== ($reservation['persona_reserved'] ?? null)
            || CanonicalJson::encode($commission['profile_scope'] ?? null) !== CanonicalJson::encode($acceptance['profile_scope'] ?? null)
            || CanonicalJson::encode($commission['profile_scope'] ?? null) !== CanonicalJson::encode($decision['profile_scope'] ?? null)
            || CanonicalJson::encode($commission['persona'] ?? null) !== CanonicalJson::encode($reservation['personnel_commitment']['persona'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($commission['custody_lease']['custody_id'] ?? null) !== ($custody['custody_id'] ?? null)
            || ($commission['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)
            || 'garrison' !== ($commission['custody_lease']['custodian'] ?? null)
            || 'PROFILE_DERIVATION_ONLY_NO_CUSTODY_TRANSFER' !== ($commission['custody_lease']['scope'] ?? null)
            || !in_array($binding['schema'] ?? null, ['imperium.laboratorium-alchemist-occupancy/v1', 'imperium.operator-root-seat-occupancy/v1'], true)
            || $bindingId !== ($binding['binding_id'] ?? null)
            || 'laboratorium' !== ($binding['office'] ?? null)
            || 'laboratorium.alchemist' !== ($binding['seat'] ?? null)
            || OfficerClass::Legate->value !== ($binding['officer_class'] ?? null)
            || 'ACTIVE' !== ($binding['status'] ?? null)
            || true !== ($binding['binding_atomic'] ?? null)
            || ($commission['instance_id'] ?? null) !== ($binding['instance_id'] ?? null)
            || true !== ($binding['profile_derivation_commission_acceptance_authority'] ?? null)
            || true === ($binding['execution_authority'] ?? null)
            || true !== ($commission['sealed'] ?? null)
            || true !== ($acceptance['sealed'] ?? null)
            || true !== ($decision['sealed'] ?? null)
            || true !== ($reservation['sealed'] ?? null)) {
            throw new \RuntimeException('L516_DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_INVALID');
        }
    }

    private function source(array $record, string $field, string $directory, string $schema, string $idField): array
    {
        $source = $record[$field] ?? null;
        if (!is_array($source) || !is_string($source['id'] ?? null) || !is_string($source['digest'] ?? null)) {
            throw new \RuntimeException('L516_DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_INVALID');
        }
        $result = $this->read($directory.'/'.$source['id'].'.json', 'L516_DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_INVALID');
        if (!$this->valid($result) || ($result['record_digest'] ?? null) !== $source['digest'] || ($result['schema'] ?? null) !== $schema || ($result[$idField] ?? null) !== $source['id']) {
            throw new \RuntimeException('L516_DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_INVALID');
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
        if (!is_dir($this->dispositions) && !mkdir($this->dispositions, 0770, true) && !is_dir($this->dispositions)) {
            throw new \RuntimeException('L517_DELEGATE_MISSION_PROFILE_DERIVATION_DISPOSITION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->dispositions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'L519_DELEGATE_MISSION_PROFILE_DERIVATION_DISPOSITION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('L519_DELEGATE_MISSION_PROFILE_DERIVATION_DISPOSITION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('L517_DELEGATE_MISSION_PROFILE_DERIVATION_DISPOSITION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
