<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Clavium\GovernedStationaryCredentialResolutionService;

final class ProviderExecutionBoundaryRedesignBatch8Test extends ProviderExecutionBoundaryRedesignBatch7Test
{
    private bool $revokeAuthority = false;
    private bool $revokePrincipal = false;

    public function testCrashBeforeResolutionLeavesNoProofAndSafeLocalRetryWinsOnce(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T20:00:00+00:00');
        $admission = $this->seedLineage($at);
        unset($_ENV['AGENTMAIL_API_KEY'], $_SERVER['AGENTMAIL_API_KEY']);

        try {
            (new GovernedStationaryCredentialResolutionService($this->root))
                ->prove($admission['admission_id'], $at);
            self::fail('Missing stationary credential must refuse.');
        } catch (\RuntimeException $exception) {
            self::assertSame('PEB711_STATIONARY_CREDENTIAL_UNAVAILABLE', $exception->getMessage());
        }

        self::assertSame([], glob(
            $this->root.'/'.GovernedStationaryCredentialResolutionService::PROOFS.'/*.json',
        ) ?: []);

        $_ENV['AGENTMAIL_API_KEY'] = 'test-secret-never-persisted';
        $_SERVER['AGENTMAIL_API_KEY'] = 'test-secret-never-persisted';
        $proof = (new GovernedStationaryCredentialResolutionService($this->root))
            ->prove($admission['admission_id'], $at);

        self::assertFalse($proof['effect']['provider_invoked']);
        self::assertCount(1, glob(
            $this->root.'/'.GovernedStationaryCredentialResolutionService::PROOFS.'/*.json',
        ) ?: []);
    }

    public function testExactReplayAndCompetingCallersConvergeOnOneImmutableProof(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T20:00:00+00:00');
        $admission = $this->seedLineage($at);

        $first = (new GovernedStationaryCredentialResolutionService($this->root))
            ->prove($admission['admission_id'], $at);
        unset($_ENV['AGENTMAIL_API_KEY'], $_SERVER['AGENTMAIL_API_KEY']);

        $second = (new GovernedStationaryCredentialResolutionService($this->root))
            ->prove($admission['admission_id'], $at->modify('+1 minute'));
        $third = (new GovernedStationaryCredentialResolutionService($this->root))
            ->prove($admission['admission_id'], $at->modify('+20 minutes'));

        self::assertSame($first, $second);
        self::assertSame($first, $third);
        self::assertCount(1, glob(
            $this->root.'/'.GovernedStationaryCredentialResolutionService::PROOFS.'/*.json',
        ) ?: []);
    }

    public function testExpiredUnprovedAdmissionRefusesWithoutProof(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T20:00:00+00:00');
        $admission = $this->seedLineage($at);

        try {
            (new GovernedStationaryCredentialResolutionService($this->root))
                ->prove($admission['admission_id'], $at->modify('+11 minutes'));
            self::fail('Expired admission must refuse.');
        } catch (\RuntimeException $exception) {
            self::assertSame('PEB702_EXECUTION_ADMISSION_INVALID', $exception->getMessage());
        }

        self::assertSame([], glob(
            $this->root.'/'.GovernedStationaryCredentialResolutionService::PROOFS.'/*.json',
        ) ?: []);
    }

    public function testRevokedAuthorityAndPrincipalEachRefuseBeforeResolution(): void
    {
        foreach (['authority', 'principal'] as $revoked) {
            $this->tearDown();
            $this->setUp();
            $at = new \DateTimeImmutable('2026-08-30T20:00:00+00:00');
            $this->revokeAuthority = 'authority' === $revoked;
            $this->revokePrincipal = 'principal' === $revoked;
            $admission = $this->seedLineage($at);

            try {
                (new GovernedStationaryCredentialResolutionService($this->root))
                    ->prove($admission['admission_id'], $at);
                self::fail('Revoked '.$revoked.' must refuse.');
            } catch (\RuntimeException $exception) {
                self::assertSame(
                    'PEB709_STATIONARY_CREDENTIAL_LINEAGE_INVALID',
                    $exception->getMessage(),
                );
            }

            self::assertSame([], glob(
                $this->root.'/'.GovernedStationaryCredentialResolutionService::PROOFS.'/*.json',
            ) ?: []);
        }
    }

    public function testCorruptReconstructionRefusesAndNeverRereadsCredential(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T20:00:00+00:00');
        $admission = $this->seedLineage($at);
        (new GovernedStationaryCredentialResolutionService($this->root))
            ->prove($admission['admission_id'], $at);
        unset($_ENV['AGENTMAIL_API_KEY'], $_SERVER['AGENTMAIL_API_KEY']);

        $files = glob(
            $this->root.'/'.GovernedStationaryCredentialResolutionService::PROOFS.'/*.json',
        ) ?: [];
        self::assertCount(1, $files);
        file_put_contents($files[0], '{}');

        $this->expectExceptionMessage('PEB714_STATIONARY_CREDENTIAL_PROOF_CONFLICT');
        (new GovernedStationaryCredentialResolutionService($this->root))
            ->prove($admission['admission_id'], $at->modify('+20 minutes'));
    }

    public function testCompleteCorridorRemainsSecretFreeAndNoIo(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T20:00:00+00:00');
        $admission = $this->seedLineage($at);
        $proof = (new GovernedStationaryCredentialResolutionService($this->root))
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
            self::assertStringNotContainsString('test-secret-never-persisted', $contents);
            self::assertStringNotContainsString('AGENTMAIL_API_KEY', $contents);
        }
        foreach ($proof['effect'] as $effect) {
            self::assertFalse($effect);
        }
    }

    protected function authority(
        array $boundaryRef,
        array $principalRef,
        array $bindingRef,
        array $binding,
        \DateTimeImmutable $at,
    ): array {
        $authority = parent::authority($boundaryRef, $principalRef, $bindingRef, $binding, $at);
        if ($this->revokeAuthority) {
            $authority['validity']['revocation_reference'] = $this->reference(
                'authority-revocation-1',
            );
        }

        return $authority;
    }

    protected function principal(array $boundaryRef, \DateTimeImmutable $at): array
    {
        $principal = parent::principal($boundaryRef, $at);
        if ($this->revokePrincipal) {
            $principal['validity']['revocation_reference'] = $this->reference(
                'principal-revocation-1',
            );
        }

        return $principal;
    }
}
