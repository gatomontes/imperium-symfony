<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimContract;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimService;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityConsumer;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderBindingActivationDecisionService
{
    public const string DECISIONS = 'var/imperium/imperator/provider-binding-activation-decisions';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
    }

    public function decide(string $callerAuthorityId, string $claimId, string $bindingId, string $disposition, string $rationale, string $limitations, \DateTimeImmutable $expiresAt, \DateTimeImmutable $decidedAt): array
    {
        $disposition = strtoupper(trim($disposition)); $rationale = trim($rationale); $limitations = trim($limitations);
        if (!preg_match('/^deterministic-execution-claim-[a-f0-9]{20}$/', $claimId) || !preg_match('/^provider-implementation-binding-[a-f0-9]{20}$/', $bindingId) || !in_array($disposition, ProviderBindingActivationIssuanceContract::DISPOSITIONS, true) || '' === $rationale || '' === $limitations || $expiresAt <= $decidedAt) throw new \InvalidArgumentException('PBA200_ACTIVATION_DECISION_INVALID');
        $claim = $this->validator->read($this->root.'/'.DeterministicExecutionClaimService::CLAIMS.'/'.$claimId.'.json', 'PBA201_EXECUTION_CLAIM_ABSENT');
        $binding = $this->validator->read($this->root.'/'.ProviderImplementationBindingService::BINDINGS.'/'.$bindingId.'.json', 'PBA202_PROVIDER_BINDING_ABSENT');
        if (!$this->validator->isIntact($claim) || DeterministicExecutionClaimContract::REQUIRED_FIELDS !== array_keys($claim) || DeterministicExecutionClaimContract::SCHEMA !== ($claim['schema'] ?? null) || 'CLAIMED_PRE_IO' !== ($claim['effect']['checkpoint'] ?? null) || false !== ($claim['effect']['external_io_started'] ?? null) || new \DateTimeImmutable($claim['expires_at']) <= $decidedAt || !$this->validator->isIntact($binding) || ProviderImplementationBindingContract::REQUIRED_FIELDS !== array_keys($binding) || ProviderImplementationBindingContract::SCHEMA !== ($binding['schema'] ?? null) || ($binding['instance_id'] ?? null) !== ($claim['instance_id'] ?? null) || 'BOUND_INACTIVE' !== ($binding['status'] ?? null) || new \DateTimeImmutable($binding['validity']['effective_at']) > $decidedAt || new \DateTimeImmutable($binding['validity']['expires_at']) <= $decidedAt || ($binding['scope']['authorization_target_id'] ?? null) !== ($claim['source_authorization']['id'] ?? null) || ($binding['scope']['authorization_target_digest'] ?? null) !== ($claim['source_authorization']['digest'] ?? null) || ($binding['scope']['operation'] ?? null) !== ($claim['request']['operation'] ?? null) || $expiresAt > new \DateTimeImmutable($claim['expires_at']) || $expiresAt > new \DateTimeImmutable($binding['validity']['expires_at'])) throw new \RuntimeException('PBA203_ACTIVATION_BASIS_INVALID');
        $caller = (new DeterministicTransitionCallerAuthorityConsumer($this->root))->consume($callerAuthorityId, 'DECIDE_EXACT_PROVIDER_BINDING_ACTIVATION', ['id' => $claimId.'|'.$bindingId, 'digest' => hash('sha256', CanonicalJson::encode([$claim['record_digest'], $binding['record_digest']]))], self::class, $decidedAt);
        $decisionId = 'provider-binding-activation-decision-'.substr(hash('sha256', CanonicalJson::encode([$claimId, $claim['record_digest'], $bindingId, $binding['record_digest'], $disposition, $rationale, $limitations, $expiresAt->format(DATE_ATOM)])), 0, 20);
        $issuanceAuthorityId = 'provider-binding-activation-issuance-authority-'.substr(hash('sha256', $decisionId.'|'.$claim['record_digest'].'|'.$binding['record_digest']), 0, 20);
        $issuanceAuthority = 'AUTHORIZED' === $disposition ? ['authority_id' => $issuanceAuthorityId, 'authority_single_use' => true, 'authority_exercisable' => true, 'issuer_service' => 'imperator.provider-binding-activation-authority-issuer', 'permitted_transition' => 'ISSUE_EXACT_PROVIDER_BINDING_ACTIVATION_AUTHORITY', 'execution_claim_digest' => $claim['record_digest'], 'provider_binding_digest' => $binding['record_digest'], 'expires_at' => $expiresAt->format(DATE_ATOM), 'consumed' => false, 'continuing_authority' => false] : null;
        $record = ['schema' => ProviderBindingActivationIssuanceContract::DECISION_SCHEMA, 'decision_id' => $decisionId, 'instance_id' => $claim['instance_id'], 'source_effect_authorization' => $claim['source_authorization'], 'execution_claim' => ['id' => $claimId, 'digest' => $claim['record_digest'], 'schema' => $claim['schema']], 'provider_binding' => ['id' => $bindingId, 'digest' => $binding['record_digest'], 'schema' => $binding['schema']], 'actor' => ['kind' => 'imperator', 'id' => $caller['authority']['principal']['principal_id']], 'disposition' => $disposition, 'rationale' => $rationale, 'limitations' => $limitations, 'issuance_authority' => $issuanceAuthority, 'decided_at' => $decidedAt->format(DATE_ATOM), 'expires_at' => $expiresAt->format(DATE_ATOM), 'external_action_performed' => false, 'sealed' => true];
        return $this->records->put(self::DECISIONS, $decisionId, $record);
    }
}
