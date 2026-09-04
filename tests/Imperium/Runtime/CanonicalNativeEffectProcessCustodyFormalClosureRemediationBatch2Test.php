<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeEffectAdmissionContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAdmissionOutcome;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapability;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectProcessIncarnation;
use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectProcessCustodyFormalClosureRemediationBatch2Test extends TestCase
{
    public function testIssuedCapabilityUsesActualRuntimePidNotAuthorityLabel(): void
    {
        $issuer = new NativeEffectContinuationCapabilityIssuer();
        $capability = $issuer->issueForNewWinner($this->admission(), 'authority-process-label');

        self::assertSame(getmypid(), $capability->runtimeProcessId);
        self::assertSame('authority-process-label', $capability->processBoundaryId);
        self::assertNotSame((string) $capability->runtimeProcessId, $capability->processBoundaryId);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', (string) $capability->processIncarnationBinding);
        self::assertTrue($issuer->recognizes($capability));
    }

    public function testCapabilityIssuerOutcomeAndIncarnationRefuseSerializationAndClone(): void
    {
        $issuer = new NativeEffectContinuationCapabilityIssuer();
        $capability = $issuer->issueForNewWinner($this->admission(), 'authority-process-label');
        $outcome = new NativeEffectAdmissionOutcome($this->admission(), $capability, true);
        $incarnation = new NativeEffectProcessIncarnation();

        foreach ([$issuer, $capability, $outcome, $incarnation] as $object) {
            $this->failsContains('PROHIBITED', static fn () => serialize($object));
            $this->failsContains('PROHIBITED', static fn () => clone $object);
        }
        $this->failsContains('PROHIBITED', static fn () => serialize([$issuer, $outcome]));
    }

    public function testCraftedUnserializationCannotRestoreCustodyObjects(): void
    {
        foreach ([
            NativeEffectContinuationCapabilityIssuer::class,
            NativeEffectContinuationCapability::class,
            NativeEffectAdmissionOutcome::class,
            NativeEffectProcessIncarnation::class,
        ] as $class) {
            $payload = 'O:'.strlen($class).':"'.$class.'":0:{}';
            $this->failsContains('PROHIBITED', static fn () => unserialize($payload, ['allowed_classes' => true]));
        }
    }

    public function testFreshIssuerAndFieldLookalikeRemainUnrecognized(): void
    {
        $issuer = new NativeEffectContinuationCapabilityIssuer();
        $capability = $issuer->issueForNewWinner($this->admission(), 'authority-process-label');
        $lookalike = new NativeEffectContinuationCapability(
            $capability->capabilityId,
            $capability->admissionId,
            $capability->admissionDigest,
            $capability->semanticEffectTupleId,
            $capability->authorityConsumptionId,
            $capability->processBoundaryId,
            $capability->expiresAt,
            $capability->runtimeProcessId,
            $capability->processIncarnationBinding,
        );

        self::assertFalse($issuer->recognizes($lookalike));
        self::assertFalse((new NativeEffectContinuationCapabilityIssuer())->recognizes($capability));
    }

    public function testForkedChildCannotUseInheritedCustodyWherePcntlIsSupported(): void
    {
        if (!function_exists('pcntl_fork')) {
            self::assertSame('Windows', PHP_OS_FAMILY);
            return;
        }

        $issuer = new NativeEffectContinuationCapabilityIssuer();
        $capability = $issuer->issueForNewWinner($this->admission(), 'authority-process-label');
        $pid = pcntl_fork();
        self::assertNotSame(-1, $pid);
        if (0 === $pid) {
            exit($issuer->recognizes($capability) ? 91 : 0);
        }
        pcntl_waitpid($pid, $status);
        self::assertTrue(pcntl_wifexited($status));
        self::assertSame(0, pcntl_wexitstatus($status));
        self::assertTrue($issuer->recognizes($capability));
    }

    public function testConsumeRemainsSingleUseAndNoProviderDependencyWasAdded(): void
    {
        $issuer = new NativeEffectContinuationCapabilityIssuer();
        $capability = $issuer->issueForNewWinner($this->admission(), 'authority-process-label');
        self::assertTrue($issuer->consume($capability));
        self::assertFalse($issuer->consume($capability));
        self::assertFalse($issuer->recognizes($capability));

        $source = $this->read('src/Imperium/Runtime/ProviderTransition/NativeEffectProcessIncarnation.php')
            .$this->read('src/Imperium/Runtime/ProviderTransition/NativeEffectContinuationCapabilityIssuer.php');
        foreach (['CredentialBroker', 'AgentMail', 'HttpClient', 'curl_', 'file_put_contents'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testBatchDocumentationPreservesRecoveryAndLiveStops(): void
    {
        $docs = $this->read('docs/canonical-native-effect-process-custody-formal-closure-remediation-batch-2-process-bound-custody-v1.md')
            .$this->read('docs/handoffs/canonical-native-effect-process-custody-formal-closure-remediation-batch-2-complete.md');
        foreach (['BATCH_2_PROCESS_BOUND_CUSTODY', 'BATCH_3_EXECUTION_RECOVERY_SEPARATION_NEXT', 'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED', 'No recovery authority/claim was admitted'] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $docs, $marker);
        }
    }

    private function admission(): array
    {
        return [
            'schema' => NativeEffectAdmissionContract::SCHEMA,
            'admission_id' => 'canonical-native-effect-admission-'.str_repeat('a', 64),
            'record_digest' => str_repeat('b', 64),
            'semantic_effect_tuple_id' => str_repeat('c', 64),
            'authority_consumption_id' => str_repeat('d', 64),
            'expires_at' => PHP_INT_MAX,
        ];
    }

    private function failsContains(string $message, callable $action): void
    {
        try {
            $action();
            self::fail('Expected '.$message);
        } catch (\LogicException $error) {
            self::assertStringContainsString($message, $error->getMessage());
        }
    }

    private function read(string $path): string
    {
        return str_replace(["\r\n", "\r"], "\n", (string) file_get_contents($this->root().'/'.$path));
    }

    private function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
