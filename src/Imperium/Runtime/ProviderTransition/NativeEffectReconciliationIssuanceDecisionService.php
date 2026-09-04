<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Publishes one exact Root/native-provenanced decision and its issuance authority. */
final readonly class NativeEffectReconciliationIssuanceDecisionService
{
    public const string DECISIONS = 'var/imperium/runtime/native-effect-reconciliation-issuance-decisions';
    public const string AUTHORITIES = 'var/imperium/runtime/native-effect-reconciliation-issuance-authorities';

    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private NativeEffectReconciliationAuthoritySourceResolver $sources;
    private NativeEffectReconciliationAuthorityRecordFactory $factory;

    public function __construct(private NativeState $state)
    {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
        $this->sources = new NativeEffectReconciliationAuthoritySourceResolver($state);
        $this->factory = new NativeEffectReconciliationAuthorityRecordFactory();
    }

    public function authorize(string $admissionId, int $at, int $expiresAt): array
    {
        return $this->state->locked(fn (): array => $this->authorizeInsideNativeExclusion($admissionId, $at, $expiresAt));
    }

    private function authorizeInsideNativeExclusion(string $admissionId, int $at, int $expiresAt): array
    {
        $source = $this->sources->resolve($admissionId, $at);
        $sourceExpiry = min(
            $source['nativeAuthority']['decision']['expires_at'] ?? 0,
            $source['nativePrincipal']['expires_at'] ?? 0,
        );
        if ($expiresAt <= $at || $expiresAt > $sourceExpiry || $at < ($source['response']['sealed_at'] ?? PHP_INT_MAX)) {
            throw new \RuntimeException('CNE640_RECONCILIATION_DECISION_TIME_INVALID');
        }

        $targetAuthority = $this->factory->build($source, $at, $expiresAt);
        $rootReference = $this->rootReference($source['nativePrincipal']['root_act']);
        $principal = $source['nativePrincipal'];
        $issuer = [
            'principal_id' => $principal['principal_id'],
            'principal_version_id' => $principal['principal_version_id'],
            'generation' => $principal['principal_generation'],
            'office' => 'imperator',
            'seat' => 'imperator',
            'competence' => 'DECIDE_EXACT_RECONCILIATION_AUTHORITY_ISSUANCE',
        ];
        $replayIdentity = hash('sha256', CanonicalJson::encode([
            $issuer,
            $targetAuthority['authority_id'],
            $targetAuthority['record_digest'],
            $at,
            $expiresAt,
        ]));
        $decisionId = 'native-effect-reconciliation-issuance-decision-'.substr($replayIdentity, 0, 32);
        $decision = [
            'schema' => NativeEffectReconciliationIssuanceDecisionContract::SCHEMA,
            'decision_id' => $decisionId,
            'instance_id' => $principal['instance_id'],
            'competent_issuer' => $issuer,
            'competent_issuer_provenance' => [
                'native_principal' => NativeState::ref($principal, 'principal_version_id'),
                'operator_root_act' => $rootReference,
            ],
            'target' => [
                'authority_id' => $targetAuthority['authority_id'],
                'authority_schema' => $targetAuthority['schema'],
                'authority_digest' => $targetAuthority['record_digest'],
                'deterministic_receipt_id' => $targetAuthority['deterministic_receipt_id'],
                'effective_at' => $at,
                'expires_at' => $expiresAt,
            ],
            'holder' => NativeEffectReconciliationAuthorityV2Contract::ISSUER_SERVICE,
            'effect_admission' => $targetAuthority['effect_admission'],
            'callback_start' => $targetAuthority['callback_start'],
            'sealed_response' => $targetAuthority['sealed_response'],
            'source_native_authority' => $targetAuthority['source_native_authority'],
            'source_native_principal' => $targetAuthority['source_native_principal'],
            'source_native_transition' => $targetAuthority['source_native_transition'],
            'operator_root_act' => $rootReference,
            'act' => NativeEffectReconciliationIssuanceDecisionContract::ACT,
            'disposition' => 'AUTHORIZED',
            'effective_at' => $at,
            'expires_at' => $expiresAt,
            'replay_identity' => $replayIdentity,
            'single_purpose' => true,
            'single_use' => true,
            'continuing_authority' => false,
            'sealed' => true,
        ];

        return $this->atomic->run('reconciliation-issuance-root:'.hash('sha256', $decisionId), function () use ($decision, $decisionId, $issuer, $targetAuthority, $rootReference, $at, $expiresAt, $replayIdentity): array {
            $storedDecision = $this->records->put(self::DECISIONS, $decisionId, $decision);
            $authorityId = 'native-effect-reconciliation-issuance-authority-'.substr(hash('sha256', $storedDecision['record_digest']), 0, 32);
            $authority = $this->records->put(self::AUTHORITIES, $authorityId, [
                'schema' => NativeEffectReconciliationIssuanceAuthorityContract::SCHEMA,
                'issuance_authority_id' => $authorityId,
                'instance_id' => $storedDecision['instance_id'],
                'issuance_decision' => NativeState::ref($storedDecision, 'decision_id'),
                'issuer' => $issuer,
                'holder' => NativeEffectReconciliationAuthorityV2Contract::ISSUER_SERVICE,
                'target' => $storedDecision['target'],
                'effect_admission' => $targetAuthority['effect_admission'],
                'callback_start' => $targetAuthority['callback_start'],
                'sealed_response' => $targetAuthority['sealed_response'],
                'source_native_authority' => $targetAuthority['source_native_authority'],
                'source_native_principal' => $targetAuthority['source_native_principal'],
                'source_native_transition' => $targetAuthority['source_native_transition'],
                'operator_root_act' => $rootReference,
                'permitted_transition' => NativeEffectReconciliationIssuanceAuthorityContract::PERMITTED_TRANSITION,
                'effective_at' => $at,
                'expires_at' => $expiresAt,
                'replay_identity' => $replayIdentity,
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'consumed' => false,
                'continuing_authority' => false,
                'sealed' => true,
            ]);

            return ['decision' => $storedDecision, 'issuance_authority' => $authority];
        });
    }

    private function rootReference(array $envelope): array
    {
        return [
            'id' => $envelope['act']['act_id'],
            'schema' => NativeRootActs::SCHEMA,
            'digest' => hash('sha256', CanonicalJson::encode($envelope)),
        ];
    }
}
