<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeEffectAdmissionContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAuthorityContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectResultContract;
use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectCorridorActivationBatch1Test extends TestCase
{
    public function testContractsSeparateAuthorityAdmissionAndResult(): void
    {
        self::assertSame('email.send', NativeEffectAuthorityContract::OPERATION);
        self::assertContains('native_receipt', NativeEffectAuthorityContract::REQUIRED_FIELDS);
        self::assertContains('revocation_reference', NativeEffectAuthorityContract::REQUIRED_FIELDS);
        self::assertContains('cancellation_reference', NativeEffectAuthorityContract::REQUIRED_FIELDS);
        self::assertSame('EFFECT_STARTED_UNKNOWN_REPLAY_PROHIBITED', NativeEffectAdmissionContract::CHECKPOINT);
        self::assertContains('UNKNOWN_REPLAY_PROHIBITED', NativeEffectResultContract::OUTCOMES);
    }

    public function testContractsContainNoRuntimeMechanism(): void
    {
        $root = dirname(__DIR__, 3);
        $source = '';
        foreach (['NativeEffectAuthorityContract.php', 'NativeEffectAdmissionContract.php', 'NativeEffectResultContract.php'] as $file) {
            $source .= file_get_contents($root.'/src/Imperium/Runtime/ProviderTransition/'.$file);
        }
        foreach (['CredentialBroker', 'CredentialCapability', 'AgentMail', 'file_put_contents', 'curl_', 'HttpClient', 'Lazaretto'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testBatchDocumentationPreservesTheBoundary(): void
    {
        $root = dirname(__DIR__, 3);
        $docs = file_get_contents($root.'/docs/canonical-native-effect-corridor-activation-batch-1-contracts-v1.md')
            .file_get_contents($root.'/docs/handoffs/canonical-native-effect-corridor-activation-batch-1-complete.md');
        foreach (['NO_PRODUCER_NO_CONSUMER', 'No authority was', 'Batch 2'] as $marker) {
            self::assertStringContainsString($marker, $docs);
        }
    }
}
