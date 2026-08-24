<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionProfileDerivationCommissionRequestService
{
    private const array DISPOSITIONS = ['ACCEPTED', 'REFUSED'];

    private string $decisions;
    private string $requests;
    private string $reservations;
    private string $custody;
    private string $acceptances;
    private string $commissionInbox;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private StateStore $bootstrap)
    {
        $this->decisions = $root.'/var/imperium/imperator/delegate-mission-profile-scope-decisions';
        $this->requests = $root.'/var/imperium/curia/delegate-mission-profile-scope-authorization-requests';
        $this->reservations = $root.'/var/imperium/offices/garrison/delegate-mission-persona-reservation-dispositions';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->acceptances = $root.'/var/imperium/offices/conscription/delegate-mission-profile-derivation-acceptances';
        $this->commissionInbox = $root.'/var/imperium/offices/laboratorium/delegate-mission-profile-derivation-commission-inbox';
    }

    public function decide(string $decisionId, string $disposition, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^delegate-mission-profile-scope-decision-[a-f0-9]{20}$/', $decisionId)) {
            throw new \InvalidArgumentException('R510_DELEGATE_MISSION_PROFILE_SCOPE_DECISION_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $rationale = trim($rationale);
        if (!in_array($disposition, self::DISPOSITIONS, true) || '' === $rationale) {
            throw new \InvalidArgumentException('R511_DELEGATE_MISSION_PROFILE_DERIVATION_ACCEPTANCE_DISPOSITION_INVALID');
        }

        $decision = $this->read($this->decisions.'/'.$decisionId.'.json', 'R512_DELEGATE_MISSION_PROFILE_SCOPE_DECISION_ABSENT');
        $request = $this->source($decision, 'source_request', $this->requests, 'imperium.curia-delegate-mission-profile-scope-authorization-request/v1', 'request_id');
        $reservation = $this->source($decision, 'source_reservation_disposition', $this->reservations, 'imperium.garrison-delegate-mission-persona-reservation-disposition/v1', 'disposition_id');
        $custodyId = $reservation['custody']['id'] ?? '';
        $custody = $this->read($this->custody.'/'.$custodyId.'.json', 'R513_DELEGATE_MISSION_CUSTODY_ABSENT');
        [$instanceId, $recruiter] = $this->ordinaryRecruiter();
        $this->validate($decisionId, $decision, $request, $reservation, $custody, $instanceId);

        foreach (glob($this->acceptances.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'R518_DELEGATE_MISSION_PROFILE_DERIVATION_ACCEPTANCE_CONFLICT');
            if (($prior['source_decision']['id'] ?? null) === $decisionId) {
                if (($prior['source_decision']['digest'] ?? null) !== $decision['record_digest'] || ($prior['disposition'] ?? null) !== $disposition || ($prior['rationale'] ?? null) !== $rationale) {
                    throw new \RuntimeException('R518_DELEGATE_MISSION_PROFILE_DERIVATION_ACCEPTANCE_CONFLICT');
                }
                $commission = 'ACCEPTED' === $disposition ? $this->read($this->commissionInbox.'/'.$prior['commission_request']['id'].'.json', 'R519_DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_CONFLICT') : null;

                return ['acceptance' => $prior, 'commission_request' => $commission];
            }
        }

        $actor = [
            'seat' => 'conscription.recruiter',
            'officer_class' => OfficerClass::Legate->value,
            'manifestation_id' => $recruiter['manifestation_id'],
            'occupancy_generation' => $recruiter['occupancy_generation'],
        ];
        $accepted = 'ACCEPTED' === $disposition;
        $acceptanceId = 'delegate-mission-profile-derivation-acceptance-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $decision['record_digest'], $actor, $disposition, $rationale])), 0, 20);
        $commissionId = $accepted ? 'delegate-mission-profile-derivation-commission-request-'.substr(hash('sha256', CanonicalJson::encode([$acceptanceId, $decision['profile_scope'], $custody['record_digest']])), 0, 20) : null;
        $acceptance = $this->save($this->acceptances, $acceptanceId, [
            'schema' => 'imperium.conscription-delegate-mission-profile-derivation-acceptance/v1',
            'acceptance_id' => $acceptanceId,
            'instance_id' => $instanceId,
            'officer_class' => OfficerClass::Delegate->value,
            'actor' => $actor,
            'source_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']],
            'source_request' => ['id' => $request['request_id'], 'digest' => $request['record_digest']],
            'source_reservation_disposition' => ['id' => $reservation['disposition_id'], 'digest' => $reservation['record_digest']],
            'profile_scope' => $decision['profile_scope'],
            'imperator_limitations' => $decision['limitations'],
            'disposition' => $disposition,
            'rationale' => $rationale,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'profile_derivation_authority' => ['id' => $decision['profile_derivation_authority']['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'commission_request' => null === $commissionId ? null : ['id' => $commissionId, 'recipient' => 'laboratorium.alchemist', 'issued' => true],
            'status' => $accepted ? 'DELEGATE_MISSION_PROFILE_DERIVATION_ACCEPTED_COMMISSION_REQUESTED_PENDING_ALCHEMIST_ACCEPTANCE' : 'DELEGATE_MISSION_PROFILE_DERIVATION_AUTHORIZATION_REFUSED_BY_CONSCRIPTION_NO_AUTHORITY',
            'profile_derived' => false,
            'laboratorium_acceptance_authority' => false,
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
        ], 'R516_DELEGATE_MISSION_PROFILE_DERIVATION_ACCEPTANCE_PERSISTENCE_FAILED', 'R518_DELEGATE_MISSION_PROFILE_DERIVATION_ACCEPTANCE_CONFLICT');

        if (!$accepted) {
            return ['acceptance' => $acceptance, 'commission_request' => null];
        }

        $commission = $this->save($this->commissionInbox, $commissionId, [
            'schema' => 'imperium.conscription-laboratorium-delegate-mission-profile-derivation-commission-request/v1',
            'request_id' => $commissionId,
            'instance_id' => $instanceId,
            'officer_class' => OfficerClass::Delegate->value,
            'issuer' => $actor,
            'recipient' => ['office' => 'laboratorium', 'seat' => 'laboratorium.alchemist', 'acceptance_pending' => true],
            'source_acceptance' => ['id' => $acceptanceId, 'digest' => $acceptance['record_digest']],
            'source_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']],
            'source_request' => ['id' => $request['request_id'], 'digest' => $request['record_digest']],
            'source_reservation_disposition' => ['id' => $reservation['disposition_id'], 'digest' => $reservation['record_digest']],
            'persona' => $decision['profile_scope']['persona'],
            'custody_lease' => [
                'custody_id' => $custody['custody_id'],
                'custody_digest' => $custody['record_digest'],
                'custody_state' => 'ADMITTED_HELD',
                'custodian' => 'garrison',
                'scope' => 'PROFILE_DERIVATION_ONLY_NO_CUSTODY_TRANSFER',
            ],
            'profile_scope' => $decision['profile_scope'],
            'imperator_limitations' => $decision['limitations'],
            'commission_scope' => 'DERIVE_ONE_EXACT_DELEGATE_MISSION_PROFILE',
            'return_destination' => ['office' => 'conscription', 'seat' => 'conscription.recruiter'],
            'status' => 'DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_REQUESTED_PENDING_ALCHEMIST_ACCEPTANCE',
            'recipient_acceptance' => false,
            'laboratorium_acceptance_disposition_authority' => [
                'authority_id' => 'delegate-mission-laboratorium-acceptance-disposition-authority-'.substr(hash('sha256', CanonicalJson::encode([$commissionId, $acceptance['record_digest'], $decision['profile_scope']])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => 'laboratorium.alchemist',
                'purpose' => 'DECIDE_ONE_EXACT_DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION',
                'consumed' => false,
                'continuing_authority' => false,
            ],
            'profile_derivation_authority' => true,
            'profile_derivation_authority_exercisable' => false,
            'profile_derived' => false,
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
        ], 'R517_DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_PERSISTENCE_FAILED', 'R519_DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_CONFLICT');

        return ['acceptance' => $acceptance, 'commission_request' => $commission];
    }

    private function validate(string $id, array $decision, array $request, array $reservation, array $custody, string $instanceId): void
    {
        $authority = $decision['profile_derivation_authority'] ?? null;
        if (!$this->valid($decision) || !$this->valid($custody)
            || 'imperium.imperator-delegate-mission-profile-scope-decision/v1' !== ($decision['schema'] ?? null)
            || $id !== ($decision['decision_id'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || 'DELEGATE_MISSION_PROFILE_DERIVATION_AUTHORIZED_PENDING_CONSCRIPTION_ACCEPTANCE' !== ($decision['status'] ?? null)
            || true !== ($decision['profile_derivation_authorized'] ?? null)
            || true !== ($decision['profile_derivation_authority_exercisable'] ?? null)
            || !is_array($authority)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || 'conscription.recruiter' !== ($authority['holder'] ?? null)
            || 'ACCEPT_AND_COMMISSION_DERIVATION_OF_ONE_EXACT_DELEGATE_MISSION_PROFILE' !== ($authority['purpose'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || hash('sha256', CanonicalJson::encode($decision['profile_scope'] ?? null)) !== ($authority['profile_scope_digest'] ?? null)
            || ($decision['instance_id'] ?? null) !== $instanceId
            || ($request['instance_id'] ?? null) !== $instanceId
            || ($reservation['instance_id'] ?? null) !== $instanceId
            || 'RESERVED' !== ($reservation['disposition'] ?? null)
            || true !== ($reservation['persona_reserved'] ?? null)
            || CanonicalJson::encode($decision['profile_scope']['persona'] ?? null) !== CanonicalJson::encode($reservation['personnel_commitment']['persona'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($custody['custody_id'] ?? null) !== ($reservation['custody']['id'] ?? null)
            || ($custody['record_digest'] ?? null) !== ($reservation['custody']['digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null)
            || true === ($decision['execution_authority'] ?? null)
            || true !== ($decision['sealed'] ?? null)
            || true !== ($request['sealed'] ?? null)
            || true !== ($reservation['sealed'] ?? null)) {
            throw new \RuntimeException('R514_DELEGATE_MISSION_PROFILE_DERIVATION_CHAIN_INVALID');
        }
    }

    private function ordinaryRecruiter(): array
    {
        $state = $this->bootstrap->read();
        if (!is_array($state) || BootstrapState::CuriaReady->value !== ($state['state'] ?? null)) {
            throw new \RuntimeException('R515_DELEGATE_MISSION_RECRUITER_UNAVAILABLE');
        }
        for ($index = count($state['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $state['events'][$index];
            $recruiter = 'T04' === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null) ? ($event['output']['successor'] ?? null) : null;
            if (is_array($recruiter)
                && 'conscription.recruiter' === ($recruiter['seat'] ?? null)
                && 'ordinary-recruiter' === ($recruiter['authority'] ?? null)
                && 2 === ($recruiter['occupancy_generation'] ?? null)
                && is_string($recruiter['manifestation_id'] ?? null)) {
                return [$state['binding']['instance_id'], $recruiter];
            }
        }
        throw new \RuntimeException('R515_DELEGATE_MISSION_RECRUITER_UNAVAILABLE');
    }

    private function source(array $record, string $field, string $directory, string $schema, string $idField): array
    {
        $source = $record[$field] ?? null;
        if (!is_array($source) || !is_string($source['id'] ?? null) || !is_string($source['digest'] ?? null)) {
            throw new \RuntimeException('R514_DELEGATE_MISSION_PROFILE_DERIVATION_CHAIN_INVALID');
        }
        $result = $this->read($directory.'/'.$source['id'].'.json', 'R514_DELEGATE_MISSION_PROFILE_DERIVATION_CHAIN_INVALID');
        if (!$this->valid($result) || ($result['record_digest'] ?? null) !== $source['digest'] || ($result['schema'] ?? null) !== $schema || ($result[$idField] ?? null) !== $source['id']) {
            throw new \RuntimeException('R514_DELEGATE_MISSION_PROFILE_DERIVATION_CHAIN_INVALID');
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

    private function save(string $directory, string $id, array $record, string $failure, string $conflict): array
    {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException($failure);
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $directory.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, $conflict);
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException($conflict);
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException($failure);
        }

        return $record;
    }
}
