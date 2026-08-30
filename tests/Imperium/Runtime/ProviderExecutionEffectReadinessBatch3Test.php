<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\ProviderAssuranceEvidenceFixtureInterruptionProofService;

final class ProviderExecutionEffectReadinessBatch3Test extends ProviderExecutionEffectReadinessBatch2Test
{
    public function testEveryFixturePathHasTruthfulBeforeAndAfterCommitRecovery(): void
    {
        $source = $this->source();
        $profile = $this->profile([$source]);
        $admission = $this->admission($profile, [$source]);
        $service = new ProviderAssuranceEvidenceFixtureInterruptionProofService($this->root);

        $cases = [
            [
                fn (?string $cut): array => $service->putSource($source, $cut),
                $this->root.'/var/imperium/evidence/provider-execution-effect-readiness/assurance-sources/*.json',
                $source,
            ],
            [
                fn (?string $cut): array => $service->putProfile($profile, [$source], $cut),
                $this->root.'/var/imperium/evidence/provider-execution-effect-readiness/assurance-profiles/*.json',
                $profile,
            ],
            [
                fn (?string $cut): array => $service->putAdmission(
                    $admission,
                    $profile,
                    [$source],
                    $cut,
                ),
                $this->root.'/var/imperium/evidence/provider-execution-effect-readiness/assurance-admissions/*.json',
                $admission,
            ],
        ];

        foreach ($cases as $index => [$put, $glob, $expected]) {
            try {
                $put(ProviderAssuranceEvidenceFixtureInterruptionProofService::CUT_BEFORE_COMMIT);
                self::fail('Before-commit cut did not interrupt at '.$index);
            } catch (\RuntimeException $exception) {
                self::assertSame(
                    'PER300_INTERRUPTED_BEFORE_IMMUTABLE_COMMIT',
                    $exception->getMessage(),
                );
            }
            self::assertSame([], glob($glob) ?: []);

            try {
                $put(ProviderAssuranceEvidenceFixtureInterruptionProofService::CUT_AFTER_COMMIT);
                self::fail('After-commit cut did not interrupt at '.$index);
            } catch (\RuntimeException $exception) {
                self::assertSame(
                    'PER301_INTERRUPTED_AFTER_IMMUTABLE_COMMIT',
                    $exception->getMessage(),
                );
            }
            self::assertCount(1, glob($glob) ?: []);
            self::assertSame($expected, $put(null));
        }
    }

    public function testTwoSameRootServicesConvergeAndChangedEvidenceConflicts(): void
    {
        $source = $this->source();
        $left = new ProviderAssuranceEvidenceFixtureInterruptionProofService($this->root);
        $right = new ProviderAssuranceEvidenceFixtureInterruptionProofService($this->root);

        self::assertSame($source, $left->putSource($source));
        self::assertSame($source, $right->putSource($source));

        $changed = $source;
        $changed['version_identity'] = 'changed-after-winner';
        $changed = self::seal($changed);

        try {
            $right->putSource($changed);
            self::fail('Changed evidence reused the immutable source ID.');
        } catch (\RuntimeException $exception) {
            self::assertStringStartsWith('PST11', $exception->getMessage());
        }
    }

    public function testRecoveryServiceHasNoProviderCredentialOrAuthorityDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
                .'/src/Imperium/Runtime/LaCortine/'
                .'ProviderAssuranceEvidenceFixtureInterruptionProofService.php',
        );

        foreach ([
            'CredentialCapability',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'DeterministicTransport',
            'GovernedProviderExecutionCombinedAdmissionService',
            'DurableProviderExecutionAuthority',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }
}
