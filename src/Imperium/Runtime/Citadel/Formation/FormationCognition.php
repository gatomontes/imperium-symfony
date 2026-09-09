<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService;
use App\Imperium\Runtime\Clavium\FormationSessionLeaseService;

/** Session-level consent, exact per-attempt claims, sealed returns, then admission.
 * Registry locks never span inspect(), invoke() or provider response sealing.
 */
final readonly class FormationCognition
{
    public function __construct(
        private FormationJournal $journal,
        private FormationSignatures $signatures,
        private FormationPersonnel $personnel,
        private BoundedFormationTransport $transport,
        private ProviderResponseEnvelopeService $responses,
        private FormationSessionLeaseService $leases,
        private Clock $clock,
        private \App\Imperium\Runtime\Curia\ReceivingFormationHandoffService $receiving,
    ) {}

    public function reply(string $intakeId, string $content, bool $changedIntent, array $decision): array
    {
        return $this->journal->change(function (array &$state) use ($intakeId, $content, $changedIntent, $decision): array {
            $intake = $state['intakes'][$intakeId] ?? throw new \RuntimeException('CMF012_INTAKE_ABSENT');
            if (in_array($state['reservations'][$intakeId]['status'] ?? null, ['EFFECT_UNCERTAIN_IDENTITY_FENCED', 'DELIVERED'], true)) {
                throw new \RuntimeException('CMF069_FORMED_MISSION_REQUIRES_SEPARATE_AMENDMENT');
            }
            $terms = ['intake_id' => $intakeId, 'head' => $intake['record_digest'], 'content' => $content, 'changed_intent' => $changedIntent];
            $this->signatures->verify($state, $decision, 'REPLY_TO_CITADEL', $terms);
            if ('' === trim($content) || strlen($content) > 1048576 || $intake['mission_id'] !== null) {
                throw new \RuntimeException('CMF050_REPLY_INVALID');
            }
            $intake['exchange'][] = ['sequence' => count($intake['exchange']) + 1,
                'kind' => 'imperator-response', 'content' => $content, 'decision' => $decision];
            if ($changedIntent) { ++$intake['intent_version']; }
            // Older journals can retain UNDERSTOOD without session completion
            // markers. Preserve that boundary before the supported reply clears it.
            if (isset($intake['understanding'])) {
                $claim = $intake['understanding']['claim'];
                $this->completeInterviews($state, $intakeId, $claim['session_id'], $claim['attempt_id']);
            }
            unset($intake['understanding'], $intake['drafting_request']);
            $intake['status'] = 'PENDING_AUTHENTICATED_INTERVIEW';
            unset($intake['record_digest']);
            $intake['record_digest'] = FormationJournal::digest($intake);
            $state['intakes'][$intakeId] = $intake;
            ++$state['registry_generation'];
            return $intake;
        });
    }

    public function draftingRequest(string $intakeId, array $charter): array
    {
        return $this->journal->change(function (array &$state) use ($intakeId, $charter): array {
            $intake = $state['intakes'][$intakeId] ?? throw new \RuntimeException('CMF012_INTAKE_ABSENT');
            $holder = $this->personnel->currentCastellan($state);
            $understanding = $intake['understanding'] ?? [];
            if (($understanding['holder_digest'] ?? null) !== FormationJournal::digest($holder)
                || ($understanding['intent_version'] ?? null) !== $intake['intent_version']
                || ($understanding['response']['ready_to_request_drafting'] ?? null) !== true) {
                throw new \RuntimeException('CMF051_CURRENT_UNDERSTANDING_AND_READINESS_REQUIRED');
            }
            $fields = ['scope', 'questions', 'inputs', 'offices', 'external_effects', 'disclosure', 'expected_return', 'stop_conditions', 'amendment_triggers', 'retention', 'expires_at'];
            if (!FormationJournal::keys($charter, $fields) || $charter['offices'] !== [] || $charter['external_effects'] !== []
                || !is_int($charter['expires_at']) || $charter['expires_at'] <= $this->clock->now()->getTimestamp()) {
                throw new \RuntimeException('CMF052_CHARTER_INVALID_OR_INVESTIGATION_COMMISSION_REQUIRED');
            }
            foreach (['scope', 'questions', 'inputs', 'disclosure', 'expected_return', 'stop_conditions', 'amendment_triggers', 'retention'] as $field) {
                if (!is_string($charter[$field]) || '' === trim($charter[$field])) { throw new \RuntimeException('CMF052_CHARTER_INVALID_OR_INVESTIGATION_COMMISSION_REQUIRED'); }
            }
            $request = ['schema' => 'imperium.citadel-drafting-request/v1', 'intake_id' => $intakeId,
                'intent_version' => $intake['intent_version'], 'understanding_digest' => FormationJournal::digest($understanding),
                'holder_digest' => FormationJournal::digest($holder), 'charter' => $charter,
                'charter_version' => 1, 'status' => 'DRAFTING_REQUEST_PENDING_EXACT_DECISION',
                'author' => $holder, 'governing_doctrine' => 'imperium-doctrine.md',
                'planning_only' => true, 'execution_authority' => false];
            $request['request_id'] = 'drafting-'.FormationJournal::digest($request);
            $state['drafting_requests'][$request['request_id']] = $request;
            $state['intakes'][$intakeId]['drafting_request'] = $request['request_id'];
            unset($state['intakes'][$intakeId]['record_digest']);
            $state['intakes'][$intakeId]['record_digest'] = FormationJournal::digest($state['intakes'][$intakeId]);
            return $request;
        });
    }

    public function grant(string $intakeId, string $phase, array $terms, array $decision): string
    {
        return $this->journal->change(function (array &$state) use ($intakeId, $phase, $terms, $decision): string {
            $source = $this->source($state, $intakeId, $phase);
            if (!FormationJournal::keys($terms, array_merge(['source', 'provider', 'model', 'destination', 'pricing', 'per_call', 'total', 'visible_intakes', 'disclosure', 'expires_at'], array_key_exists('transport', $terms) ? ['transport'] : []))
                || FormationJournal::digest($terms['source']) !== FormationJournal::digest($source['authorization_source'])
                || !is_int($terms['expires_at']) || $terms['expires_at'] <= $this->clock->now()->getTimestamp()
                || !is_array($terms['visible_intakes']) || !array_is_list($terms['visible_intakes'])
                || $terms['visible_intakes'] !== array_values(array_unique($terms['visible_intakes']))
                || !is_string($terms['disclosure']) || '' === trim($terms['disclosure'])
                || !is_array($terms['pricing']) || [] === $terms['pricing']) {
                throw new \RuntimeException('CMF053_SESSION_TERMS_INVALID');
            }
            foreach (['provider', 'model', 'destination'] as $field) {
                if (!is_string($terms[$field]) || '' === trim($terms[$field])) { throw new \RuntimeException('CMF053_SESSION_TERMS_INVALID'); }
            }
            foreach ($terms['visible_intakes'] as $id) {
                if (!is_string($id) || !isset($state['intakes'][$id])) { throw new \RuntimeException('CMF053_SESSION_TERMS_INVALID'); }
            }
            if (array_key_exists('transport', $terms)) { FormationPreparedOperation::authorization($terms['transport']); }
            SessionExposure::validate($terms['per_call']);
            SessionExposure::validate($terms['total']);
            if ($terms['per_call']['calls'] !== 1) { throw new \RuntimeException('CMF053_SESSION_TERMS_INVALID'); }
            $effect = match ($phase) { 'interview' => 'AUTHORIZE_INTERVIEW_SESSION', 'drafting' => 'AUTHORIZE_EXACT_DRAFTING', 'acceptance' => 'AUTHORIZE_RECEIVING_ASSESSMENT' };
            $providerDecision = \App\Imperium\Runtime\Imperator\GovernanceProviderResourceDecisionService::formationSession($state, $terms, $decision, $phase, $this->signatures);
            if ($terms['expires_at'] > $decision['payload']['expires_at']
                || ($phase === 'drafting' && $terms['expires_at'] > $source['authorization_source']['charter']['expires_at'])) {
                throw new \RuntimeException('CMF053_SESSION_TERMS_INVALID');
            }
            $id = 'session-'.FormationJournal::digest([$intakeId, $phase, $terms, $decision]);
            $state['sessions'][$id] ??= ['session_id' => $id, 'intake_id' => $intakeId, 'phase' => $phase,
                'terms' => $terms, 'decision' => $decision, 'provider_resource_decision' => $providerDecision, 'effect' => $effect, 'holder_digest' => FormationJournal::digest($source['holder']),
                'intent_version' => $source['intake']['intent_version'],
                'per_call' => $terms['per_call'], 'total' => $terms['total'], 'attempts' => [], 'status' => 'OPEN'];
            return $id;
        });
    }

    public function control(string $sessionId, string $disposition, array $decision): void
    {
        $this->journal->change(function (array &$state) use ($sessionId, $disposition, $decision): void {
            $session = $state['sessions'][$sessionId] ?? throw new \RuntimeException('CMF054_SESSION_ABSENT');
            if (!in_array($disposition, ['REFUSED', 'DEFERRED', 'OPEN'], true)
                || $this->wasRefused($session) || isset($session['interview_completion'])) { throw new \RuntimeException('CMF055_SESSION_CONTROL_INVALID'); }
            $this->signatures->verify($state, $decision, 'CONTROL_FORMATION_SESSION', ['session_id' => $sessionId, 'disposition' => $disposition]);
            if ($disposition === 'OPEN') { $this->validateSession($state, $session, false); }
            $state['sessions'][$sessionId]['status'] = $disposition;
            $state['sessions'][$sessionId]['controls'][] = $decision;
        });
    }

    public function call(string $sessionId, string $attemptId): array
    {
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{7,79}$/D', $attemptId)) { throw new \RuntimeException('CMF056_ATTEMPT_INVALID'); }
        // inspect() is a trusted no-I/O adapter contract, and runs outside the lock.
        $snapshot = $this->journal->read()['state'];
        $session = $snapshot['sessions'][$sessionId] ?? throw new \RuntimeException('CMF054_SESSION_ABSENT');
        if (isset($session['attempts'][$attemptId])) { return $this->recover($sessionId, $attemptId); }
        $source = $this->validateSession($snapshot, $session);
        $request = $this->request($snapshot, $session, $source);
        // The prepared path inspects once and retains that exact operation, rather
        // than discarding its bytes and preparing a possibly different operation.
        $operation = $this->transport instanceof PreparedFormationTransport ? $this->transport->prepareOperation($request, $session['terms']) : null;
        $maximum = $operation === null ? $this->transport->inspect($request, $session['terms']) : $operation['maximum'];
        if ($operation !== null) { FormationPreparedOperation::validate($operation, $request, $session['terms'], $maximum); }
        $claim = $this->journal->change(function (array &$state) use ($sessionId, $attemptId, $request, $maximum, $operation): array {
            $session = &$state['sessions'][$sessionId];
            $source = $this->validateSession($state, $session);
            if (FormationJournal::digest($request) !== FormationJournal::digest($this->request($state, $session, $source))) {
                throw new \RuntimeException('CMF057_CONTEXT_CHANGED_REASSESS');
            }
            if (isset($session['attempts'][$attemptId])) { throw new \RuntimeException('CMF058_ATTEMPT_ALREADY_RESERVED'); }
            SessionExposure::reserve($session, $attemptId, $maximum, FormationJournal::digest($request));
            $expiresAt = min($session['terms']['expires_at'], $this->clock->now()->getTimestamp() + (int) ceil($maximum['milliseconds'] / 1000));
            $derivation = $this->leases->derive($state, $session, $source['holder'], $request, $maximum, $expiresAt, $attemptId, $operation);
            $claim = ['schema' => $operation === null ? 'imperium.citadel-session-call-claim/v1' : 'imperium.citadel-session-call-claim/v2',
                'claim_id' => 'governance-cognition-invocation-claim-'.substr(FormationJournal::digest([$sessionId, $attemptId, $request]), 0, 20),
                'session_id' => $sessionId, 'attempt_id' => $attemptId,
                'source_decision_digest' => FormationJournal::digest($session['decision']),
                'holder' => $source['holder'], 'request_digest' => FormationJournal::digest($request),
                'maximum' => $maximum, 'expires_at' => $expiresAt, 'derivation' => $derivation,
                'derived_authority_consumed' => true, 'lease_consumed' => true,
                'automatic_retry_permitted' => false, 'execution_authority' => false];
            if ($operation !== null) { $claim['prepared_operation'] = $operation; }
            $claim['record_digest'] = FormationJournal::digest($claim);
            $session['attempts'][$attemptId] += ['claim' => $claim, 'request' => $request];
            return $claim;
        });
        // The durable start fence is the last local operation before the transport.
        $this->journal->change(function (array &$state) use ($sessionId, $attemptId): void {
            $session = &$state['sessions'][$sessionId];
            $this->validateSession($state, $session);
            if ($session['attempts'][$attemptId]['status'] !== 'RESERVED') { throw new \RuntimeException('CMF058_ATTEMPT_ALREADY_RESERVED'); }
            if (FormationJournal::digest($this->personnel->currentLocksmith($state)) !== FormationJournal::digest($session['attempts'][$attemptId]['claim']['derivation']['lease']['issuer'])
                || $session['attempts'][$attemptId]['claim']['expires_at'] <= $this->clock->now()->getTimestamp()) { throw new \RuntimeException('CMF068_LEASE_CHANGED_OR_EXPIRED'); }
            $session['attempts'][$attemptId]['status'] = 'STARTED_OUTCOME_UNCERTAIN';
        });
        try {
            $result = $this->transport->invoke($claim, $request, $session['terms']);
            if (!is_string($result['response'] ?? null) || !is_string($result['provider_response_id'] ?? null)
                || '' === $result['provider_response_id'] || !is_array($result['usage'] ?? null)) {
                throw new \RuntimeException('CMF033_USAGE_UNTRUSTWORTHY');
            }
            // Existing provider envelope consumer preserves the exact raw return.
            $envelope = $operation === null
                ? $this->responses->seal($claim, $result['response'], $this->clock->now())
                : $this->responses->read($claim['claim_id']);
            if ($envelope['claim']['digest'] !== $claim['record_digest'] || $envelope['response'] !== $result['response']) {
                throw new \RuntimeException('CMF060_RESPONSE_PROVENANCE_INVALID');
            }
            if ($this->clock->now()->getTimestamp() > $claim['expires_at']) { throw new \RuntimeException('CMF068_LEASE_CHANGED_OR_EXPIRED'); }
            $this->journal->change(function (array &$state) use ($sessionId, $attemptId, $result, $envelope): void {
                $attempt = &$state['sessions'][$sessionId]['attempts'][$attemptId];
                $attempt['envelope'] = $envelope;
                $attempt['provider_response_id'] = $result['provider_response_id'];
                $attempt['status'] = 'SEALED_PENDING_ADMISSION';
                SessionExposure::settle($attempt, $result['usage']);
            });
        } catch (\Throwable $error) {
            // A sealed envelope may still be recovered. Never release exposure here.
            throw new \RuntimeException('CMF059_OUTCOME_UNKNOWN_NO_RETRY', 0, $operation === null ? $error : null);
        }
        return $this->recover($sessionId, $attemptId);
    }

    public function recover(string $sessionId, string $attemptId): array
    {
        $snapshot = $this->journal->read()['state'];
        $attempt = $snapshot['sessions'][$sessionId]['attempts'][$attemptId] ?? throw new \RuntimeException('CMF056_ATTEMPT_INVALID');
        $envelope = $this->responses->read($attempt['claim']['claim_id']);
        $result = $this->journal->change(function (array &$state) use ($sessionId, $attemptId, $envelope): array {
            $session = &$state['sessions'][$sessionId];
            $attempt = &$session['attempts'][$attemptId];
            if (($envelope['claim']['digest'] ?? null) !== $attempt['claim']['record_digest']) { throw new \RuntimeException('CMF060_RESPONSE_PROVENANCE_INVALID'); }
            if (($attempt['claim']['schema'] ?? null) === 'imperium.citadel-session-call-claim/v2') {
                if (!in_array($attempt['custody']['status'] ?? null, ['RESPONSE_VALIDATED_PENDING_ENVELOPE', 'RESPONSE_RETAINED'], true)
                    || ($attempt['custody']['claim_digest'] ?? null) !== $attempt['claim']['record_digest']
                    || ($attempt['custody']['operation_digest'] ?? null) !== FormationJournal::digest($attempt['claim']['prepared_operation'])
                    || ($attempt['custody']['response_identity'] ?? null) !== 'sha256:'.hash('sha256',$envelope['response'])
                    || ($attempt['custody']['response_identity'] ?? null) !== $envelope['provider_response_identity']
                    || !is_string($attempt['custody']['provider_response_id'] ?? null) || $attempt['custody']['provider_response_id'] === ''
                    || ($attempt['custody']['provenance'] ?? null) !== $attempt['claim']['prepared_operation']['authorization']['adapter']
                    || (isset($attempt['provider_response_id']) && $attempt['provider_response_id'] !== $attempt['custody']['provider_response_id'])
                    || ($attempt['custody']['status'] === 'RESPONSE_RETAINED' && ($attempt['custody']['response_digest'] ?? null) !== $envelope['record_digest'])) {
                    throw new \RuntimeException('FC016_RETAINED_CUSTODY_REQUIRED');
                }
            }
            if (isset($attempt['admitted'])) { return $attempt['admitted']; }
            $source = $this->validateSession($state, $session);
            if ((new \DateTimeImmutable($envelope['sealed_at']))->getTimestamp() > $attempt['claim']['expires_at']) { throw new \RuntimeException('CMF068_LEASE_CHANGED_OR_EXPIRED'); }
            if (FormationJournal::digest($this->request($state, $session, $source)) !== $attempt['fingerprint']) {
                throw new \RuntimeException('CMF057_CONTEXT_CHANGED_REASSESS');
            }
            $response = json_decode($envelope['response'], true, 64, JSON_THROW_ON_ERROR);
            $record = ['claim' => $attempt['claim'], 'envelope' => $envelope,
                'holder_digest' => $session['holder_digest'], 'intent_version' => $session['intent_version'],
                'registry_generation' => $attempt['request']['registry_generation'],
                'response' => $response, 'execution_authority' => false];
            if (($attempt['claim']['schema'] ?? null) === 'imperium.citadel-session-call-claim/v2') {
                $record['provider_response_id'] = $attempt['custody']['provider_response_id'];
                $record['provider_provenance'] = $attempt['custody']['provenance'];
                $attempt['provider_response_id'] = $record['provider_response_id'];
            }
            $intake = &$state['intakes'][$session['intake_id']];
            if ($session['phase'] === 'interview') {
                if (!is_array($response) || !FormationJournal::keys($response, ['disposition', 'understood_intent', 'question', 'dissent', 'unknowns', 'overlap', 'ready_to_request_drafting'])
                    || !in_array($response['disposition'], ['QUESTION', 'UNDERSTOOD'], true)
                    || !is_string($response['understood_intent']) || !is_string($response['question'])
                    || !is_string($response['dissent']) || !is_string($response['unknowns'])
                    || !is_string($response['overlap']) || !is_bool($response['ready_to_request_drafting'])
                    || ($response['disposition'] === 'UNDERSTOOD' && '' === trim($response['understood_intent']))
                    || ($response['disposition'] === 'QUESTION' && '' === trim($response['question']))) {
                    throw new \RuntimeException('CMF061_INTERVIEW_RESPONSE_INVALID_NO_PROPOSAL_ALLOWED');
                }
                $intake['exchange'][] = ['sequence' => count($intake['exchange']) + 1, 'kind' => 'castellan-response', 'attribution' => $record];
                if ($response['disposition'] === 'UNDERSTOOD') {
                    $intake['understanding'] = $record;
                    // Admission and closure share the journal transaction. Fence every
                    // existing interview grant, including work already in flight.
                    // A signed reply may clear understanding, but cannot revive these grants.
                    $this->completeInterviews($state, $session['intake_id'], $sessionId, $attemptId);
                }
            } elseif ($session['phase'] === 'drafting') {
                FormationPlan::validate($response);
                $version = count($state['dossiers'][$session['intake_id']] ?? []) + 1;
                $record += ['version' => $version, 'drafting_request' => $session['terms']['source'],
                    'drafting_decision' => $session['decision'], 'lines' => FormationPlan::lines($response)];
                $record['dossier_id'] = 'dossier-'.FormationJournal::digest($record);
                $state['dossiers'][$session['intake_id']][] = $record;
            } else {
                if (!is_array($response) || !FormationJournal::keys($response, ['disposition', 'rationale', 'gaps', 'dissent'])
                    || !in_array($response['disposition'], ['ACCEPTED', 'GAP'], true)
                    || !is_string($response['rationale']) || '' === trim($response['rationale'])
                    || !is_string($response['gaps']) || !is_string($response['dissent'])
                    || ($response['disposition'] === 'GAP' && '' === trim($response['gaps']))
                    || ($response['disposition'] === 'ACCEPTED' && '' !== $response['gaps'])) {
                    throw new \RuntimeException('CMF062_RECEIVING_RESPONSE_INVALID');
                }
                $state['handoffs'][$session['intake_id']]['acceptances'][] = $record;
            }
            unset($intake['record_digest']);
            $intake['record_digest'] = FormationJournal::digest($intake);
            $attempt['envelope'] = $envelope;
            $attempt['admitted'] = $record;
            $attempt['status'] = 'ADMITTED';
            return $record;
        });
        if ($snapshot['sessions'][$sessionId]['phase'] === 'acceptance') { $this->receiving->receive($sessionId, $attemptId); }
        return $result;
    }

    public function authorizationSource(string $intakeId, string $phase): array
    {
        return $this->source($this->journal->read()['state'], $intakeId, $phase)['authorization_source'];
    }

    private function completeInterviews(array &$state, string $intakeId, string $sessionId, string $attemptId): void
    {
        foreach ($state['sessions'] as &$interview) {
            if ($interview['intake_id'] === $intakeId && $interview['phase'] === 'interview') {
                $interview['interview_completion'] ??= ['session_id' => $sessionId, 'attempt_id' => $attemptId];
                if ($interview['status'] === 'OPEN') { $interview['status'] = 'COMPLETED'; }
            }
        }
    }

    private function source(array $state, string $intakeId, string $phase): array
    {
        return (new FormationSessionAuthority($this->signatures, $this->personnel, $this->clock))->source($state, $intakeId, $phase);
    }
    private function validateSession(array $state, array $session, bool $requireOpen = true): array
    {
        return (new FormationSessionAuthority($this->signatures, $this->personnel, $this->clock))->validateSession($state, $session, $requireOpen);
    }
    private function wasRefused(array $session): bool
    {
        return (new FormationSessionAuthority($this->signatures, $this->personnel, $this->clock))->wasRefused($session);
    }
    private function request(array $state, array $session, array $source): array
    {
        return (new FormationSessionAuthority($this->signatures, $this->personnel, $this->clock))->request($state, $session, $source);
    }
}
