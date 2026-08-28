<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\OutboundEmailAuthorizationRequestService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OutboundEmailDecisionService
{
    public const string DECISIONS = 'var/imperium/imperator/deterministic-outbound-email-decisions';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
    }

    public function decide(string $requestId, string $disposition, string $rationale, string $limitations, \DateTimeImmutable $expiresAt, \DateTimeImmutable $decidedAt): array
    {
        $disposition = strtoupper(trim($disposition)); $rationale = trim($rationale); $limitations = trim($limitations);
        if (!preg_match('/^outbound-email-request-[a-f0-9]{20}$/', $requestId) || !in_array($disposition, OutboundEmailAuthorizationIssuanceContract::DISPOSITIONS, true) || '' === $rationale || '' === $limitations || $expiresAt <= $decidedAt) throw new \InvalidArgumentException('IGD200_OUTBOUND_EMAIL_DECISION_INVALID');
        $request = $this->validator->read($this->root.'/'.OutboundEmailAuthorizationRequestService::REQUESTS.'/'.$requestId.'.json', 'IGD201_OUTBOUND_EMAIL_REQUEST_ABSENT');
        if (!$this->validator->isIntact($request) || OutboundEmailAuthorizationIssuanceContract::REQUIRED_REQUEST_FIELDS !== array_keys($request) || OutboundEmailAuthorizationIssuanceContract::REQUEST_SCHEMA !== ($request['schema'] ?? null) || $requestId !== ($request['request_id'] ?? null) || true !== ($request['authority_requested'] ?? null) || false !== ($request['authority_granted'] ?? null) || new \DateTimeImmutable($request['requested_at']) > $decidedAt || new \DateTimeImmutable($request['expires_at']) <= $decidedAt || $expiresAt > new \DateTimeImmutable($request['expires_at'])) throw new \RuntimeException('IGD202_OUTBOUND_EMAIL_REQUEST_INVALID');
        $decisionId = 'outbound-email-decision-'.substr(hash('sha256', CanonicalJson::encode([$requestId, $request['record_digest'], $disposition, $rationale, $limitations, $decidedAt->format(DATE_ATOM), $expiresAt->format(DATE_ATOM)])), 0, 20);
        $authorityId = 'outbound-email-issuance-authority-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $requestId, $request['record_digest']])), 0, 20);
        $authorized = 'AUTHORIZED' === $disposition;
        $issuanceAuthority = $authorized ? ['authority_id' => $authorityId, 'authority_single_use' => true, 'authority_exercisable' => true, 'issuer_service' => 'imperator.outbound-email-authorization-issuer', 'permitted_transition' => 'ISSUE_ONE_EXACT_DETERMINISTIC_OUTBOUND_EMAIL_AUTHORIZATION', 'source_request_digest' => $request['record_digest'], 'scope_digest' => hash('sha256', CanonicalJson::encode($request['scope'])), 'expires_at' => $expiresAt->format(DATE_ATOM), 'consumed' => false, 'continuing_authority' => false] : null;
        $record = ['schema' => OutboundEmailAuthorizationIssuanceContract::DECISION_SCHEMA, 'decision_id' => $decisionId, 'instance_id' => $request['instance_id'], 'source_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'actor' => ['kind' => 'imperator', 'id' => 'imperator-development-root'], 'disposition' => $disposition, 'rationale' => $rationale, 'limitations' => $limitations, 'issuance_authority' => $issuanceAuthority, 'decided_at' => $decidedAt->format(DATE_ATOM), 'expires_at' => $expiresAt->format(DATE_ATOM), 'external_action_performed' => false, 'sealed' => true];

        return $this->atomic->run('iron-gate-email-decision:'.$requestId, function () use ($requestId, $decisionId, $record): array {
            foreach (glob($this->root.'/'.self::DECISIONS.'/*.json') ?: [] as $path) { $prior = $this->validator->read($path, 'IGD203_OUTBOUND_EMAIL_DECISION_CONFLICT'); if (($prior['source_request']['id'] ?? null) === $requestId) { if (($prior['decision_id'] ?? null) !== $decisionId) throw new \RuntimeException('IGD203_OUTBOUND_EMAIL_DECISION_CONFLICT'); return $prior; } }
            return $this->records->put(self::DECISIONS, $decisionId, $record);
        });
    }
}
