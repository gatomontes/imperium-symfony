<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileDerivationAuthorizationAcceptanceService
{
    private string $decisionDirectory;
    private string $requestDirectory;
    private string $reservationDirectory;
    private string $acceptanceDirectory;
    private string $handoffDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir, private StateStore $bootstrap)
    {
        $this->decisionDirectory = $projectDir.'/var/imperium/curia/profile-derivation-authorization-decisions';
        $this->requestDirectory = $projectDir.'/var/imperium/curia/profile-derivation-authorization-requests';
        $this->reservationDirectory = $projectDir.'/var/imperium/offices/garrison/persona-reservation-dispositions';
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/conscription/profile-derivation-authorization-acceptances';
        $this->handoffDirectory = $projectDir.'/var/imperium/offices/garrison/profile-derivation-handoff-inbox';
    }

    public function accept(string $actId): array
    {
        if (!preg_match('/^profile-derivation-decision-[a-f0-9]{20}$/', $actId)) throw new \InvalidArgumentException('R72_PROFILE_DERIVATION_ACT_ID_INVALID');
        $act = $this->read($this->decisionDirectory.'/'.$actId.'.json', 'R73_PROFILE_DERIVATION_ACT_ABSENT');
        $requestId = $act['source_request_id'] ?? null;
        $request = is_string($requestId) ? $this->read($this->requestDirectory.'/'.$requestId.'.json', 'R74_PROFILE_DERIVATION_REQUEST_ABSENT') : [];
        $reservationRef = $act['source_reservation_disposition'] ?? null;
        $reservationId = is_array($reservationRef) ? ($reservationRef['id'] ?? null) : null;
        $reservation = is_string($reservationId) ? $this->read($this->reservationDirectory.'/'.$reservationId.'.json', 'R75_PROFILE_DERIVATION_RESERVATION_ABSENT') : [];

        $this->validateChain($actId, $act, $request, $reservation);
        [$instanceId, $recruiter] = $this->ordinaryRecruiter();
        if ($instanceId !== $act['instance_id']) throw new \RuntimeException('R77_PROFILE_DERIVATION_INSTANCE_MISMATCH');

        $actor = ['seat' => 'conscription.recruiter', 'manifestation_id' => $recruiter['manifestation_id'], 'occupancy_generation' => $recruiter['occupancy_generation']];
        $acceptanceId = 'profile-derivation-acceptance-'.substr(hash('sha256', CanonicalJson::encode([$actId, $act['record_digest'], $actor])), 0, 20);
        $acceptance = $this->persist($this->acceptanceDirectory, $acceptanceId, [
            'schema' => 'imperium.conscription-profile-derivation-authorization-acceptance/v1',
            'acceptance_id' => $acceptanceId,
            'instance_id' => $instanceId,
            'proceeding_id' => $act['proceeding_id'],
            'actor' => $actor,
            'source_authorization_act' => ['id' => $actId, 'digest' => $act['record_digest']],
            'source_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
            'source_reservation_disposition' => $act['source_reservation_disposition'],
            'source_plan' => $act['source_plan'],
            'profile_scope' => $act['profile_scope'],
            'disposition' => 'ACCEPTED',
            'status' => 'PROFILE_DERIVATION_ACCEPTED_PENDING_CONSTABLE_HANDOFF_DISPOSITION',
            'garrison_handoff_request_authority' => true,
            'retrieval_authority' => false,
            'custody_release_authority' => false,
            'laboratorium_commission_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ], 'R78_PROFILE_DERIVATION_ACCEPTANCE_FAILED', 'R79_PROFILE_DERIVATION_ACCEPTANCE_CONFLICT');

        $handoffId = 'profile-derivation-handoff-request-'.substr(hash('sha256', CanonicalJson::encode([$acceptanceId, $acceptance['record_digest'], $reservation['record_digest']])), 0, 20);
        $handoff = $this->persist($this->handoffDirectory, $handoffId, [
            'schema' => 'imperium.conscription-garrison-profile-derivation-handoff-request/v1',
            'request_id' => $handoffId,
            'instance_id' => $instanceId,
            'proceeding_id' => $act['proceeding_id'],
            'requester' => $actor,
            'recipient' => ['office' => 'garrison', 'seat' => 'garrison.constable'],
            'source_acceptance' => ['id' => $acceptanceId, 'digest' => $acceptance['record_digest']],
            'source_authorization_act' => ['id' => $actId, 'digest' => $act['record_digest']],
            'source_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
            'source_reservation_disposition' => $act['source_reservation_disposition'],
            'source_plan' => $act['source_plan'],
            'profile_scope' => $act['profile_scope'],
            'custody' => ['id' => $reservation['custody_id'], 'digest' => $reservation['custody_digest']],
            'persona' => $reservation['personnel_commitment']['persona'],
            'requested_handoff' => 'CUSTODY_BOUND_PROFILE_DERIVATION_ONLY',
            'status' => 'PENDING_CONSTABLE_PROFILE_DERIVATION_HANDOFF_DISPOSITION',
            'handoff_requested' => true,
            'handoff_authority' => false,
            'retrieval_authority' => false,
            'custody_release_authority' => false,
            'laboratorium_commission_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ], 'R80_PROFILE_DERIVATION_HANDOFF_REQUEST_FAILED', 'R81_PROFILE_DERIVATION_HANDOFF_REQUEST_CONFLICT');

        return ['acceptance' => $acceptance, 'handoff_request' => $handoff];
    }

    private function validateChain(string $actId, array $act, array $request, array $reservation): void
    {
        $scope = $act['profile_scope'] ?? null;
        if (!$this->digestMatches($act) || !$this->digestMatches($request) || !$this->digestMatches($reservation)
            || 'imperium.imperator-profile-derivation-decision/v1' !== ($act['schema'] ?? null) || $actId !== ($act['act_id'] ?? null)
            || 'AUTHORIZED' !== ($act['disposition'] ?? null) || 'PROFILE_DERIVATION_AUTHORIZED_PENDING_CONSCRIPTION_ACCEPTANCE' !== ($act['status'] ?? null)
            || true !== ($act['profile_derivation_authority'] ?? null) || true !== ($act['profile_derivation_authority_exercisable'] ?? null)
            || true !== ($act['conscription_followup_required'] ?? null) || true === ($act['retrieval_authority'] ?? null)
            || true === ($act['conscription_acceptance_authority'] ?? null) || true === ($act['spawning_authority'] ?? null)
            || true === ($act['seat_binding_authority'] ?? null) || true === ($act['deployment_authority'] ?? null) || true === ($act['execution_authority'] ?? null)
            || 'imperium.curia-profile-derivation-authorization-request/v1' !== ($request['schema'] ?? null)
            || ($act['source_request_id'] ?? null) !== ($request['request_id'] ?? null) || ($act['source_request_digest'] ?? null) !== ($request['record_digest'] ?? null)
            || ($act['instance_id'] ?? null) !== ($request['instance_id'] ?? null) || ($act['proceeding_id'] ?? null) !== ($request['proceeding_id'] ?? null)
            || CanonicalJson::encode($act['source_reservation_disposition'] ?? null) !== CanonicalJson::encode($request['source_reservation_disposition'] ?? null)
            || CanonicalJson::encode($act['source_plan'] ?? null) !== CanonicalJson::encode($request['source_plan'] ?? null)
            || CanonicalJson::encode($scope) !== CanonicalJson::encode($request['profile_scope'] ?? null)
            || 'PROFILE_DERIVATION_ONLY' !== ($request['requested_authority'] ?? null) || 'PENDING_IMPERATOR_PROFILE_DERIVATION_DECISION' !== ($request['status'] ?? null)
            || 'imperium.garrison-persona-reservation-disposition/v1' !== ($reservation['schema'] ?? null)
            || ($reservation['disposition_id'] ?? null) !== ($act['source_reservation_disposition']['id'] ?? null)
            || ($reservation['record_digest'] ?? null) !== ($act['source_reservation_disposition']['digest'] ?? null)
            || ($reservation['instance_id'] ?? null) !== ($act['instance_id'] ?? null) || ($reservation['proceeding_id'] ?? null) !== ($act['proceeding_id'] ?? null)
            || 'RESERVED' !== ($reservation['disposition'] ?? null) || 'RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION' !== ($reservation['status'] ?? null)
            || true !== ($reservation['persona_reserved'] ?? null) || !is_array($scope)
            || CanonicalJson::encode($reservation['personnel_commitment']['persona'] ?? null) !== CanonicalJson::encode($scope['persona'] ?? null)
            || ($reservation['personnel_commitment']['profession'] ?? null) !== ($scope['profession'] ?? null)
            || ($reservation['personnel_commitment']['capability_slot_id'] ?? null) !== ($scope['capability_slot_id'] ?? null)
            || CanonicalJson::encode($reservation['personnel_commitment']['capability_requirements'] ?? null) !== CanonicalJson::encode($scope['capability_requirements'] ?? null)
            || 'curia' !== ($scope['profile_steward'] ?? null) || 'conscription.recruiter' !== ($scope['prospective_commissioner_and_installer'] ?? null)
            || 'laboratorium.alchemist' !== ($scope['prospective_transformer'] ?? null) || 'senate' !== ($scope['prospective_examiner'] ?? null)
            || 'imperator' !== ($scope['prospective_approver'] ?? null) || true !== ($act['sealed'] ?? null) || true !== ($request['sealed'] ?? null) || true !== ($reservation['sealed'] ?? null)
        ) throw new \RuntimeException('R76_PROFILE_DERIVATION_AUTHORIZATION_CHAIN_INVALID');
    }

    private function ordinaryRecruiter(): array
    {
        $state = $this->bootstrap->read();
        if (!is_array($state) || BootstrapState::CuriaReady->value !== ($state['state'] ?? null)) throw new \RuntimeException('R82_RECRUITER_UNAVAILABLE');
        for ($index = count($state['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $state['events'][$index];
            $recruiter = 'T04' === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null) ? ($event['output']['successor'] ?? null) : null;
            if (is_array($recruiter) && 'conscription.recruiter' === ($recruiter['seat'] ?? null) && 'ordinary-recruiter' === ($recruiter['authority'] ?? null) && 2 === ($recruiter['occupancy_generation'] ?? null) && is_string($recruiter['manifestation_id'] ?? null)) return [$state['binding']['instance_id'] ?? null, $recruiter];
        }
        throw new \RuntimeException('R82_RECRUITER_UNAVAILABLE');
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) throw new \RuntimeException($error);
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record['record_digest'] ?? null; unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function persist(string $directory, string $id, array $record, string $failure, string $conflict): array
    {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) throw new \RuntimeException($failure);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $directory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, $conflict);
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException($conflict);
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary); throw new \RuntimeException($failure);
        }
        return $record;
    }
}
