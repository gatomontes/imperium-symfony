<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Clavium\GovernedStationaryCredentialResolutionV2Service;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionAdmissionService;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionCombinedAdmissionService;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationRevocationWinnerService;

final class ProviderActivationConsumptionRemediationBatch7TerminalTest extends ProviderExecutionBoundaryRedesignBatch6Test
{
    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['AGENTMAIL_API_KEY'] = 'terminal-secret-never-persisted';
        $_SERVER['AGENTMAIL_API_KEY'] = 'terminal-secret-never-persisted';
    }

    protected function tearDown(): void
    {
        unset($_ENV['AGENTMAIL_API_KEY'], $_SERVER['AGENTMAIL_API_KEY']);
        parent::tearDown();
    }

    public function testExpiredLineageCannotCreateCombinedWinner(): void
    {
        $at = new \DateTimeImmutable('2026-08-31T01:00:00+00:00');
        $authority = $this->seedLineage($at, $at->modify('+10 minutes'));

        $this->expectExceptionMessage('PEB602_EXECUTION_AUTHORITY_INVALID');
        (new GovernedProviderExecutionCombinedAdmissionService($this->root))
            ->admit($authority['authority_id'], $at->modify('+11 minutes'));
    }

    public function testCorruptCombinedWinnerRefusesBeforeCredentialResolution(): void
    {
        $at = new \DateTimeImmutable('2026-08-31T01:00:00+00:00');
        $authority = $this->seedLineage($at, $at->modify('+10 minutes'));
        $admission = (new GovernedProviderExecutionCombinedAdmissionService($this->root))
            ->admit($authority['authority_id'], $at);
        $path = $this->root.'/'
            .GovernedProviderExecutionCombinedAdmissionService::ADMISSIONS
            .'/'.$admission['admission_id'].'.json';
        file_put_contents($path, '{}');

        $this->expectExceptionMessage('PST113_IMMUTABLE_RECORD_TAMPERED');
        (new GovernedStationaryCredentialResolutionV2Service($this->root))
            ->prove($admission['admission_id'], $at);
    }

    public function testMissingCredentialLeavesNoV2ResolutionProof(): void
    {
        $at = new \DateTimeImmutable('2026-08-31T01:00:00+00:00');
        $authority = $this->seedLineage($at, $at->modify('+10 minutes'));
        $admission = (new GovernedProviderExecutionCombinedAdmissionService($this->root))
            ->admit($authority['authority_id'], $at);
        unset($_ENV['AGENTMAIL_API_KEY'], $_SERVER['AGENTMAIL_API_KEY']);

        try {
            (new GovernedStationaryCredentialResolutionV2Service($this->root))
                ->prove($admission['admission_id'], $at);
            self::fail('Missing credential must refuse.');
        } catch (\RuntimeException $exception) {
            self::assertSame(
                'PEB711_STATIONARY_CREDENTIAL_UNAVAILABLE',
                $exception->getMessage(),
            );
        }

        self::assertSame([], glob(
            $this->root.'/'
            .GovernedStationaryCredentialResolutionV2Service::PROOFS
            .'/*.json',
        ) ?: []);
    }

    public function testCorrectedResolverRejectsV1EvenWhenV1RecordIsIntact(): void
    {
        $at = new \DateTimeImmutable('2026-08-31T01:00:00+00:00');
        $authority = $this->seedLineage($at, $at->modify('+10 minutes'));
        $v1 = (new GovernedProviderExecutionAdmissionService($this->root))
            ->admit($authority['authority_id'], $at);

        $this->expectExceptionMessage('PEB700_EXECUTION_ADMISSION_ID_INVALID');
        (new GovernedStationaryCredentialResolutionV2Service($this->root))
            ->prove($v1['admission_id'], $at);
    }

    public function testCompletedV2ProofReconstructsAndAllStateRemainsSecretFree(): void
    {
        $at = new \DateTimeImmutable('2026-08-31T01:00:00+00:00');
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
                'terminal-secret-never-persisted',
                $contents,
            );
            self::assertStringNotContainsString('AGENTMAIL_API_KEY', $contents);
        }
        foreach ($proof['effect'] as $effect) {
            self::assertFalse($effect);
        }
    }

    public function testTerminalSourcesUseOneSharedActivationWinnerWithoutProviderPath(): void
    {
        $root = dirname(__DIR__, 3).'/src/Imperium/Runtime';
        $combined = (string) file_get_contents(
            $root.'/LaCortine/GovernedProviderExecutionCombinedAdmissionService.php',
        );
        $revocation = (string) file_get_contents(
            $root.'/LaCortine/ProviderBindingActivationRevocationWinnerService.php',
        );
        $resolution = (string) file_get_contents(
            $root.'/Clavium/GovernedStationaryCredentialResolutionV2Service.php',
        );

        self::assertStringContainsString(
            "'governed-provider-execution-admission:'.\$activationId",
            $combined,
        );
        self::assertStringContainsString('LOCK_SCOPE_PREFIX', $revocation);
        self::assertStringContainsString(
            'ProviderBindingActivationRevocationWinnerService::WINNERS',
            $combined,
        );
        self::assertStringContainsString(
            'GovernedProviderExecutionCombinedAdmissionContract',
            $resolution,
        );
        foreach ([
            'CredentialCapability',
            'DeterministicTransport',
            'AgentMailEmailTransport',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString(
                $forbidden,
                $combined.$revocation.$resolution,
            );
        }
    }
}
