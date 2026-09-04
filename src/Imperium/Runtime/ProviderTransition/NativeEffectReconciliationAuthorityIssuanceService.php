<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Consumes exact typed issuance custody and publishes under native exclusion. */
final readonly class NativeEffectReconciliationAuthorityIssuanceService
{
    public const string AUTHORITIES = 'var/imperium/runtime/canonical-native-effect-reconciliation-authorities-v2';
    public const string ISSUANCES = 'var/imperium/runtime/canonical-native-effect-reconciliation-authority-issuances';
    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private AuthorityConsumptionStore $consumptions;

    public function __construct(
        private NativeState $state,
        private NativeEffectReconciliationIssuanceAuthorityResolver $resolver,
        private ?\Closure $checkpoint = null,
    )
    {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
        $this->consumptions = new AuthorityConsumptionStore($this->records, $this->atomic);
    }

    public function issue(NativeEffectReconciliationIssuanceCapability $capability, int $at): array
    {
        return $this->state->locked(fn (): array => $this->atomic->run('reconciliation-issuance-root:'.hash('sha256', $capability->issuanceAuthorityId), function () use ($capability, $at): array {
            $resolved = $this->resolver->consume($capability, $at);
            $authority = $resolved['target_authority'];
            $issuanceAuthority = $resolved['issuance_authority'];
            $decision = $resolved['decision'];
            if (null !== $this->checkpoint) { ($this->checkpoint)('currentness.passed'); }
            $consumption = $this->consumptions->consume(
                $issuanceAuthority['issuance_authority_id'], $decision['decision_id'], $decision['record_digest'],
                NativeEffectReconciliationAuthorityV2Contract::ISSUER_SERVICE, new \DateTimeImmutable('@'.$at),
            );
            if (null !== $this->checkpoint) { ($this->checkpoint)('issuance_capability.consumed'); }
            $storedAuthority = $this->records->put(self::AUTHORITIES, $authority['authority_id'], $authority);
            if (null !== $this->checkpoint) { ($this->checkpoint)('authority.published'); }
            $issuance = $this->records->put(self::ISSUANCES, $authority['issuance_id'], [
                'schema' => NativeEffectReconciliationAuthorityIssuanceContract::SCHEMA,
                'issuance_id' => $authority['issuance_id'],
                'issued_authority' => NativeState::ref($storedAuthority, 'authority_id'),
                'source_native_authority' => $authority['source_native_authority'],
                'source_native_principal' => $authority['source_native_principal'],
                'source_native_transition' => $authority['source_native_transition'],
                'effect_admission' => $authority['effect_admission'],
                'issuer_service' => NativeEffectReconciliationAuthorityV2Contract::ISSUER_SERVICE,
                'issued_at' => $decision['effective_at'],
                'authority_issued' => true,
                'provider_invocation_performed' => false,
                'credential_resolution_performed' => false,
                'callback_invocation_performed' => false,
                'external_io_performed' => false,
                'continuing_authority' => false,
                'sealed' => true,
            ]);
            if (null !== $this->checkpoint) { ($this->checkpoint)('issuance.published'); }
            return ['authority' => $storedAuthority, 'issuance' => $issuance, 'issuance_authority_consumption' => $consumption];
        }));
    }
}
