<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderExecutionEffectReadinessBatch10TerminalAuditTest extends TestCase
{
    public function testTerminalAuditClosesOnlyThePreProviderCampaign(): void
    {
        $audit = $this->document(
            'docs/provider-execution-effect-readiness-terminal-adversarial-audit.md',
        );

        foreach ([
            'BATCH_10_TERMINAL_ADVERSARIAL_AUDIT_PASSED_PRE_PROVIDER_READINESS_COMPLETE',
            'PROVIDER_EXECUTION_EFFECT_READINESS_COMPLETE_PRE_PROVIDER_ONLY',
            'pre-provider closure only',
            'principal remains ATTESTED_INERT',
            'binding remains BOUND_INACTIVE',
            'No joining runtime contract was introduced',
            'No live consumer has adoption authority',
        ] as $finding) {
            self::assertNotFalse(stripos($audit, $finding), $finding);
        }
    }

    public function testAuditPreservesCrashReplayRefusalAndThreatCeilings(): void
    {
        $audit = $this->document(
            'docs/provider-execution-effect-readiness-terminal-adversarial-audit.md',
        );

        foreach ([
            'pre-commit cuts leave absence',
            'post-commit cuts leave the immutable winner',
            'exact replay and reconstruction are read only',
            'refused evidence is validated before later absence',
            'expiry, revocation, supersession, corruption',
            'TRUSTED_WRITER_CANONICAL_INTEGRITY',
            'SINGLE_AUTHORITATIVE_ROOT_ONLY',
            'UNKNOWN_REPLAY_PROHIBITED',
            'remote authorship or provider conformance is claimed',
        ] as $finding) {
            self::assertNotFalse(stripos($audit, $finding), $finding);
        }
    }

    public function testAuditNamesSecretExclusionsAndExactNonAuthorities(): void
    {
        $audit = $this->document(
            'docs/provider-execution-effect-readiness-terminal-adversarial-audit.md',
        );

        foreach ([
            'credential bytes',
            'credential references',
            'environment-variable names',
            'process-local capability identity',
            'Documentary evidence',
            'immutable fixture possession',
            'attestation',
            'same-process location',
            'credential possession',
            'credential resolvability',
            'are not provider-effect authority',
        ] as $boundary) {
            self::assertNotFalse(stripos($audit, $boundary), $boundary);
        }
    }

    public function testCampaignHandoffAuthorizesNoBatchElevenOrRuntimeEffect(): void
    {
        $handoff = $this->document(
            'docs/handoffs/provider-execution-effect-readiness-campaign-complete.md',
        );

        foreach ([
            'No Batch 11 exists',
            'starting with Preparation Batch 0 only after explicit selection',
            'No runtime contract or behavior changed',
            'No principal or binding was activated',
            'no credential or capability was handled',
            'no provider was invoked',
            'Iron Gate and Lazaretto remained closed',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    public function testEveryCampaignBatchHandoffExists(): void
    {
        $root = dirname(__DIR__, 3);
        self::assertFileExists(
            $root.'/docs/handoffs/provider-execution-effect-readiness-preparation-batch-0-complete.md',
        );

        for ($batch = 1; $batch <= 9; ++$batch) {
            self::assertFileExists(
                $root.'/docs/handoffs/provider-execution-effect-readiness-batch-'
                    .$batch.'-complete.md',
            );
        }
    }

    public function testTerminalAuditIntroducesNoRuntimeEffectAuthority(): void
    {
        $audit = $this->document(
            'docs/provider-execution-effect-readiness-terminal-adversarial-audit.md',
        );

        foreach ([
            'produces or consumes no decision',
            'does not activate or reactivate a principal or binding',
            'resolve or handle a credential or capability',
            'invoke a provider',
            'perform external I/O',
            'open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertNotFalse(stripos($audit, $boundary), $boundary);
        }
    }

    private function document(string $path): string
    {
        return (string) preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(dirname(__DIR__, 3).'/'.$path),
        );
    }
}
