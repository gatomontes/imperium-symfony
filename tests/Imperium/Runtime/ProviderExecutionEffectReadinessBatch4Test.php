<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderAssuranceEvidenceAggregateReconstructor;
use App\Imperium\Runtime\LaCortine\ProviderAssuranceEvidenceFixtureStore;

final class ProviderExecutionEffectReadinessBatch4Test extends ProviderExecutionEffectReadinessBatch2Test
{
    public function testCompleteChainReconstructsEligibleWithoutWriting(): void
    {
        $source = $this->source();
        $profile = $this->profile([$source]);
        $admission = $this->admission($profile, [$source]);
        $store = new ProviderAssuranceEvidenceFixtureStore($this->root);
        $store->putSource($source);
        $store->putProfile($profile, [$source]);
        $store->putAdmission($admission, $profile, [$source]);
        $before = $this->fileDigests();

        $result = (new ProviderAssuranceEvidenceAggregateReconstructor($this->root))
            ->reconstruct(
                $source['source_id'],
                $profile['profile_id'],
                $admission['admission_id'],
            );

        self::assertSame('ELIGIBLE_OFFLINE_EVIDENCE', $result['classification']);
        self::assertSame([], $result['reasons']);
        self::assertTrue($result['read_only']);
        self::assertFalse($result['fixture_created']);
        self::assertFalse($result['fixture_repaired']);
        self::assertFalse($result['provider_truth_promoted']);
        self::assertFalse($result['execution_authority_created']);
        self::assertFalse($result['retry_authority_created']);
        self::assertSame($before, $this->fileDigests());
    }

    public function testAbsentCorruptAndValidButRefusedChainsClassifyExactly(): void
    {
        $source = $this->source();
        $profile = $this->profile([$source]);
        $admission = $this->admission($profile, [$source]);
        $store = new ProviderAssuranceEvidenceFixtureStore($this->root);
        $store->putSource($source);
        $reconstructor = new ProviderAssuranceEvidenceAggregateReconstructor($this->root);

        self::assertSame(
            'INCOMPLETE',
            $reconstructor->reconstruct(
                $source['source_id'],
                $profile['profile_id'],
                $admission['admission_id'],
            )['classification'],
        );

        $store->putProfile($profile, [$source]);
        $profilePath = $this->root.'/'
            .ProviderAssuranceEvidenceFixtureStore::PROFILES
            .'/'.$profile['profile_id'].'.json';
        file_put_contents($profilePath, '{}');
        self::assertSame(
            'CONFLICTED',
            $reconstructor->reconstruct(
                $source['source_id'],
                $profile['profile_id'],
                $admission['admission_id'],
            )['classification'],
        );

        unlink($profilePath);
        $refused = $profile;
        $refused['status'] = 'REFUSED';
        $refused = self::seal($refused);
        file_put_contents($profilePath, CanonicalJson::encode($refused));
        self::assertSame(
            'REFUSED',
            $reconstructor->reconstruct(
                $source['source_id'],
                $profile['profile_id'],
                $admission['admission_id'],
            )['classification'],
        );
    }

    public function testInvalidIdentifierRefusesWithoutFilesystemMutation(): void
    {
        $before = $this->fileDigests();
        $result = (new ProviderAssuranceEvidenceAggregateReconstructor($this->root))
            ->reconstruct('../source', 'profile', 'admission');

        self::assertSame('REFUSED', $result['classification']);
        self::assertSame(['IDENTIFIER_INVALID'], $result['reasons']);
        self::assertSame($before, $this->fileDigests());
    }

    public function testReconstructorHasNoProviderCredentialOrAuthorityProducer(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3)
                .'/src/Imperium/Runtime/LaCortine/'
                .'ProviderAssuranceEvidenceAggregateReconstructor.php',
        );

        foreach ([
            'CredentialCapability',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'DeterministicTransport',
            'GovernedProviderExecutionCombinedAdmissionService',
            'DurableProviderExecutionAuthorityIssuanceService',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    private function fileDigests(): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[$file->getPathname()] = hash_file('sha256', $file->getPathname());
            }
        }
        ksort($files);

        return $files;
    }
}
