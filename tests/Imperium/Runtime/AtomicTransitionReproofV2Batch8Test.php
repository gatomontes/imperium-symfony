<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\IndependentVerification\ReproofV2AdmissionConsumer;
use App\ReproofV2\Records;
use App\ReproofV2\SourceBundle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Public archival checks only. No mission CLI, operational verifier or signing custody. */
final class AtomicTransitionReproofV2Batch8Test extends TestCase
{
    private const string REVIEWED_MAIN = '7318ab23c9f14db06bcb2da6844225206e273f57';
    private const string SOURCE = '2b5cb56c8ae60d80b628311377f929830401ca3e';
    private const string ACCEPTED = 'CAMPAIGN_CLOSURE_ACCEPTED_AFTER_INDEPENDENTLY_ATTESTED_REPROOF';
    private const string QUALIFICATION = 'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT';
    private const string V1_REFUSAL = 'CAMPAIGN_TERMINATED_INDEPENDENT_VERIFICATION_EVIDENCE_INSUFFICIENT';

    public function testActualPublicChainAndApprovalsBindTheHistoricalAdmission(): void
    {
        $p = [];
        foreach (['candidate', 'identity', 'report', 'attestation', 'trust-anchor', 'admission'] as $kind) {
            $p[$kind] = $this->json('docs/evidence/atomic-transition-reproof-v2-proof-2-'.$kind.'.json');
            self::assertSame($p[$kind], Records::seal($p[$kind]));
        }
        $anchor = $p['trust-anchor'];
        $execution = $this->json('docs/atomic-transition-reproof-v2-execution-request.json');
        $signing = $this->json('docs/atomic-transition-reproof-v2-verification-signing-request.json');
        self::assertSame(Records::hash($execution), $anchor['execution_request_digest']);
        self::assertSame(Records::hash($signing), $anchor['verification_request_digest']);
        self::assertSame($anchor['execution_request_digest'], $signing['execution_authorization_digest']);
        foreach (['proof_id', 'source_commit', 'source_manifest_root'] as $field) {
            self::assertSame($anchor[$field], $execution[$field]);
            self::assertSame($anchor[$field], $signing[$field]);
        }
        $time = new \DateTimeImmutable($p['admission']['admitted_at']);
        // Re-evaluate a historical public record in memory; do not publish a new admission.
        self::assertSame($p['admission'], (new ReproofV2AdmissionConsumer($anchor))->admit(
            $p['candidate'], $p['identity'], $p['report'], $p['attestation'], $time));
        self::assertSame('2026-09-02T19:00:04Z', $p['admission']['admitted_at']);
        foreach (['report', 'admission'] as $kind) {
            self::assertFalse($p[$kind]['qualification_removed']);
            self::assertFalse($p[$kind]['campaign_closed']);
        }
        $audit = $this->read('docs/atomic-transition-reproof-v2-terminal-audit-v1.md');
        foreach ($p as $record) { self::assertStringContainsString($record['record_digest'], $audit); }
        foreach (['execution_request_digest', 'verification_request_digest', 'controller_digest', 'verifier_root', 'receipt_digest'] as $field) {
            self::assertStringContainsString($anchor[$field], $audit);
        }
    }

    public function testReviewedGitSourceAndVerifierBytesMatchTheApprovedPins(): void
    {
        self::assertSame('', $this->git(['merge-base', '--is-ancestor', '0a33113', self::REVIEWED_MAIN]));
        self::assertSame('', $this->git(['merge-base', '--is-ancestor', self::SOURCE, self::REVIEWED_MAIN]));
        $manifest = [];
        foreach (SourceBundle::PATHS as $path) {
            $bytes = $this->git(['show', self::SOURCE.':'.$path]);
            self::assertSame($bytes, $this->git(['show', self::REVIEWED_MAIN.':'.$path]), $path);
            $manifest[$path] = ['blob' => hash('sha1', 'blob '.strlen($bytes)."\0".$bytes), 'sha256' => hash('sha256', $bytes)];
        }
        $anchor = $this->json('docs/evidence/atomic-transition-reproof-v2-proof-2-trust-anchor.json');
        self::assertCount(17, $manifest);
        self::assertSame($anchor['source_manifest_root'], Records::hash($manifest));
        $verifier = [];
        foreach (['src/Bootstrap/CanonicalJson.php', 'src/ReproofV2/Records.php',
            'src/IndependentVerification/ReproofV2CaseEvaluator.php', 'src/IndependentVerification/ReproofV2Exclusion.php',
            'src/IndependentVerification/ReproofV2SourceProof.php', 'src/IndependentVerification/ReproofV2Verifier.php'] as $path) {
            $bytes = $this->git(['show', self::SOURCE.':'.$path]);
            self::assertSame($bytes, $this->git(['show', self::REVIEWED_MAIN.':'.$path]), $path);
            $verifier[$path] = hash('sha256', $bytes);
        }
        self::assertSame($anchor['verifier_root'], Records::hash($verifier));
        self::assertSame($anchor['controller_digest'], hash('sha256', $this->git([
            'show', self::REVIEWED_MAIN.':tools/verify-and-sign-atomic-transition-reproof-v2.php'])));
    }

    public static function disabledConsumers(): iterable
    {
        $namespace = 'App\\Imperium\\Runtime\\Imperator\\';
        yield 'boolean audit' => [$namespace.'ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService',
            'audit', [[], [], []], 'PBL1015_HISTORICAL_BOOLEAN_AUDIT_DISABLED'];
        yield 'self recomputed closure' => [$namespace.'AtomicTransitionEvidenceCorrectedClosureService',
            'close', ['synthetic', [], [], [], [], [], []], 'PBL1016_HISTORICAL_SELF_RECOMPUTED_CLOSURE_DISABLED'];
        yield 'unsigned terminal' => [$namespace.'AtomicTransitionEvidenceTerminalAdversarialAuditor',
            'close', ['synthetic', [], []], 'PBL1033_LEGACY_UNSIGNED_TERMINAL_CLOSURE_DISABLED'];
    }

    #[DataProvider('disabledConsumers')]
    public function testDirectHistoricalClosureCallsStillRefuse(string $class, string $method, array $arguments, string $reason): void
    {
        // No dependency graph is constructed. Each method must throw before reading dependencies.
        $consumer = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        self::assertStringContainsString("- '../src/Imperium/Runtime/Imperator/".substr($class, strrpos($class, '\\') + 1).".php'",
            $this->read('config/services.yaml'));
        $this->expectExceptionMessage($reason);
        $consumer->$method(...$arguments);
    }

    public function testV1EvidenceAndRuntimeWereNotRehabilitatedByTheCampaign(): void
    {
        self::assertSame('', $this->git(['diff', '--name-only', '3c4f8b2', self::REVIEWED_MAIN, '--',
            'src/Imperium/Runtime', 'src/IndependentVerification/AtomicTransition*',
            'docs/evidence/atomic-transition-integrated-disposable-proof-1-sanitized.json']));
        self::assertStringContainsString("\$domains['acceptance_matrix'] = 'INDETERMINATE'",
            $this->read('src/IndependentVerification/AtomicTransitionArtifactAndReceiptVerifier.php'));
        foreach (['src/Imperium/Runtime/Imperator/AtomicTransitionEvidenceIndependentReconstructor.php',
            'src/IndependentVerification/AtomicTransitionIndependentVerificationAdmissionConsumer.php',
            'src/IndependentVerification/ReproofV2AdmissionConsumer.php'] as $path) {
            self::assertStringContainsString("'qualification_removed' => false", $this->read($path));
            self::assertStringContainsString("'campaign_closed' => false", $this->read($path));
        }
        self::assertStringContainsString(self::V1_REFUSAL,
            $this->read('src/IndependentVerification/AtomicTransitionIndependentVerificationTerminalAuditor.php'));
        self::assertStringContainsString("- '../src/IndependentVerification/ReproofV2*'", $this->read('config/services.yaml'));
    }

    public function testTerminalDecisionPreservesScopeAndAllCurrentConsumersPointToCompletion(): void
    {
        $audit = $this->read('docs/atomic-transition-reproof-v2-terminal-audit-v1.md');
        foreach ([self::REVIEWED_MAIN, self::SOURCE, 'batch 8 approved', self::ACCEPTED, self::QUALIFICATION,
            self::V1_REFUSAL, 'PINNED_EXPLICIT_LOADER_AND_SOURCE_IMPORTS', 'PHP_NATIVE_RUNTIME_TRUSTED',
            'GIT_READ_ONLY_CAPTURE', 'LOCAL_PACKAGE_WRITER_ONLY', 'NO_VENDOR_BOOTSTRAP',
            'BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED',
            'qualification_removed=false', 'campaign_closed=false', 'No runtime closure consumer',
            'No private receipt or signing material was inspected', 'No stages remain'] as $boundary) {
            self::assertStringContainsString($boundary, $audit);
        }
        $handoff = 'docs/handoffs/atomic-transition-reproof-v2-campaign-complete.md';
        foreach (['docs/next-campaign-atomic-transition-independently-verifiable-reproof.md',
            'docs/handoffs/atomic-transition-reproof-v2-implementation-progress.md', 'docs/handoffs/README.md',
            'docs/delegate-mission-flow.md', 'todo/blackquill-todos.md'] as $path) {
            self::assertStringContainsString(self::ACCEPTED, $this->read($path));
            self::assertStringContainsString($handoff, $this->read($path));
        }
        foreach ([self::ACCEPTED, self::V1_REFUSAL, 'No stages remain', 'BOUND_INACTIVE', 'NOT_IMPLEMENTED',
            'UNKNOWN_REPLAY_PROHIBITED'] as $boundary) { self::assertStringContainsString($boundary, $this->read($handoff)); }
    }

    private function json(string $path): array { return json_decode($this->read($path), true, flags: JSON_THROW_ON_ERROR); }
    private function read(string $path): string { return file_get_contents(dirname(__DIR__, 3).'/'.$path); }

    /** Local immutable public Git objects only; never fetches or invokes repository scripts. */
    private function git(array $arguments): string
    {
        $process = proc_open(['git', '--no-pager', '-c', 'core.fsmonitor=false', ...$arguments],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes,
            dirname(__DIR__, 3), null, ['bypass_shell' => true]);
        self::assertIsResource($process);
        fclose($pipes[0]); $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $error = stream_get_contents($pipes[2]); fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $error);
        return $output;
    }
}
