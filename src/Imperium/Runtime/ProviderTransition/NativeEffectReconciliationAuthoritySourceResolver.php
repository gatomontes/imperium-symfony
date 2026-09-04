<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Resolves the existing signed Root -> active Imperator -> committed transition chain. */
final readonly class NativeEffectReconciliationAuthoritySourceResolver
{
    private ImmutableRecordStore $records;

    public function __construct(private NativeState $state)
    {
        $this->records = new ImmutableRecordStore($state->root, new AtomicTransition($state->root));
    }

    public function resolve(string $admissionId, int $at, bool $atUse = false): array
    {
        NativeState::id($admissionId);
        $admission = $this->records->read(NativeEffectAtomicAdmissionService::ADMISSIONS, $admissionId);
        if (NativeEffectAdmissionContract::REQUIRED_FIELDS !== array_keys($admission)
            || NativeEffectAdmissionContract::SCHEMA !== ($admission['schema'] ?? null)
            || NativeState::seal($admission) !== $admission
            || !is_string($admission['native_root'] ?? null)) {
            throw new \RuntimeException('CNE600_RECONCILIATION_SOURCE_ADMISSION_INVALID');
        }

        $commit = $this->state->get('transitions', $admission['native_root'])
            ?? throw new \RuntimeException('CNE601_RECONCILIATION_SOURCE_TRANSITION_ABSENT');
        $authorityId = $commit['authority_id'] ?? null;
        if (!is_string($authorityId)) {
            throw new \RuntimeException('CNE602_RECONCILIATION_SOURCE_AUTHORITY_INVALID');
        }
        try {
            $nativeAuthority = (new NativeAuthority($this->state))->load($authorityId, $at);
        } catch (\RuntimeException $error) {
            if (!$atUse) { throw $error; }
            throw new \RuntimeException($this->atUseRefusal($error, $authorityId, $at), 0, $error);
        }
        $principalId = $nativeAuthority['principal']['id'] ?? null;
        if (!is_string($principalId)) {
            throw new \RuntimeException('CNE602_RECONCILIATION_SOURCE_AUTHORITY_INVALID');
        }
        $nativePrincipal = (new NativePrincipal($this->state))->load($principalId, $at);
        if (($commit['root'] ?? null) !== $admission['native_root']
            || $authorityId !== ($nativeAuthority['authority']['authority_id'] ?? null)
            || $nativeAuthority['principal'] !== NativeState::ref($nativePrincipal, 'principal_version_id')) {
            throw new \RuntimeException('CNE602_RECONCILIATION_SOURCE_AUTHORITY_INVALID');
        }

        $callbackId = 'canonical-native-effect-callback-'.substr(hash('sha256', $admissionId), 0, 20);
        $responseId = 'canonical-native-effect-response-'.substr(hash('sha256', $callbackId), 0, 20);
        $callback = $this->records->read(NativeEffectDoubleExecutionService::CALLBACK_STARTS, $callbackId);
        $response = $this->records->read(NativeEffectDoubleExecutionService::RESPONSES, $responseId);
        if (($callback['effect_admission']['digest'] ?? null) !== $admission['record_digest']
            || ($response['effect_admission']['digest'] ?? null) !== $admission['record_digest']
            || ($response['callback_start']['digest'] ?? null) !== $callback['record_digest']) {
            throw new \RuntimeException('CNE603_RECONCILIATION_SOURCE_LINEAGE_INVALID');
        }

        return compact('admission', 'commit', 'nativeAuthority', 'nativePrincipal', 'callback', 'response');
    }

    private function atUseRefusal(\RuntimeException $error, string $authorityId, int $at): string
    {
        return match ($error->getMessage()) {
            'NIR_ROOT_INELIGIBLE' => 'REFUSED_OPERATOR_ROOT_REVOKED',
            'NIR_PRINCIPAL_NOT_CURRENT', 'NIR_PRINCIPAL_REVOKED' => 'REFUSED_NATIVE_PRINCIPAL_REVOKED',
            'NIR_SOURCE_GENERATION_CHANGED' => 'REFUSED_SOURCE_SUPERSEDED',
            'NIR_V3_LIFECYCLE_REQUIRES_NATIVE_MIGRATION' => 'REFUSED_SOURCE_MIGRATION_REQUIRED',
            'NIR_SOURCE_PRINCIPAL_NOT_ACTIVE' => $this->sourceLifecycleRefusal($authorityId, $at),
            default => $error->getMessage(),
        };
    }

    private function sourceLifecycleRefusal(string $authorityId, int $at): string
    {
        $chain = $this->state->get('authorities', $authorityId) ?? [];
        $native = $this->state->get('principals', (string) ($chain['principal']['id'] ?? '')) ?? [];
        $sourceReference = $native['source_principal'] ?? [];
        $source = $this->state->source('principal', $sourceReference);
        $status = (new \App\Imperium\Runtime\Imperator\ImperatorPrincipalLifecycleReconstructionService($this->state->root))
            ->reconstruct($source['principal_version_id'], new \DateTimeImmutable('@'.$at))['effective_status'];
        return match ($status) {
            'SUSPENDED' => 'REFUSED_SOURCE_SUSPENDED',
            'SUPERSEDED' => 'REFUSED_SOURCE_SUPERSEDED',
            'REVOKED' => 'REFUSED_SOURCE_REVOKED',
            'EXPIRED' => 'REFUSED_SOURCE_EXPIRED',
            'RETIRED' => 'REFUSED_SOURCE_RETIRED',
            default => 'REFUSED_STALE',
        };
    }
}
