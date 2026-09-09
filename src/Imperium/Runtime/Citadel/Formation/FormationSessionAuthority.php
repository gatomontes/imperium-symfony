<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;
use App\Imperium\Runtime\Clock;

/** Shared authentic session interpretation; callers supply state only under its journal boundary. */
final readonly class FormationSessionAuthority
{
    public function __construct(private FormationSignatures $signatures, private FormationPersonnel $personnel, private Clock $clock) {}
    public function source(array $state, string $intakeId, string $phase): array
    {
        $intake = $state['intakes'][$intakeId] ?? throw new \RuntimeException('CMF012_INTAKE_ABSENT');
        if ($phase === 'acceptance') {
            $handoff = $state['handoffs'][$intakeId] ?? throw new \RuntimeException('CMF063_HANDOFF_REQUIRED');
            $holder = $handoff['constitution']['occupants']['curia.seneschal'];
            $this->personnel->candidate($state, $holder['candidate'], $handoff['mission_id'], 'curia.seneschal');
            $this->signatures->verify($state, $handoff['constitution']['decision'], 'APPROVE_MISSION_AND_CONSTITUTION', $handoff['constitution']['signed_review']);
            $authority = ['handoff_id' => $handoff['handoff_id'], 'packet_digest' => FormationJournal::digest($handoff['packet']), 'holder_digest' => FormationJournal::digest($holder)];
        } else {
            $holder = $this->personnel->currentCastellan($state);
            if ($phase === 'interview') {
                if (isset($intake['understanding'])) { throw new \RuntimeException('CMF067_SESSION_CLOSED_CHANGED_OR_EXPIRED'); }
                $authority = ['intake_id' => $intakeId, 'intent_version' => $intake['intent_version'],
                    'opening_exchange' => $intake['exchange'][0], 'holder_digest' => FormationJournal::digest($holder)];
            } elseif ($phase === 'drafting') {
                $authority = $state['drafting_requests'][$intake['drafting_request'] ?? ''] ?? throw new \RuntimeException('CMF064_SEPARATE_DRAFTING_REQUEST_REQUIRED');
                if ($authority['understanding_digest'] !== FormationJournal::digest($intake['understanding'] ?? null)
                    || $authority['holder_digest'] !== FormationJournal::digest($holder)
                    || $authority['intent_version'] !== $intake['intent_version']) { throw new \RuntimeException('CMF065_DRAFTING_LINEAGE_CHANGED'); }
            } else { throw new \RuntimeException('CMF066_PHASE_INVALID'); }
        }
        return ['intake' => $intake, 'holder' => $holder, 'authorization_source' => $authority];
    }

    public function validateSession(array $state, array $session, bool $requireOpen = true): array
    {
        $this->signatures->verify($state, $session['decision'], $session['effect'], $session['terms']);
        $source = $this->source($state, $session['intake_id'], $session['phase']);
        if ($this->wasRefused($session) || isset($session['interview_completion']) || ($requireOpen && $session['status'] !== 'OPEN')
            || $session['terms']['expires_at'] <= $this->clock->now()->getTimestamp()
            || $session['terms']['source'] !== $source['authorization_source']
            || $session['holder_digest'] !== FormationJournal::digest($source['holder'])
            || $session['intent_version'] !== $source['intake']['intent_version']) {
            throw new \RuntimeException('CMF067_SESSION_CLOSED_CHANGED_OR_EXPIRED');
        }
        return $source;
    }

    /** Retained controls also fence sessions indirectly reopened by older code. */
    public function wasRefused(array $session): bool
    {
        if ($session['status'] === 'REFUSED') { return true; }
        foreach ($session['controls'] ?? [] as $control) {
            if (($control['payload']['effect'] ?? null) === 'CONTROL_FORMATION_SESSION'
                && ($control['payload']['object_digest'] ?? null) === FormationJournal::digest([
                    'session_id' => $session['session_id'], 'disposition' => 'REFUSED'])) { return true; }
        }
        return false;
    }

    public function request(array $state, array $session, array $source): array
    {
        $context = [];
        foreach ($session['terms']['visible_intakes'] as $id) {
            $intake = $state['intakes'][$id];
            $context[] = ['intake_id' => $id, 'intent_version' => $intake['intent_version'],
                'status' => $intake['status'], 'request' => $intake['exchange'][0]['content'],
                'mission_id' => $intake['mission_id'], 'digest' => $intake['record_digest']];
        }
        return ['phase' => $session['phase'], 'cognitive_artifact' => $source['holder']['cognitive_artifact'],
            'holder' => $source['holder'], 'exchange' => $source['intake']['exchange'],
            'authority_source' => $source['authorization_source'],
            'prior_dossiers' => $session['phase'] === 'drafting' ? ($state['dossiers'][$session['intake_id']] ?? []) : [],
            'prior_reviews' => $session['phase'] === 'drafting' ? array_values(array_filter($state['reviews'] ?? [], static fn (array $review): bool => $review['terms']['intake_id'] === $session['intake_id'])) : [],
            'registry_generation' => $state['registry_generation'] ?? 0, 'registry_context' => $context,
            'handoff' => $session['phase'] === 'acceptance' ? $state['handoffs'][$session['intake_id']]['packet'] : null,
            'response_contract' => match ($session['phase']) {
                'interview' => 'Question or understanding with dissent only; never produce a proposal. Keys: disposition, understood_intent, question, dissent, unknowns, overlap, ready_to_request_drafting.',
                'drafting' => 'Produce only the exact authorized FormationPlan schema under the supplied Planning Charter. No investigation or execution.',
                'acceptance' => 'Assess the complete original exchange and mandate. Keys: disposition (ACCEPTED or GAP), rationale, gaps, dissent. Acceptance grants no execution authority.',
            }];
    }
}
