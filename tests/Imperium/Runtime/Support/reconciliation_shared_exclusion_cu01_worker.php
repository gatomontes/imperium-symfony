<?php

declare(strict_types=1);

use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityClaimDerivationService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorizationService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeState;

require dirname(__DIR__, 4).'/vendor/autoload.php';

[$script, $fixturePath] = $argv + [null, null];
try {
    $fixture = json_decode((string) file_get_contents((string) $fixturePath), true, 32, JSON_THROW_ON_ERROR);
    $state = new NativeState($fixture['root']);
    $authorization = (new NativeEffectReconciliationIssuanceAuthorizationService($state))->authorize(...\App\Tests\Imperium\Runtime\Support\ReconciliationMissionFixture::arguments($fixture['admission_id'], $fixture['issue_at'], $fixture['expires_at']));
    $issuanceResolver = new NativeEffectReconciliationIssuanceAuthorityResolver($state);
    $issued = (new NativeEffectReconciliationAuthorityIssuanceService($state, $issuanceResolver))->issue(
        $issuanceResolver->resolve($authorization['issuance_authority']['issuance_authority_id'], $fixture['issue_at']),
        $fixture['issue_at'],
    );
    $resolver = new NativeEffectReconciliationAuthorityResolver($state);
    $capability = $resolver->resolve($issued['authority']['authority_id'], $fixture['resolve_at']);

    echo "CU01_CURRENTNESS_RESOLUTION_PASSED\n";
    flush();
    $deadline = microtime(true) + 10.0;
    while (!is_file($fixture['release_path'])) {
        if (microtime(true) >= $deadline) {
            throw new RuntimeException('CU01_RELEASE_TIMEOUT');
        }
        usleep(1000);
    }

    $claim = (new NativeEffectReconciliationAuthorityClaimDerivationService($state, $resolver))->derive(
        $capability,
        $fixture['use_at'],
    );
    echo json_encode([
        'result' => 'STALE_CLAIM_PUBLISHED',
        'claim_id' => $claim['claim_id'],
        'authority_id' => $issued['authority']['authority_id'],
        'consumed_at' => $claim['authority_consumption']['consumed_at'],
    ], JSON_THROW_ON_ERROR)."\n";
    exit(0);
} catch (Throwable $error) {
    echo json_encode(['result' => 'REFUSED', 'error' => $error->getMessage()], JSON_THROW_ON_ERROR)."\n";
    exit(3);
}
