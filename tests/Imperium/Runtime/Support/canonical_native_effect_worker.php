<?php

declare(strict_types=1);

use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Imperium\Runtime\ProviderTransition\NativeState;

require dirname(__DIR__, 4).'/vendor/autoload.php';

[$script, $mode, $fixturePath] = $argv + [null, null, null];
if (!is_string($mode) || !is_string($fixturePath)) {
    exit(64);
}

$fixture = json_decode((string) file_get_contents($fixturePath), true, 64, JSON_THROW_ON_ERROR);
$state = new NativeState($fixture['root']);
$authority = $fixture['authority'];
$at = $fixture['at'];

try {
    if ('stop-before-admit' === $mode) {
        exit(71);
    }

    if ('admit' === $mode || 'admit-and-exit' === $mode) {
        $issuer = new NativeEffectCredentialCapabilityIssuer();
        $capability = $issuer->issue($authority, $authority['execution_boundary']['id'], $at);
        $record = (new NativeEffectAtomicAdmissionService($state, $issuer))->admit($authority, $capability, $at);
        if ('admit-and-exit' === $mode) {
            exit(72);
        }
        echo json_encode(['status' => 'admitted', 'id' => $record['admission_id']], JSON_THROW_ON_ERROR);
        exit(0);
    }

    if ('callback-exit' === $mode || 'callback-retry' === $mode) {
        $service = new NativeEffectDoubleExecutionService($state);
        $service->execute(
            $fixture['admission_id'],
            $authority,
            $fixture['payload'],
            $fixture['idempotency_key'],
            $at,
            static function () use ($mode, $fixture): array {
                if ('callback-exit' === $mode) {
                    exit(73);
                }
                file_put_contents($fixture['unexpected_callback_marker'], 'invoked');
                return [
                    'http_status' => 202,
                    'headers' => [],
                    'body' => '{"message_id":"unexpected","thread_id":"unexpected"}',
                    'observed_at' => $fixture['at'],
                    'received_at' => $fixture['at'],
                ];
            },
        );
        echo json_encode(['status' => 'completed'], JSON_THROW_ON_ERROR);
        exit(0);
    }

    exit(65);
} catch (\Throwable $error) {
    echo json_encode(['status' => 'refused', 'message' => $error->getMessage()], JSON_THROW_ON_ERROR);
    exit(3);
}
