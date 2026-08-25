<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\ProviderInvocationJournalService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\MutableStateStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionTurnRecoveryService
{
    private const AUTHORIZATIONS = 'var/imperium/runtime/provider-turn-recovery-authorizations';
    private const CLAIMS = 'var/imperium/runtime/provider-invocations';
    private const ENVELOPES = 'var/imperium/runtime/provider-response-envelopes';
    private const JOURNAL = 'var/imperium/runtime/provider-invocation-journal';
    private const ACTIVATIONS = 'var/imperium/offices/clavium/delegate-mission-provider-invocation-activations';
    private const COMMISSIONS = 'var/imperium/offices/curia/delegate-mission-bounded-cognition-commissions';
    private const TURNS = 'var/imperium/operational/delegate-mission-bounded-cognition-turns';

    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private MutableStateStore $state;
    private AuthorityConsumptionStore $authorities;
    private ProviderInvocationJournalService $journal;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $root,
        ?AtomicTransition $atomic = null,
        ?ImmutableRecordStore $records = null,
        ?MutableStateStore $state = null,
        ?AuthorityConsumptionStore $authorities = null,
        ?ProviderInvocationJournalService $journal = null,
    ) {
        $this->atomic = $atomic ?? new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $this->atomic);
        $this->state = $state ?? new MutableStateStore($root, $this->atomic);
        $this->authorities = $authorities ?? new AuthorityConsumptionStore($this->records, $this->atomic);
        $this->journal = $journal ?? new ProviderInvocationJournalService($root, $this->atomic, $this->state, $this->records);
    }

    public function recover(string $authorizationId, \DateTimeImmutable $at): array
    {
        if (!preg_match('/^provider-turn-recovery-[a-f0-9]{20}$/', $authorizationId)) {
            throw new \RuntimeException('CT330_DELEGATE_TURN_RECOVERY_AUTHORIZATION_INVALID');
        }
        try {
            $authorization = $this->records->read(self::AUTHORIZATIONS, $authorizationId);
        } catch (\RuntimeException) {
            throw new \RuntimeException('CT330_DELEGATE_TURN_RECOVERY_AUTHORIZATION_INVALID');
        }

        return $this->atomic->run('provider-turn-recovery:'.hash('sha256', $authorizationId), fn (): array => $this->recoverLocked($authorization, $at));
    }

    private function recoverLocked(array $authorization, \DateTimeImmutable $at): array
    {
        $claimId = $authorization['claim']['id'] ?? null;
        $authority = $authorization['recovery_authority'] ?? [];
        if ('AUTHORIZED_PENDING_PROVIDER_TURN_FORWARD_RECOVERY' !== ($authorization['status'] ?? null)
            || !is_string($claimId)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || !is_string($authority['authority_id'] ?? null)
            || !is_string($authority['expires_at'] ?? null)
            || new \DateTimeImmutable($authority['expires_at']) <= $at
            || true === ($authorization['provider_invocation_authority'] ?? null)) {
            throw new \RuntimeException('CT330_DELEGATE_TURN_RECOVERY_AUTHORIZATION_INVALID');
        }

        try {
            $claim = $this->records->read(self::CLAIMS, $claimId);
            $envelope = $this->records->read(self::ENVELOPES, $claimId);
            $activation = $this->records->read(self::ACTIVATIONS, $claim['source_activation']['id']);
            $commission = $this->records->read(self::COMMISSIONS, $claim['target']['commission_id']);
            $journal = $this->state->read(self::JOURNAL.'/'.$claimId.'.json');
        } catch (\RuntimeException $exception) {
            throw new \RuntimeException('CT331_DELEGATE_TURN_RECOVERY_EVIDENCE_INVALID', 0, $exception);
        }
        if (($authorization['claim']['digest'] ?? null) !== $claim['record_digest']
            || ($authorization['response_envelope']['digest'] ?? null) !== $envelope['record_digest']
            || ($envelope['claim'] ?? null) !== ['id' => $claimId, 'digest' => $claim['record_digest']]
            || ($activation['record_digest'] ?? null) !== ($claim['source_activation']['digest'] ?? null)
            || ($activation['target'] ?? null) !== ($claim['target'] ?? null)
            || ($commission['commission_id'] ?? null) !== ($claim['target']['commission_id'] ?? null)
            || ($journal['claim']['digest'] ?? null) !== $claim['record_digest']
            || false !== ($envelope['automatic_provider_replay_permitted'] ?? null)) {
            throw new \RuntimeException('CT331_DELEGATE_TURN_RECOVERY_EVIDENCE_INVALID');
        }
        $response = $envelope['response'] ?? null;
        if (!is_string($response) || 'sha256:'.hash('sha256', $response) !== ($envelope['provider_response_identity'] ?? null)) {
            throw new \RuntimeException('CT331_DELEGATE_TURN_RECOVERY_EVIDENCE_INVALID');
        }
        if ('INVOCATION_IN_FLIGHT' === ($journal['status'] ?? null)) {
            $journal = $this->journal->sealResponse($claim, $response, $at);
        }
        if ('PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING' !== ($journal['status'] ?? null)
            || ($journal['provider_response_identity'] ?? null) !== $envelope['provider_response_identity']) {
            throw new \RuntimeException('CT331_DELEGATE_TURN_RECOVERY_EVIDENCE_INVALID');
        }

        $payload = $this->payload($response);
        $activationId = $activation['activation_id'];
        $turnAuthorityId = $claim['turn_authority_consumption']['authority_id'];
        $turnId = 'delegate-mission-bounded-cognition-turn-'.substr(hash('sha256', CanonicalJson::encode([$activationId, $activation['record_digest'], $turnAuthorityId, $payload])), 0, 20);
        try {
            $existing = $this->records->read(self::TURNS, $turnId);
            if (($existing['source_invocation_claim'] ?? null) !== ['id' => $claimId, 'digest' => $claim['record_digest']]
                || ($existing['result'] ?? null) !== $payload) {
                throw new \RuntimeException('CT332_DELEGATE_TURN_RECOVERY_CONFLICT');
            }

            return $existing;
        } catch (\RuntimeException $exception) {
            if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $exception->getMessage()) {
                throw $exception;
            }
        }

        $this->authorities->consume($authority['authority_id'], $authorization['authorization_id'], $authorization['record_digest'], 'citadel.delegate-turn-recovery', $at);
        $next = 'delegate-mission-cognition-result-disposition-authority-'.substr(hash('sha256', CanonicalJson::encode([$turnId, $payload, $commission['record_digest']])), 0, 20);

        return $this->records->put(self::TURNS, $turnId, [
            'schema' => 'imperium.citadel-delegate-mission-bounded-cognition-turn/v1',
            'turn_id' => $turnId,
            'instance_id' => $activation['instance_id'],
            'source_invocation_claim' => ['id' => $claimId, 'digest' => $claim['record_digest']],
            'source_provider_response_envelope' => ['id' => $envelope['envelope_id'], 'digest' => $envelope['record_digest']],
            'source_activation' => ['id' => $activationId, 'digest' => $activation['record_digest']],
            'source_commission' => ['id' => $commission['commission_id'], 'digest' => $commission['record_digest']],
            'source_model_binding' => $activation['source_model_binding'],
            'source_access_attestation' => $activation['source_access_attestation'],
            'target' => $activation['target'],
            'model' => $activation['model'],
            'turn_authority' => ['id' => $turnAuthorityId, 'consumed' => true, 'continuing_authority' => false],
            'credential_lease' => ['id' => $claim['lease_consumption']['lease_id'], 'consumed' => true, 'continuing_authority' => false],
            'result' => $payload,
            'performed_at' => $envelope['sealed_at'],
            'recovery' => ['authorization_id' => $authorization['authorization_id'], 'recovered_at' => $at->format(DATE_ATOM), 'provider_reinvoked' => false],
            'status' => 'DELEGATE_MISSION_BOUNDED_COGNITION_TURN_COMPLETE_PENDING_CURIA_DISPOSITION',
            'provider_invoked' => true,
            'cognition_performed' => true,
            'maximum_turns_consumed' => true,
            'curia_result_disposition_authority' => ['authority_id' => $next, 'authority_single_use' => true, 'authority_exercisable' => true, 'holder' => 'curia.seneschal', 'consumed' => false],
            'credential_use_authority' => false,
            'provider_invocation_authority' => false,
            'tool_use_authority' => false,
            'perimeter_crossing_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'continuing_turn_authority' => false,
            'sealed' => true,
        ]);
    }

    private function payload(string $response): array
    {
        $text = trim($response);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $text) ?? $text;
        }
        try {
            $payload = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('CT333_DELEGATE_TURN_RECOVERY_PAYLOAD_INVALID', 0, $exception);
        }
        if (!is_array($payload)
            || array_keys($payload) !== ['disposition', 'output', 'evidence_references', 'uncertainties', 'stop_condition_triggered', 'stop_rationale']
            || !in_array($payload['disposition'], ['COMPLETED', 'STOPPED', 'FAILED'], true)
            || !is_string($payload['output'])
            || !is_array($payload['evidence_references'])
            || !is_array($payload['uncertainties'])
            || !is_bool($payload['stop_condition_triggered'])
            || !(null === $payload['stop_rationale'] || is_string($payload['stop_rationale']))) {
            throw new \RuntimeException('CT333_DELEGATE_TURN_RECOVERY_PAYLOAD_INVALID');
        }

        return $payload;
    }
}
