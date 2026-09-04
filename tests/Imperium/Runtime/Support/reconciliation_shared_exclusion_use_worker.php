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
    $f = json_decode((string) file_get_contents((string) $fixturePath), true, 32, JSON_THROW_ON_ERROR);
    $state = new NativeState($f['root']);
    $barrier = static function (string $expected, string $ready, string $cut) use ($f): void {
        if ($cut !== $expected) { return; }
        echo $ready."\n"; flush();
        $deadline = microtime(true) + 10.0;
        while (!is_file($f['release_path'])) {
            if (microtime(true) >= $deadline) { throw new RuntimeException('USE_RELEASE_TIMEOUT'); }
            usleep(1000);
        }
    };
    if ('dp01' === $f['mode']) {
        $result = (new NativeEffectReconciliationIssuanceAuthorizationService($state, static fn (string $cut) => $barrier('currentness.passed', 'DP01_CURRENTNESS_HELD', $cut)))
            ->authorize(...\App\Tests\Imperium\Runtime\Support\ReconciliationMissionFixture::arguments($f['admission_id'], $f['issue_at'], $f['expires_at']));
        echo json_encode(['result' => 'DECISION_PUBLISHED_BEFORE_MUTATION', 'id' => $result['decision']['decision_id']], JSON_THROW_ON_ERROR)."\n";
    } else {
        $authorization = (new NativeEffectReconciliationIssuanceAuthorizationService($state))->authorize(...\App\Tests\Imperium\Runtime\Support\ReconciliationMissionFixture::arguments($f['admission_id'], $f['issue_at'], $f['expires_at']));
        $issuanceResolver = new NativeEffectReconciliationIssuanceAuthorityResolver($state);
        $issuanceCapability = $issuanceResolver->resolve($authorization['issuance_authority']['issuance_authority_id'], $f['resolve_at']);
        if ('iu01' === $f['mode']) {
            $issued = (new NativeEffectReconciliationAuthorityIssuanceService($state, $issuanceResolver, static fn (string $cut) => $barrier('currentness.passed', 'IU01_CURRENTNESS_HELD', $cut)))
                ->issue($issuanceCapability, $f['use_at']);
            echo json_encode(['result' => 'AUTHORITY_PUBLISHED_BEFORE_MUTATION', 'id' => $issued['authority']['authority_id']], JSON_THROW_ON_ERROR)."\n";
        } else {
            $issued = (new NativeEffectReconciliationAuthorityIssuanceService($state, $issuanceResolver))->issue($issuanceCapability, $f['resolve_at']);
            $claimResolver = new NativeEffectReconciliationAuthorityResolver($state);
            $claimCapability = $claimResolver->resolve($issued['authority']['authority_id'], $f['resolve_at']);
            $claim = (new NativeEffectReconciliationAuthorityClaimDerivationService($state, $claimResolver, static fn (string $cut) => $barrier('currentness.passed', 'CU01_CURRENTNESS_HELD', $cut)))
                ->derive($claimCapability, $f['use_at']);
            echo json_encode(['result' => 'CLAIM_PUBLISHED_BEFORE_MUTATION', 'id' => $claim['claim_id']], JSON_THROW_ON_ERROR)."\n";
        }
    }
    exit(0);
} catch (Throwable $error) {
    echo json_encode(['result' => 'REFUSED', 'error' => $error->getMessage()], JSON_THROW_ON_ERROR)."\n";
    exit(3);
}
