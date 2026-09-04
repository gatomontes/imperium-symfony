<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Consumes exact typed issuance custody and publishes only its predetermined result. */
final readonly class NativeEffectReconciliationAuthorizedIssuanceService
{
    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private AuthorityConsumptionStore $consumptions;

    public function __construct(
        private NativeState $state,
        private NativeEffectReconciliationIssuanceAuthorityResolver $resolver,
        private ?\Closure $checkpoint = null,
    ) {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
        $this->consumptions = new AuthorityConsumptionStore($this->records, $this->atomic);
    }

    public function issue(NativeEffectReconciliationIssuanceAuthorityCapability $capability, int $at): array
    {
        $scope = 'reconciliation-issuance-root:'.hash('sha256', $capability->targetAuthorityId);

        return $this->state->locked(fn (): array => $this->atomic->run($scope, function () use ($capability, $at): array {
            $evidence = $this->resolver->consume($capability, $at);
            $issuanceAuthority = $evidence['issuance_authority'];
            $decision = $evidence['decision'];
            $source = $evidence['source'];
            $targetAuthority = (new NativeEffectReconciliationAuthorityRecordFactory())->build(
                $source,
                $decision['effective_at'],
                $decision['expires_at'],
            );
            if ($targetAuthority['authority_id'] !== $capability->targetAuthorityId
                || $targetAuthority['record_digest'] !== $capability->targetAuthorityDigest) {
                throw new \RuntimeException('CNE646_ISSUANCE_TARGET_CONFLICT');
            }
            $this->consumptions->consume(
                'reconciliation-issuance-target-'.$targetAuthority['authority_id'],
                $issuanceAuthority['issuance_authority_id'],
                $issuanceAuthority['record_digest'],
                $targetAuthority['record_digest'],
                new \DateTimeImmutable('@'.$at),
            );
            $consumption = $this->consumptions->consume(
                $issuanceAuthority['issuance_authority_id'],
                $issuanceAuthority['issuance_authority_id'],
                $issuanceAuthority['record_digest'],
                $targetAuthority['authority_id'],
                new \DateTimeImmutable('@'.$at),
            );
            if (null !== $this->checkpoint) { ($this->checkpoint)('issuance-authority.consumed'); }
            $storedAuthority = $this->records->put(
                NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES,
                $targetAuthority['authority_id'],
                $targetAuthority,
            );
            if (null !== $this->checkpoint) { ($this->checkpoint)('authority.published'); }
            $issuance = $this->records->put(
                NativeEffectReconciliationAuthorityIssuanceService::ISSUANCES,
                $targetAuthority['issuance_id'],
                [
                    'schema' => NativeEffectReconciliationAuthorityIssuanceV2Contract::SCHEMA,
                    'issuance_id' => $targetAuthority['issuance_id'],
                    'issuance_decision' => NativeState::ref($decision, 'decision_id'),
                    'issuance_authority' => NativeState::ref($issuanceAuthority, 'issuance_authority_id'),
                    'issuance_authority_consumption' => NativeState::ref($consumption, 'consumption_id'),
                    'issued_authority' => NativeState::ref($storedAuthority, 'authority_id'),
                    'source_native_authority' => $targetAuthority['source_native_authority'],
                    'source_native_principal' => $targetAuthority['source_native_principal'],
                    'source_native_transition' => $targetAuthority['source_native_transition'],
                    'effect_admission' => $targetAuthority['effect_admission'],
                    'issuer_service' => NativeEffectReconciliationAuthorityV2Contract::ISSUER_SERVICE,
                    'issued_at' => $at,
                    'authority_issued' => true,
                    'provider_invocation_performed' => false,
                    'credential_resolution_performed' => false,
                    'callback_invocation_performed' => false,
                    'external_io_performed' => false,
                    'continuing_authority' => false,
                    'sealed' => true,
                ],
            );

            return [
                'decision' => $decision,
                'issuance_authority' => $issuanceAuthority,
                'issuance_authority_consumption' => $consumption,
                'authority' => $storedAuthority,
                'issuance' => $issuance,
            ];
        }));
    }
}
