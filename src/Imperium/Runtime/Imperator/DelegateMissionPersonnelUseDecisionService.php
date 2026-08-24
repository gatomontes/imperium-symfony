<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionPersonnelUseDecisionService
{
    private const string IMPERATOR_ID = 'imperator-development-root';
    private const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED', 'RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED', 'DEFERRED'];

    private string $requests;
    private string $decisions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->requests = $root.'/var/imperium/curia/delegate-mission-personnel-use-requests';
        $this->decisions = $root.'/var/imperium/imperator/delegate-mission-personnel-use-decisions';
    }

    public function decide(string $requestId, string $disposition, string $response, ?string $limitations, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^delegate-mission-personnel-use-request-[a-f0-9]{20}$/', $requestId)) {
            throw new \InvalidArgumentException('I510_DELEGATE_MISSION_PERSONNEL_USE_REQUEST_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $response = trim($response);
        $limitations = null === $limitations ? null : trim($limitations);
        if (!in_array($disposition, self::DISPOSITIONS, true)
            || '' === $response
            || '' === $limitations
            || ('AUTHORIZED' === $disposition && null === $limitations)) {
            throw new \InvalidArgumentException('I511_DELEGATE_MISSION_PERSONNEL_USE_DISPOSITION_INVALID');
        }

        $request = $this->read($this->requests.'/'.$requestId.'.json', 'I512_DELEGATE_MISSION_PERSONNEL_USE_REQUEST_ABSENT');
        $this->validateRequest($requestId, $request);

        foreach (glob($this->decisions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'I515_DELEGATE_MISSION_PERSONNEL_USE_DECISION_CONFLICT');
            if (($prior['source_request']['id'] ?? null) === $requestId) {
                if (($prior['source_request']['digest'] ?? null) === $request['record_digest']
                    && ($prior['disposition'] ?? null) === $disposition
                    && ($prior['response'] ?? null) === $response
                    && ($prior['limitations'] ?? null) === $limitations) {
                    return $prior;
                }
                throw new \RuntimeException('I515_DELEGATE_MISSION_PERSONNEL_USE_DECISION_CONFLICT');
            }
        }

        $authorized = 'AUTHORIZED' === $disposition;
        $decisionId = 'delegate-mission-personnel-use-decision-'.substr(hash('sha256', CanonicalJson::encode([
            $requestId,
            $request['record_digest'],
            self::IMPERATOR_ID,
            $disposition,
            $response,
            $limitations,
        ])), 0, 20);
        $authority = null;
        if ($authorized) {
            $authority = [
                'authority_id' => 'delegate-mission-personnel-use-authority-'.substr(hash('sha256', CanonicalJson::encode([
                    $decisionId,
                    $request['record_digest'],
                    $request['personnel_commitment'],
                    $limitations,
                ])), 0, 20),
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'holder' => 'guildhall.guildmaster',
                'purpose' => 'ACCEPT_ONE_EXACT_AUTHORIZED_DELEGATE_PERSONNEL_COMMITMENT',
                'source_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
                'personnel_commitment_digest' => hash('sha256', CanonicalJson::encode($request['personnel_commitment'])),
                'limitations' => $limitations,
                'consumed' => false,
                'continuing_authority' => false,
            ];
        }

        return $this->save($decisionId, [
            'schema' => 'imperium.imperator-delegate-mission-personnel-use-decision/v1',
            'decision_id' => $decisionId,
            'instance_id' => $request['instance_id'],
            'officer_class' => OfficerClass::Delegate->value,
            'actor' => ['kind' => 'imperator', 'id' => self::IMPERATOR_ID],
            'authority_basis' => 'development-local-cli',
            'source_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
            'source_resolution' => $request['source_resolution'],
            'source_demand' => $request['source_demand'],
            'source_mission_plan' => $request['source_mission_plan'],
            'source_garrison_facts' => $request['source_garrison_facts'],
            'personnel_commitment' => $request['personnel_commitment'],
            'personnel_resolution_boundary' => $request['personnel_resolution_boundary'],
            'disposition' => $disposition,
            'response' => $response,
            'limitations' => $limitations,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'imperator_decision_recorded' => true,
            'personnel_use_authorized' => $authorized,
            'personnel_use_authority' => $authority,
            'personnel_use_authority_exercisable' => $authorized,
            'guildhall_followup_required' => in_array($disposition, ['RETURNED_FOR_REVISION', 'ALTERNATIVE_PROPOSED', 'CLARIFICATION_REQUIRED'], true),
            'status' => $authorized
                ? 'DELEGATE_MISSION_PERSONNEL_USE_AUTHORIZED_PENDING_GUILDHALL_ACCEPTANCE'
                : 'DELEGATE_MISSION_NON_AUTHORIZING_IMPERATOR_PERSONNEL_USE_DISPOSITION_RECORDED',
            'guildhall_acceptance_authority' => false,
            'reservation_authority' => false,
            'retrieval_authority' => false,
            'custody_transfer_authority' => false,
            'profile_derivation_authority' => false,
            'profile_examination_authority' => false,
            'profile_approval_authority' => false,
            'profile_installation_authority' => false,
            'profile_qualification_authority' => false,
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
            'continuing_turn_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validateRequest(string $requestId, array $request): void
    {
        $commitment = $request['personnel_commitment'] ?? null;
        if (!$this->valid($request)
            || 'imperium.curia-delegate-mission-personnel-use-request/v1' !== ($request['schema'] ?? null)
            || $requestId !== ($request['request_id'] ?? null)
            || OfficerClass::Delegate->value !== ($request['officer_class'] ?? null)
            || 'DELEGATE_MISSION_PERSONNEL_USE_REQUEST_PRESENTED_PENDING_IMPERATOR_DECISION' !== ($request['status'] ?? null)
            || self::IMPERATOR_ID !== ($request['recipient']['id'] ?? null)
            || true !== ($request['recipient']['decision_pending'] ?? null)
            || 'PRESENTATION_ONLY' !== ($request['requester']['role'] ?? null)
            || 'ONE_EXACT_DELEGATE_PERSONNEL_USE_COMMITMENT_ONLY' !== ($request['requested_authority'] ?? null)
            || self::DISPOSITIONS !== ($request['allowed_dispositions'] ?? null)
            || true === ($request['imperator_decision_recorded'] ?? null)
            || true === ($request['personnel_use_authority'] ?? null)
            || true === ($request['reservation_authority'] ?? null)
            || true === ($request['retrieval_authority'] ?? null)
            || true === ($request['custody_transfer_authority'] ?? null)
            || true === ($request['profile_derivation_authority'] ?? null)
            || true === ($request['manifestation_assembly_authority'] ?? null)
            || true === ($request['seat_binding_authority'] ?? null)
            || true === ($request['deployment_authority'] ?? null)
            || true === ($request['execution_authority'] ?? null)
            || true !== ($request['personnel_use_request_authority']['consumed'] ?? null)
            || false !== ($request['personnel_use_request_authority']['continuing_authority'] ?? null)
            || !is_array($commitment)
            || OfficerClass::Delegate->value !== ($commitment['officer_class'] ?? null)
            || [] === ($commitment['capability_requirements'] ?? [])
            || !is_string($commitment['mission_seat'] ?? null)
            || !is_string($commitment['profession'] ?? null)
            || 'SUITABLE' !== ($commitment['suitability_disposition'] ?? null)
            || !is_array($commitment['persona'] ?? null)
            || !is_string($commitment['persona']['custody_id'] ?? null)
            || !is_string($commitment['persona']['persona_id'] ?? null)
            || 'ADMITTED_HELD' !== ($commitment['persona']['custody_state'] ?? null)
            || true !== ($commitment['persona']['available'] ?? null)
            || 'guildhall.guildmaster' !== ($request['personnel_resolution_boundary']['resolution_authority'] ?? null)
            || 'PRESENTATION_ONLY' !== ($request['personnel_resolution_boundary']['curia_role'] ?? null)
            || false !== ($request['personnel_resolution_boundary']['curia_profession_selection_authority'] ?? null)
            || false !== ($request['personnel_resolution_boundary']['curia_persona_selection_authority'] ?? null)
            || false !== ($request['personnel_resolution_boundary']['curia_substitution_authority'] ?? null)
            || false !== ($request['personnel_resolution_boundary']['curia_amendment_authority'] ?? null)
            || true !== ($request['sealed'] ?? null)) {
            throw new \RuntimeException('I513_DELEGATE_MISSION_PERSONNEL_USE_REQUEST_INVALID');
        }
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
        if (!is_dir($this->decisions) && !mkdir($this->decisions, 0770, true) && !is_dir($this->decisions)) {
            throw new \RuntimeException('I514_DELEGATE_MISSION_PERSONNEL_USE_DECISION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->decisions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'I515_DELEGATE_MISSION_PERSONNEL_USE_DECISION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('I515_DELEGATE_MISSION_PERSONNEL_USE_DECISION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('I514_DELEGATE_MISSION_PERSONNEL_USE_DECISION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
