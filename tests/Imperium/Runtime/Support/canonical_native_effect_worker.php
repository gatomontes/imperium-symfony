<?php

declare(strict_types=1);

use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapability;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
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
        echo json_encode(['status' => $record->newlyPublished ? 'admitted' : 'reconciled', 'id' => $record['admission_id'], 'newly_published' => $record->newlyPublished], JSON_THROW_ON_ERROR);
        exit(0);
    }

    if ('admit-callback-exit' === $mode || 'response-exit' === $mode) {
        $issuer = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $capability = $issuer->issue($authority, $authority['execution_boundary']['id'], $at);
        $admission = (new NativeEffectAtomicAdmissionService($state, $issuer, $continuations))->admit($authority, $capability, $at);
        $checkpoint = 'response-exit' === $mode ? static function (string $cut): void {
            if ('response.sealed' === $cut) { exit(74); }
        } : null;
        $service = new NativeEffectDoubleExecutionService($state, $continuations, $checkpoint);
        $service->execute(
            $admission['admission_id'],
            $admission->continuation,
            $fixture['payload'],
            $fixture['idempotency_key'],
            $at,
            static function () use ($mode, $fixture): array {
                if ('admit-callback-exit' === $mode) { exit(73); }
                return [
                    'http_status' => 202,
                    'headers' => [],
                    'body' => '{"message_id":"sealed","thread_id":"sealed"}',
                    'observed_at' => $fixture['at'],
                    'received_at' => $fixture['at'],
                ];
            },
        );
        exit(0);
    }

    if ('first-callback-attempt' === $mode || 'callback-retry' === $mode || 'forward-recover' === $mode) {
        $metadata = $fixture['continuation_metadata'];
        $lookalike = new NativeEffectContinuationCapability(
            $metadata['capability_id'], $metadata['admission_id'], $metadata['admission_digest'],
            $metadata['semantic_effect_tuple_id'], $metadata['authority_consumption_id'],
            $metadata['process_boundary_id'], $metadata['expires_at'],
        );
        $service = new NativeEffectDoubleExecutionService($state, new NativeEffectContinuationCapabilityIssuer());
        $service->execute(
            $fixture['admission_id'],
            $lookalike,
            $fixture['payload'],
            $fixture['idempotency_key'],
            $at,
            static function () use ($fixture): array {
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
