<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Consumes exact issuance custody and publishes only its deterministic target. */
final readonly class NativeEffectReconciliationIssuancePublicationService
{
    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private AuthorityConsumptionStore $consumptions;
    private NativeEffectReconciliationAuthorityIssuanceService $issuer;

    public function __construct(
        private NativeState $state,
        private NativeEffectReconciliationIssuanceAuthorityResolver $resolver,
        private ?\Closure $checkpoint = null,
    ) {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
        $this->consumptions = new AuthorityConsumptionStore($this->records, $this->atomic);
        $this->issuer = new NativeEffectReconciliationAuthorityIssuanceService($state);
    }

    public function publish(NativeEffectReconciliationIssuanceCapability $capability, int $at): array
    {
        $targetRoot = self::targetConsumptionId($capability->authorityId);
        return $this->atomic->run(
            'reconciliation-issuance-root:'.hash('sha256', $capability->authorityId),
            function () use ($capability, $at, $targetRoot): array {
                $retry = $this->consumptionExists($targetRoot);
                $evidence = $this->resolver->consume($capability, $at);
                $decision = $evidence['decision'];
                $issuanceAuthority = $evidence['issuance_authority'];
                try {
                    $consumption = $this->consumptions->consume(
                        $targetRoot,
                        $issuanceAuthority['issuance_authority_id'],
                        $issuanceAuthority['record_digest'],
                        NativeEffectReconciliationIssuanceAuthorizationService::HOLDER,
                        new \DateTimeImmutable('@'.$at),
                    );
                } catch (\RuntimeException $error) {
                    if ('PST131_AUTHORITY_CONSUMPTION_CONFLICT' === $error->getMessage()) {
                        throw new \RuntimeException('REFUSED_CONFLICTED', 0, $error);
                    }
                    throw $error;
                }
                if (null !== $this->checkpoint) { ($this->checkpoint)('issuance-authority.consumed'); }

                $issued = $this->issuer->issue(
                    $decision['effect_admission']['id'],
                    $decision['effective_at'],
                    $decision['expires_at'],
                );
                if ($issued['authority']['authority_id'] !== $decision['target']['authority_id']
                    || $issued['authority']['schema'] !== $decision['target']['authority_schema']
                    || $issued['authority']['record_digest'] !== $decision['target']['authority_digest']
                    || $issued['authority']['deterministic_receipt_id'] !== $decision['target']['deterministic_receipt_id']) {
                    throw new \RuntimeException('CNE645_RECONCILIATION_ISSUANCE_TARGET_MISMATCH');
                }
                if (null !== $this->checkpoint) { ($this->checkpoint)('issuance-evidence.published'); }

                return [
                    'schema' => NativeEffectReconciliationIssuanceOutcomeContract::SCHEMA,
                    'result' => $retry ? 'EXACT_RETRY_CONVERGED' : 'AUTHORIZED',
                    'refusal' => null,
                    'replay_identity' => $decision['replay_identity'],
                    'established_result' => [
                        'issuance_authority_consumption' => $consumption,
                        'reconciliation_authority' => $issued['authority'],
                        'reconciliation_issuance_evidence' => $issued['issuance'],
                    ],
                    'continuing_authority' => false,
                ];
            },
        );
    }

    /** One semantic target owns the only consumption winner, regardless of competing grant IDs. */
    public static function targetConsumptionId(string $authorityId): string
    {
        NativeState::id($authorityId);
        return 'reconciliation-issuance-target:'.hash('sha256', $authorityId);
    }

    private function consumptionExists(string $targetRoot): bool
    {
        $id = 'authority-consumption-'.hash('sha256', $targetRoot);
        try {
            $this->records->read('var/imperium/runtime/authority-consumptions', $id);
            return true;
        } catch (\RuntimeException $error) {
            if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $error->getMessage()) { throw $error; }
            return false;
        }
    }
}
