<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Clavium\GovernedStationaryCredentialResolutionV2Service;
use App\Imperium\Runtime\Clavium\StationaryCredentialResolutionProofContract;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionService;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionContract;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionService;

final class ProviderActivationConsumptionRemediationBatch6Test extends ProviderExecutionBoundaryRedesignBatch6Test
{
    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['AGENTMAIL_API_KEY'] = 'v2-test-secret-never-persisted';
        $_SERVER['AGENTMAIL_API_KEY'] = 'v2-test-secret-never-persisted';
    }

    protected function tearDown(): void
    {
        unset($_ENV['AGENTMAIL_API_KEY'], $_SERVER['AGENTMAIL_API_KEY']);
        parent::tearDown();
    }

    public function testStationaryResolutionRequiresCombinedV2Winner(): void
    {
        $at = new \DateTimeImmutable('2026-08-31T00:00:00+00:00');
        $authority = $this->seedLineage($at, $at->modify('+10 minutes'));
        $admission = (new GovernedProviderExecutionCombinedAdmissionService($this->root))
            ->admit($authority['authority_id'], $at);

        $proof = (new GovernedStationaryCredentialResolutionV2Service($this->root))
            ->prove($admission['admission_id'], $at);

        self::assertSame(
            GovernedProviderExecutionCombinedAdmissionContract::SCHEMA,
            $proof['provider_execution_admission']['schema'],
        );
        self::assertSame(
            StationaryCredentialResolutionProofContract::CHECKPOINT,
            $proof['resolution']['checkpoint'],
        );
        self::assertTrue($proof['resolution']['credential_resolved']);
        self::assertTrue($proof['resolution']['callback_local']);
        self::assertFalse($proof['resolution']['secret_exposed_to_caller']);
        self::assertFalse($proof['resolution']['credential_secret_persisted']);
        self::assertFalse($proof['resolution']['credential_reference_persisted']);
        foreach ($proof['effect'] as $effect) {
            self::assertFalse($effect);
        }
    }

    public function testExactV2ProofReplayDoesNotRereadCredential(): void
    {
        $at = new \DateTimeImmutable('2026-08-31T00:00:00+00:00');
        $authority = $this->seedLineage($at, $at->modify('+10 minutes'));
        $admission = (new GovernedProviderExecutionCombinedAdmissionService($this->root))
            ->admit($authority['authority_id'], $at);
        $service = new GovernedStationaryCredentialResolutionV2Service($this->root);
        $proof = $service->prove($admission['admission_id'], $at);
        unset($_ENV['AGENTMAIL_API_KEY'], $_SERVER['AGENTMAIL_API_KEY']);

        self::assertSame(
            $proof,
            $service->prove(
                $admission['admission_id'],
                $at->modify('+20 minutes'),
            ),
        );
    }

    public function testV1AdmissionIdIsRejectedAsHistoricalEvidence(): void
    {
        $at = new \DateTimeImmutable('2026-08-31T00:00:00+00:00');
        $authority = $this->seedLineage($at, $at->modify('+10 minutes'));
        $v1 = (new GovernedProviderExecutionAdmissionService($this->root))
            ->admit($authority['authority_id'], $at);

        $this->expectExceptionMessage('PEB700_EXECUTION_ADMISSION_ID_INVALID');
        (new GovernedStationaryCredentialResolutionV2Service($this->root))
            ->prove($v1['admission_id'], $at);
    }

    public function testEveryDurableRecordExcludesSecretAndEnvironmentName(): void
    {
        $at = new \DateTimeImmutable('2026-08-31T00:00:00+00:00');
        $authority = $this->seedLineage($at, $at->modify('+10 minutes'));
        $admission = (new GovernedProviderExecutionCombinedAdmissionService($this->root))
            ->admit($authority['authority_id'], $at);
        (new GovernedStationaryCredentialResolutionV2Service($this->root))
            ->prove($admission['admission_id'], $at);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->root.'/var/imperium',
                \FilesystemIterator::SKIP_DOTS,
            ),
        );
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $contents = (string) file_get_contents($file->getPathname());
            self::assertStringNotContainsString(
                'v2-test-secret-never-persisted',
                $contents,
            );
            self::assertStringNotContainsString('AGENTMAIL_API_KEY', $contents);
        }
    }

    public function testDocumentationAuthorizesOnlyAdversarialAuditNext(): void
    {
        $root = dirname(__DIR__, 3);
        $document = preg_replace(
            '/\\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/'
                .'provider-activation-consumption-remediation-stationary-resolution-v2.md',
            ),
        );
        $handoff = preg_replace(
            '/\\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/handoffs/'
                .'provider-activation-consumption-remediation-batch-6-complete.md',
            ),
        );

        foreach ([
            'BATCH_6_STATIONARY_RESOLUTION_REQUIRES_COMBINED_V2_WINNER',
            'A v1 admission ID is rejected',
            'Only remediation Batch 7 may next be considered',
            'adversarial proof and repeated terminal audit',
            'may not invoke a provider',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'one batch',
        ] as $boundary) {
            self::assertNotFalse(stripos($document.$handoff, $boundary), $boundary);
        }
    }

    public function testV2SourceHasNoCapabilityProviderOrExternalIoPath(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
            .'/src/Imperium/Runtime/Clavium/'
            .'GovernedStationaryCredentialResolutionV2Service.php',
        );

        foreach ([
            'GovernedProviderExecutionCombinedAdmissionContract',
            "'activation_consumption']['consumed']",
            "'authority_consumption']['consumed']",
            "'credential_secret_persisted' => false",
            "'provider_invoked' => false",
            "'external_io_started' => false",
        ] as $proof) {
            self::assertStringContainsString($proof, $source);
        }
        foreach ([
            'CredentialCapability',
            'CredentialBroker',
            'DeterministicTransport',
            'AgentMailEmailTransport',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
