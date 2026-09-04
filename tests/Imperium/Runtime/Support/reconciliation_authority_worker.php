<?php

declare(strict_types=1);

use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeState;

require dirname(__DIR__, 4).'/vendor/autoload.php';

[$script, $mode, $fixturePath] = $argv + [null, null, null];
try {
    $fixture = json_decode((string) file_get_contents((string) $fixturePath), true, 32, JSON_THROW_ON_ERROR);
    $state = new NativeState($fixture['root']);
    $issued = (new NativeEffectReconciliationAuthorityIssuanceService($state))->issue(
        $fixture['admission_id'],
        $fixture['at'],
        $fixture['expires_at'],
    );
    $resolver = new NativeEffectReconciliationAuthorityResolver($state);
    $capability = $resolver->resolve($issued['authority']['authority_id'], $fixture['at']);
    if ('resolve-only' === $mode) {
        echo json_encode(['authority_id' => $capability->authorityId], JSON_THROW_ON_ERROR);
        exit(0);
    }
    if ('derive' !== $mode) {
        throw new RuntimeException('unknown mode');
    }
    $claim = (new NativeEffectForwardRecoveryClaimAdmissionService($state, $resolver))->admit($capability, $fixture['at']);
    echo json_encode(['claim_id' => $claim['claim_id'], 'capability_id' => $capability->capabilityId], JSON_THROW_ON_ERROR);
    exit(0);
} catch (Throwable $error) {
    echo $error->getMessage();
    exit(3);
}
