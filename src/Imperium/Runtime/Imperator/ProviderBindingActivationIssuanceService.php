<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\ProviderTransition\{NativeBindingReader, NativeState};
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimContract;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimService;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityConsumer;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderBindingActivationIssuanceService
{
    public const string ISSUANCES = 'var/imperium/imperator/provider-binding-activation-authority-issuances';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root, new NativeBindingReader(new NativeState($root)));
        $this->atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $this->atomic);
    }

    public function issue(string $callerAuthorityId, string $decisionId, \DateTimeImmutable $issuedAt): array
    {
        $reader = new NativeBindingReader(new NativeState($this->root));
        return $reader->legacy(function () use ($reader, $callerAuthorityId, $decisionId, $issuedAt): array {
            return $this->legacyIssue($callerAuthorityId, $decisionId, $issuedAt);
        });
    }

    private function legacyIssue(string $callerAuthorityId, string $decisionId, \DateTimeImmutable $issuedAt): array
    {
        if (!preg_match('/^provider-binding-activation-decision-[a-f0-9]{20}$/', $decisionId)) throw new \InvalidArgumentException('PBA300_ACTIVATION_DECISION_ID_INVALID');
        $decision = $this->validator->read($this->root.'/'.ProviderBindingActivationDecisionService::DECISIONS.'/'.$decisionId.'.json', 'PBA301_ACTIVATION_DECISION_ABSENT');
        $authority = $decision['issuance_authority'] ?? null;
        if (!$this->validator->isIntact($decision) || ProviderBindingActivationIssuanceContract::REQUIRED_DECISION_FIELDS !== array_keys($decision) || ProviderBindingActivationIssuanceContract::DECISION_SCHEMA !== ($decision['schema'] ?? null) || $decisionId !== ($decision['decision_id'] ?? null) || 'AUTHORIZED' !== ($decision['disposition'] ?? null) || !is_array($authority) || ProviderBindingActivationIssuanceContract::REQUIRED_ISSUANCE_AUTHORITY_FIELDS !== array_keys($authority) || true !== ($authority['authority_single_use'] ?? null) || true !== ($authority['authority_exercisable'] ?? null) || false !== ($authority['consumed'] ?? null) || false !== ($authority['continuing_authority'] ?? null) || 'ISSUE_EXACT_PROVIDER_BINDING_ACTIVATION_AUTHORITY' !== ($authority['permitted_transition'] ?? null) || new \DateTimeImmutable($decision['decided_at']) > $issuedAt || new \DateTimeImmutable($authority['expires_at']) <= $issuedAt) throw new \RuntimeException('PBA302_ACTIVATION_DECISION_NOT_ISSUABLE');
        $caller = (new DeterministicTransitionCallerAuthorityConsumer($this->root))->consume($callerAuthorityId, 'ISSUE_EXACT_PROVIDER_BINDING_ACTIVATION_AUTHORITY', ['id' => $decisionId, 'digest' => $decision['record_digest']], self::class, $issuedAt);
        $claimId = $decision['execution_claim']['id'];
        $bindingId = $decision['provider_binding']['id'];
        $claim = $this->validator->read($this->root.'/'.DeterministicExecutionClaimService::CLAIMS.'/'.$claimId.'.json', 'PBA303_EXECUTION_CLAIM_ABSENT');
        $binding = $this->validator->read($this->root.'/'.ProviderImplementationBindingService::BINDINGS.'/'.$bindingId.'.json', 'PBA304_PROVIDER_BINDING_ABSENT');
        if (!$this->validator->isIntact($claim) || DeterministicExecutionClaimContract::REQUIRED_FIELDS !== array_keys($claim) || DeterministicExecutionClaimContract::SCHEMA !== ($claim['schema'] ?? null) || !$this->validator->isIntact($binding) || ProviderImplementationBindingContract::REQUIRED_FIELDS !== array_keys($binding) || ProviderImplementationBindingContract::SCHEMA !== ($binding['schema'] ?? null) || ($decision['execution_claim']['digest'] ?? null) !== $claim['record_digest'] || ($decision['provider_binding']['digest'] ?? null) !== $binding['record_digest'] || ($authority['execution_claim_digest'] ?? null) !== $claim['record_digest'] || ($authority['provider_binding_digest'] ?? null) !== $binding['record_digest'] || 'CLAIMED_PRE_IO' !== ($claim['effect']['checkpoint'] ?? null) || false !== ($claim['effect']['external_io_started'] ?? null) || new \DateTimeImmutable($claim['expires_at']) <= $issuedAt || 'BOUND_INACTIVE' !== ($binding['status'] ?? null) || new \DateTimeImmutable($binding['validity']['effective_at']) > $issuedAt || new \DateTimeImmutable($binding['validity']['expires_at']) <= $issuedAt || $claim['source_authorization'] !== $decision['source_effect_authorization'] || ($binding['scope']['authorization_target_id'] ?? null) !== ($claim['source_authorization']['id'] ?? null) || ($binding['scope']['authorization_target_digest'] ?? null) !== ($claim['source_authorization']['digest'] ?? null) || ($binding['scope']['operation'] ?? null) !== ($claim['request']['operation'] ?? null)) throw new \RuntimeException('PBA305_ACTIVATION_LINEAGE_INVALID');
        $activationAuthorityId = 'provider-binding-activation-authority-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $decision['record_digest'], $authority['authority_id']])), 0, 20);
        $activationAuthority = ['schema' => ProviderBindingActivationAuthorityContract::SCHEMA, 'authority_id' => $activationAuthorityId, 'instance_id' => $decision['instance_id'], 'source_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest'], 'schema' => $decision['schema']], 'tool_authority' => $binding['tool_operation'], 'effect_authorization' => ['id' => $claim['source_authorization']['id'], 'digest' => $claim['source_authorization']['digest'], 'schema' => $claim['source_authorization']['schema']], 'execution_claim' => $decision['execution_claim'], 'provider_binding' => $decision['provider_binding'], 'assurance_profile' => $binding['assurance_profile'], 'destination_policy' => $binding['destination_policy'], 'scope' => ['execution_id' => $claim['execution_identity']['execution_id'], 'operation' => $claim['request']['operation'], 'exact_destination' => $claim['request']['destination'], 'provider_substitution_permitted' => false], 'issued_at' => $issuedAt->format(DATE_ATOM), 'expires_at' => $authority['expires_at'], 'authority_single_use' => true, 'authority_exercisable' => true, 'consumed' => false, 'continuing_authority' => false, 'sealed' => true];
        $activationAuthority['record_digest'] = hash('sha256', CanonicalJson::encode($activationAuthority));
        $issuanceId = 'provider-binding-activation-authority-issuance-'.substr(hash('sha256', CanonicalJson::encode([$activationAuthorityId, $activationAuthority['record_digest'], $authority['authority_id']])), 0, 20);
        $record = ['schema' => ProviderBindingActivationIssuanceContract::ISSUANCE_SCHEMA, 'issuance_id' => $issuanceId, 'instance_id' => $decision['instance_id'], 'source_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']], 'consumed_issuance_authority' => ['authority_id' => $authority['authority_id'], 'consumed' => true, 'consumed_at' => $issuedAt->format(DATE_ATOM), 'continuing_authority' => false], 'issued_activation_authority' => $activationAuthority, 'issuer' => ['service' => 'imperator.provider-binding-activation-authority-issuer', 'principal_id' => $caller['authority']['principal']['principal_id']], 'issued_at' => $issuedAt->format(DATE_ATOM), 'authority_issued' => true, 'provider_binding_activated' => false, 'credential_capability_issued' => false, 'external_action_performed' => false, 'sealed' => true];

        return $this->atomic->run('provider-binding-activation-issuance:'.$authority['authority_id'], function () use ($authority, $issuanceId, $record): array {
            foreach (glob($this->root.'/'.self::ISSUANCES.'/*.json') ?: [] as $path) {
                $prior = $this->validator->read($path, 'PBA306_ACTIVATION_ISSUANCE_CONFLICT');
                if (($prior['consumed_issuance_authority']['authority_id'] ?? null) === $authority['authority_id']) {
                    if (($prior['issuance_id'] ?? null) !== $issuanceId) throw new \RuntimeException('PBA306_ACTIVATION_ISSUANCE_CONFLICT');
                    return $prior;
                }
            }
            return $this->records->put(self::ISSUANCES, $issuanceId, $record);
        });
    }
}
