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

    public function resolve(string $admissionId, int $at): array
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
        $nativeAuthority = (new NativeAuthority($this->state))->load($authorityId, $at);
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
}
