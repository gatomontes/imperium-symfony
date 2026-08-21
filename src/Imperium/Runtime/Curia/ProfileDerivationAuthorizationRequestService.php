<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileDerivationAuthorizationRequestService
{
    private string $reservationDirectory;
    private string $requestDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir, private ProceedingStore $proceedings)
    {
        $this->reservationDirectory = $projectDir.'/var/imperium/offices/garrison/persona-reservation-dispositions';
        $this->requestDirectory = $projectDir.'/var/imperium/curia/profile-derivation-authorization-requests';
    }

    public function request(string $reservationDispositionId, int $planTurnSequence): array
    {
        if (!preg_match('/^persona-reservation-disposition-[a-f0-9]{20}$/', $reservationDispositionId)) {
            throw new \InvalidArgumentException('C136_RESERVATION_DISPOSITION_ID_INVALID');
        }
        if ($planTurnSequence < 1) throw new \InvalidArgumentException('C137_PLAN_TURN_INVALID');

        $reservation = $this->read($this->reservationDirectory.'/'.$reservationDispositionId.'.json', 'C138_RESERVATION_DISPOSITION_ABSENT');
        $commitment = $reservation['personnel_commitment'] ?? null;
        $persona = is_array($commitment) ? ($commitment['persona'] ?? null) : null;
        if (!$this->digestMatches($reservation)
            || 'imperium.garrison-persona-reservation-disposition/v1' !== ($reservation['schema'] ?? null)
            || $reservationDispositionId !== ($reservation['disposition_id'] ?? null)
            || 'RESERVED' !== ($reservation['disposition'] ?? null)
            || 'RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION' !== ($reservation['status'] ?? null)
            || true !== ($reservation['persona_reserved'] ?? null)
            || true === ($reservation['retrieval_authority'] ?? null)
            || true === ($reservation['profile_derivation_authority'] ?? null)
            || true === ($reservation['spawning_authority'] ?? null)
            || true === ($reservation['seat_binding_authority'] ?? null)
            || true === ($reservation['deployment_authority'] ?? null)
            || true === ($reservation['execution_authority'] ?? null)
            || true !== ($reservation['sealed'] ?? null)
            || !is_array($commitment) || !is_array($persona)
            || !is_string($commitment['capability_slot_id'] ?? null) || '' === trim($commitment['capability_slot_id'])
            || !is_string($commitment['profession'] ?? null) || '' === trim($commitment['profession'])
            || !is_array($commitment['capability_requirements'] ?? null) || [] === $commitment['capability_requirements']
            || !is_string($commitment['suitability_determination'] ?? null) || '' === trim($commitment['suitability_determination'])
            || !is_string($commitment['guildhall_resolution_digest'] ?? null) || !preg_match('/^[a-f0-9]{64}$/', $commitment['guildhall_resolution_digest'])
            || !is_string($persona['custody_id'] ?? null) || '' === trim($persona['custody_id'])
            || !is_string($persona['persona_id'] ?? null) || '' === trim($persona['persona_id'])
        ) {
            throw new \RuntimeException('C139_RESERVATION_DISPOSITION_INVALID');
        }
        foreach ($commitment['capability_requirements'] as $capability) {
            if (!is_string($capability) || '' === trim($capability)) throw new \RuntimeException('C139_RESERVATION_DISPOSITION_INVALID');
        }

        $proceedingId = $reservation['proceeding_id'] ?? null;
        $proceeding = is_string($proceedingId) ? $this->proceedings->find($proceedingId) : null;
        $turn = is_string($proceedingId) ? $this->proceedings->turn($proceedingId, $planTurnSequence) : null;
        $plan = is_array($turn) ? ($turn['seneschal']['mission_plan'] ?? null) : null;
        if (!is_array($proceeding) || ($proceeding['instance_id'] ?? null) !== ($reservation['instance_id'] ?? null)
            || !is_array($turn) || !$this->digestMatches($turn)
            || 'MISSION_PLAN_DRAFTED' !== ($turn['seneschal']['disposition'] ?? null)
            || !is_array($plan)
        ) {
            throw new \RuntimeException('C140_PROFILE_SCOPE_SOURCE_INVALID');
        }
        $this->validatePlan($plan);
        $capabilities = $commitment['capability_requirements'] ?? null;
        if (!is_array($capabilities) || [] === $capabilities || [] !== array_diff($capabilities, $plan['capability_requirements'])) {
            throw new \RuntimeException('C141_PROFILE_SCOPE_MISMATCH');
        }

        $profileScope = [
            'target_kind' => 'MISSION_OPERATIVE',
            'capability_slot_id' => $commitment['capability_slot_id'],
            'profession' => $commitment['profession'],
            'persona' => $persona,
            'objective' => $plan['objective'],
            'scope' => $plan['scope'],
            'constraints' => $plan['constraints'],
            'capability_requirements' => $capabilities,
            'tool_requirements' => $plan['tool_requirements'],
            'data_requirements' => $plan['data_requirements'],
            'stop_conditions' => $plan['stop_conditions'],
            'profile_steward' => 'curia',
            'prospective_commissioner_and_installer' => 'conscription.recruiter',
            'prospective_transformer' => 'laboratorium.alchemist',
            'prospective_examiner' => 'senate',
            'prospective_approver' => 'imperator',
        ];
        $requestId = 'profile-derivation-authorization-request-'.substr(hash('sha256', CanonicalJson::encode([$reservationDispositionId, $reservation['record_digest'], $planTurnSequence, $turn['record_digest'], $profileScope])), 0, 20);
        return $this->persist($requestId, [
            'schema' => 'imperium.curia-profile-derivation-authorization-request/v1',
            'request_id' => $requestId,
            'instance_id' => $reservation['instance_id'],
            'proceeding_id' => $proceedingId,
            'requester' => ['office' => 'curia', 'seat' => 'curia.seneschal'],
            'recipient' => ['kind' => 'imperator', 'id' => 'imperator-development-root'],
            'source_reservation_disposition' => ['id' => $reservationDispositionId, 'digest' => $reservation['record_digest']],
            'source_plan' => ['turn_sequence' => $planTurnSequence, 'turn_digest' => $turn['record_digest']],
            'profile_scope' => $profileScope,
            'question' => 'Authorize Profile derivation for this exact reserved Persona and immutable mission scope?',
            'requested_authority' => 'PROFILE_DERIVATION_ONLY',
            'allowed_dispositions' => ['AUTHORIZED', 'REFUSED', 'RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED', 'DEFERRED'],
            'status' => 'PENDING_IMPERATOR_PROFILE_DERIVATION_DECISION',
            'retrieval_authority' => false,
            'profile_derivation_authority' => false,
            'profile_derivation_authority_exercisable' => false,
            'conscription_acceptance_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validatePlan(array $plan): void
    {
        $expected = ['capability_requirements', 'constraints', 'data_requirements', 'deliverables', 'objective', 'office_participation', 'required_inputs', 'scope', 'stop_conditions', 'tool_requirements'];
        $keys = array_keys($plan); sort($keys);
        if ($expected !== $keys || !is_string($plan['objective']) || '' === trim($plan['objective'])) throw new \RuntimeException('C140_PROFILE_SCOPE_SOURCE_INVALID');
        foreach (array_diff($expected, ['objective']) as $field) {
            if (!is_array($plan[$field]) || [] === $plan[$field]) throw new \RuntimeException('C140_PROFILE_SCOPE_SOURCE_INVALID');
            foreach ($plan[$field] as $value) if (!is_string($value) || '' === trim($value)) throw new \RuntimeException('C140_PROFILE_SCOPE_SOURCE_INVALID');
        }
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

    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->requestDirectory) && !mkdir($this->requestDirectory, 0770, true) && !is_dir($this->requestDirectory)) throw new \RuntimeException('C142_PROFILE_DERIVATION_REQUEST_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->requestDirectory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'C143_PROFILE_DERIVATION_REQUEST_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('C143_PROFILE_DERIVATION_REQUEST_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary); throw new \RuntimeException('C142_PROFILE_DERIVATION_REQUEST_FAILED');
        }
        return $record;
    }
}
