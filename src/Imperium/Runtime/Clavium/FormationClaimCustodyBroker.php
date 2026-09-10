<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Clavium;
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal as J, FormationSessionAuthority, FormationPreparedOperation, FormationWireAdapter, SessionExposure};
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Imperium\Runtime\Clock;

/** One-use custody over the genuine aggregate. No caller callback, root or capability ingress.
 * Each irreversible checkpoint is committed before its possible effect. No lock
 * spans credential infrastructure or external dispatch; uncertain checkpoints never retry.
 */
final readonly class FormationClaimCustodyBroker
{
    public function __construct(private J $journal, private FormationSessionAuthority $authority,
        private FormationSessionLeaseService $leases,
        private CredentialBroker $credentials, private FormationWireAdapter $adapter,
        private ProviderResponseEnvelopeService $responses, private Clock $clock) {}

    public function invoke(array $claim, array $request, array $terms): array
    {
        try { return $this->deliver($claim,$request,$terms); }
        catch (\Throwable $error) {
            throw new \RuntimeException('FC099_CUSTODY_REFUSED_OR_OUTCOME_UNKNOWN'); // no infrastructure message or previous exception
        }
    }

    private function deliver(array $claim, array $request, array $terms): array
    {
        // Pure adapter refusal occurs before journal/credential infrastructure.
        $operation = $this->adapter->prepare($request,$terms);
        FormationPreparedOperation::validate($operation,$request,$terms,$claim['maximum'] ?? []);
        $sid = $claim['session_id'] ?? null; $aid = $claim['attempt_id'] ?? null;
        if (!is_string($sid) || !is_string($aid)) { throw new \RuntimeException('FC004_RETAINED_CLAIM_REQUIRED'); }
        $this->journal->change(function (array &$state) use ($claim,$request,$terms,$operation,$sid,$aid): void {
            $this->validate($state,$claim,$request,$terms,$operation);
            $attempt = &$state['sessions'][$sid]['attempts'][$aid];
            if (isset($attempt['custody'])) { throw new \RuntimeException('FC005_CUSTODY_ALREADY_DELIVERED_NO_RETRY'); }
            $attempt['custody'] = ['schema'=>'imperium.formation-claim-custody/v1', 'claim_digest'=>$claim['record_digest'],
                'operation_digest'=>J::digest($operation), 'status'=>'DELIVERY_COMMITTED_OUTCOME_UNCERTAIN',
                'accepted_at'=>$this->clock->now()->getTimestamp(), 'automatic_retry_permitted'=>false];
        });
        $authorization = $operation['authorization'];
        $capability = $this->credentials->issue($authorization['credential_reference'],$claim['claim_id'],
            $authorization['operation'],new \DateTimeImmutable('@'.$claim['expires_at']),1);
        if ($capability->credentialReferenceDigest !== hash('sha256',$authorization['credential_reference'])
            || $capability->commissionId !== $claim['claim_id'] || $capability->operation !== $authorization['operation']
            || $capability->maxUses !== 1 || $capability->expiresAt->getTimestamp() !== $claim['expires_at']) {
            throw new \RuntimeException('FC006_CAPABILITY_SCOPE_MISMATCH');
        }
        $this->advance($claim,$request,$terms,$operation,'DELIVERY_COMMITTED_OUTCOME_UNCERTAIN','CONSUMPTION_COMMITTED_OUTCOME_UNCERTAIN');
        $result = $this->credentials->consume($capability, function (#[\SensitiveParameter] mixed $authentication) use ($claim,$request,$terms,$operation): array {
            // Even a replaying infrastructure callback must cross this durable fence.
            if (J::digest($this->adapter->prepare($request,$terms)) !== J::digest($operation)) { throw new \RuntimeException('FC003_PREPARED_OPERATION_MISMATCH'); }
            $this->advance($claim,$request,$terms,$operation,'CONSUMPTION_COMMITTED_OUTCOME_UNCERTAIN','DISPATCH_COMMITTED_OUTCOME_UNCERTAIN');
            if (!is_string($authentication) || $authentication === '') { throw new \RuntimeException('FC007_SECRET_OR_CONTEXT_REFUSED'); }
            $result = $this->adapter->dispatch($operation,$authentication);
            if (!J::keys($result,['response','usage','provider_response_id','operation_digest','provenance'])
                || !is_string($result['response']) || !is_string($result['provider_response_id']) || $result['provider_response_id'] === ''
                || !is_string($result['provenance']) || $result['provenance'] !== $operation['authorization']['adapter']
                || $result['operation_digest'] !== J::digest($operation) || !is_array($result['usage'])) { throw new \RuntimeException('CMF033_USAGE_UNTRUSTWORTHY'); }
            foreach (['response','provider_response_id','provenance','operation_digest'] as $field) {
                if (str_contains($result[$field],$authentication)) { throw new \RuntimeException('FC007_SECRET_OR_CONTEXT_REFUSED'); }
            }
            $sample = ['maximum'=>$claim['maximum'],'settled'=>null]; SessionExposure::settle($sample,$result['usage']);
            if ($this->clock->now()->getTimestamp() > $claim['expires_at']) { throw new \RuntimeException('CMF068_LEASE_CHANGED_OR_EXPIRED'); }
            // Retain attribution before publishing the envelope, so interruption
            // cannot leave a recoverable body with its provider ID only in memory.
            $this->journal->change(function (array &$state) use ($claim,$operation,$result): void {
                $attempt = &$state['sessions'][$claim['session_id']]['attempts'][$claim['attempt_id']];
                if (($attempt['custody']['status'] ?? null) !== 'DISPATCH_COMMITTED_OUTCOME_UNCERTAIN'
                    || ($attempt['custody']['claim_digest'] ?? null) !== $claim['record_digest']
                    || ($attempt['custody']['operation_digest'] ?? null) !== J::digest($operation)) { throw new \RuntimeException('FC016_RETAINED_CUSTODY_REQUIRED'); }
                $attempt['custody'] += ['response_identity'=>'sha256:'.hash('sha256',$result['response']), 'provider_response_id'=>$result['provider_response_id'],
                    'usage'=>$result['usage'], 'provenance'=>$result['provenance']];
                $attempt['custody']['status'] = 'RESPONSE_VALIDATED_PENDING_ENVELOPE';
            });
            $envelope = $this->responses->seal($claim,$result['response'],$this->clock->now());
            $this->journal->change(function (array &$state) use ($claim,$envelope): void {
                $attempt = &$state['sessions'][$claim['session_id']]['attempts'][$claim['attempt_id']];
                if (($attempt['custody']['status'] ?? null) !== 'RESPONSE_VALIDATED_PENDING_ENVELOPE'
                    || $attempt['custody']['claim_digest'] !== $claim['record_digest']
                    || $attempt['custody']['response_identity'] !== $envelope['provider_response_identity']) { throw new \RuntimeException('FC016_RETAINED_CUSTODY_REQUIRED'); }
                $attempt['custody']['response_digest'] = $envelope['record_digest'];
                $attempt['custody']['status'] = 'RESPONSE_RETAINED';
            });
            return $result;
        });
        // Do not accept a fabricated consume() return without actual retained completion.
        $retained = $this->journal->read()['state']['sessions'][$sid]['attempts'][$aid]['custody'];
        $envelope = $this->responses->read($claim['claim_id']);
        if (($retained['status'] ?? null) !== 'RESPONSE_RETAINED' || $retained['response_digest'] !== $envelope['record_digest']
            || ($result['response'] ?? null) !== $envelope['response'] || ($result['usage'] ?? null) !== $retained['usage']
            || ($result['provider_response_id'] ?? null) !== $retained['provider_response_id']) { throw new \RuntimeException('FC016_RETAINED_CUSTODY_REQUIRED'); }
        return $result;
    }

    private function advance(array $claim,array $request,array $terms,array $operation,string $from,string $to): void
    {
        $this->journal->change(function(array &$state) use ($claim,$request,$terms,$operation,$from,$to): void {
            $this->validate($state,$claim,$request,$terms,$operation);
            $custody = &$state['sessions'][$claim['session_id']]['attempts'][$claim['attempt_id']]['custody'];
            if (($custody['status'] ?? null) !== $from || $custody['claim_digest'] !== $claim['record_digest']
                || $custody['operation_digest'] !== J::digest($operation)) { throw new \RuntimeException('FC005_CUSTODY_ALREADY_DELIVERED_NO_RETRY'); }
            $custody['status']=$to; $custody['last_boundary_at']=$this->clock->now()->getTimestamp();
        });
    }

    private function validate(array $state,array $claim,array $request,array $terms,array $operation): void
    {
        $session = $state['sessions'][$claim['session_id']] ?? null;
        $attempt = $session['attempts'][$claim['attempt_id']] ?? null;
        if (!is_array($session) || !is_array($attempt) || ($attempt['claim'] ?? null) !== $claim
            || ($claim['schema'] ?? null) !== 'imperium.citadel-session-call-claim/v2'
            || $attempt['status'] !== 'STARTED_OUTCOME_UNCERTAIN' || $attempt['request'] !== $request
            || $session['terms'] !== $terms || ($claim['prepared_operation'] ?? null) !== $operation
            || $claim['expires_at'] <= $this->clock->now()->getTimestamp()) { throw new \RuntimeException('FC004_RETAINED_CLAIM_REQUIRED'); }
        $source = $this->authority->validateSession($state,$session);
        $effect = match($session['phase']) { 'interview'=>'AUTHORIZE_INTERVIEW_SESSION','drafting'=>'AUTHORIZE_EXACT_DRAFTING','acceptance'=>'AUTHORIZE_RECEIVING_ASSESSMENT',default=>'' };
        if ($effect !== $session['effect'] || $session['session_id'] !== 'session-'.J::digest([$session['intake_id'],$session['phase'],$terms,$session['decision']])
            || $request !== $this->authority->request($state,$session,$source) || $attempt['fingerprint'] !== J::digest($request)
            || $session['per_call'] !== $terms['per_call'] || $session['total'] !== $terms['total']
            || $attempt['maximum'] !== $claim['maximum'] || $attempt['settled'] !== null
            || $session['provider_resource_decision'] !== \App\Imperium\Runtime\Imperator\GovernanceProviderResourceDecisionService::formationSession(
                $state,$terms,$session['decision'],$session['phase'],new \App\Imperium\Runtime\Citadel\Formation\FormationSignatures($this->journal,$this->clock))) {
            throw new \RuntimeException('FC008_AUTHORITY_BINDING_MISMATCH');
        }
        \App\Imperium\Runtime\Citadel\Formation\SharedExposure::formation($state,$claim['session_id'],$claim['maximum'],$claim['attempt_id'],$this->journal,$this->clock);
        $accounting = $session;
        unset($accounting['attempts'][$claim['attempt_id']]);
        SessionExposure::reserve($accounting,$claim['attempt_id'],$claim['maximum'],$attempt['fingerprint']);
        $derivation = $this->leases->derive($state,$session,$source['holder'],$request,$claim['maximum'],$claim['expires_at'],$claim['attempt_id'],$operation);
        $expected = ['schema'=>'imperium.citadel-session-call-claim/v2',
            'claim_id'=>'governance-cognition-invocation-claim-'.substr(J::digest([$claim['session_id'],$claim['attempt_id'],$request]),0,20),
            'session_id'=>$session['session_id'],'attempt_id'=>$claim['attempt_id'],'source_decision_digest'=>J::digest($session['decision']),
            'holder'=>$source['holder'],'request_digest'=>J::digest($request),'maximum'=>$attempt['maximum'],'expires_at'=>$claim['expires_at'],
            'derivation'=>$derivation,'derived_authority_consumed'=>true,'lease_consumed'=>true,'automatic_retry_permitted'=>false,
            'execution_authority'=>false,'prepared_operation'=>$operation];
        $expected['record_digest']=J::digest($expected);
        if ($claim !== $expected || $claim['expires_at'] > $terms['expires_at']) { throw new \RuntimeException('FC008_AUTHORITY_BINDING_MISMATCH'); }
        FormationPreparedOperation::validate($operation,$request,$terms,$claim['maximum']);
    }
}
