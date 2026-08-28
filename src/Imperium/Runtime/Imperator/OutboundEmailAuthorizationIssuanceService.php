<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\OutboundEmailAuthorizationRequestService;
use App\Imperium\Runtime\LaCortine\DeterministicOutboundEmailAuthorizationContract;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityConsumer;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OutboundEmailAuthorizationIssuanceService
{
    public const string ISSUANCES = 'var/imperium/imperator/outbound-email-authorization-issuances';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root); $this->atomic = new AtomicTransition($root); $this->records = new ImmutableRecordStore($root, $this->atomic);
    }

    public function issue(string $callerAuthorityId, string $decisionId, \DateTimeImmutable $issuedAt): array
    {
        if (!preg_match('/^outbound-email-decision-[a-f0-9]{20}$/', $decisionId)) throw new \InvalidArgumentException('IGI300_OUTBOUND_EMAIL_DECISION_ID_INVALID');
        $decision = $this->validator->read($this->root.'/'.OutboundEmailDecisionService::DECISIONS.'/'.$decisionId.'.json', 'IGI301_OUTBOUND_EMAIL_DECISION_ABSENT');
        if (!$this->validator->isIntact($decision) || OutboundEmailAuthorizationIssuanceContract::REQUIRED_DECISION_FIELDS !== array_keys($decision) || OutboundEmailAuthorizationIssuanceContract::DECISION_SCHEMA !== ($decision['schema'] ?? null) || $decisionId !== ($decision['decision_id'] ?? null) || 'AUTHORIZED' !== ($decision['disposition'] ?? null) || !is_array($authority = $decision['issuance_authority'] ?? null) || OutboundEmailAuthorizationIssuanceContract::REQUIRED_ISSUANCE_AUTHORITY_FIELDS !== array_keys($authority) || true !== ($authority['authority_single_use'] ?? null) || true !== ($authority['authority_exercisable'] ?? null) || false !== ($authority['consumed'] ?? null) || new \DateTimeImmutable($decision['decided_at']) > $issuedAt || new \DateTimeImmutable($authority['expires_at']) <= $issuedAt) throw new \RuntimeException('IGI302_OUTBOUND_EMAIL_DECISION_NOT_ISSUABLE');
        $caller = (new DeterministicTransitionCallerAuthorityConsumer($this->root))->consume($callerAuthorityId, 'ISSUE_EXACT_OUTBOUND_EMAIL_AUTHORIZATION', ['id' => $decisionId, 'digest' => $decision['record_digest']], self::class, $issuedAt);
        $requestId = $decision['source_request']['id'];
        $request = $this->validator->read($this->root.'/'.OutboundEmailAuthorizationRequestService::REQUESTS.'/'.$requestId.'.json', 'IGI303_OUTBOUND_EMAIL_REQUEST_ABSENT');
        if (!$this->validator->isIntact($request) || OutboundEmailAuthorizationIssuanceContract::REQUIRED_REQUEST_FIELDS !== array_keys($request) || ($decision['source_request']['digest'] ?? null) !== ($request['record_digest'] ?? null) || ($authority['source_request_digest'] ?? null) !== $request['record_digest'] || ($authority['scope_digest'] ?? null) !== hash('sha256', CanonicalJson::encode($request['scope']))) throw new \RuntimeException('IGI304_OUTBOUND_EMAIL_LINEAGE_INVALID');
        $authorizationId = 'outbound-email-authorization-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $decision['record_digest'], $authority['authority_id'], $request['record_digest']])), 0, 20);
        $authorization = ['schema' => DeterministicOutboundEmailAuthorizationContract::SCHEMA, 'authorization_id' => $authorizationId, 'instance_id' => $request['instance_id'], 'source_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest'], 'schema' => $decision['schema'], 'decision' => $decision['disposition'], 'decision_owner' => $decision['actor']], 'issuer' => ['actor_id' => $caller['authority']['principal']['principal_id'], 'office' => 'imperator', 'seat' => 'imperator', 'binding_id' => $caller['authority']['principal']['binding_id'], 'runtime_principal_id' => $caller['authority']['principal']['principal_id']], 'holder' => $request['holder'], 'scope' => $request['scope'], 'provider_safety' => $request['provider_safety'], 'single_use' => true, 'exercisable' => true, 'consumed' => false, 'issued_at' => $issuedAt->format(DATE_ATOM), 'expires_at' => $authority['expires_at'], 'continuing_authority' => false, 'sealed' => true];
        $authorization['record_digest'] = hash('sha256', CanonicalJson::encode($authorization));
        $issuanceId = 'outbound-email-authorization-issuance-'.substr(hash('sha256', CanonicalJson::encode([$authorizationId, $authorization['record_digest'], $authority['authority_id']])), 0, 20);
        $record = ['schema' => OutboundEmailAuthorizationIssuanceContract::ISSUANCE_SCHEMA, 'issuance_id' => $issuanceId, 'instance_id' => $request['instance_id'], 'source_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']], 'source_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'consumed_issuance_authority' => ['authority_id' => $authority['authority_id'], 'consumed' => true, 'consumed_at' => $issuedAt->format(DATE_ATOM), 'continuing_authority' => false], 'issued_authorization' => $authorization, 'issuer' => 'imperator.outbound-email-authorization-issuer', 'issued_at' => $issuedAt->format(DATE_ATOM), 'authority_issued' => true, 'external_action_performed' => false, 'sealed' => true];

        return $this->atomic->run('iron-gate-email-issuance:'.$authority['authority_id'], function () use ($authority, $issuanceId, $record): array {
            foreach (glob($this->root.'/'.self::ISSUANCES.'/*.json') ?: [] as $path) { $prior = $this->validator->read($path, 'IGI305_OUTBOUND_EMAIL_ISSUANCE_CONFLICT'); if (($prior['consumed_issuance_authority']['authority_id'] ?? null) === $authority['authority_id']) { if (($prior['issuance_id'] ?? null) !== $issuanceId) throw new \RuntimeException('IGI305_OUTBOUND_EMAIL_ISSUANCE_CONFLICT'); return $prior; } }
            return $this->records->put(self::ISSUANCES, $issuanceId, $record);
        });
    }
}
