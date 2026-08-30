<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\CorridorDispositionPrincipalAuthorityRemediationProducer as Producer;
use App\Imperium\Runtime\Imperator\FutureInstanceImperatorPrincipalConstitutionService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class CorridorDispositionPrincipalAuthorityRemediationBatch5Test extends TestCase
{
    private string $root;
    protected function setUp(): void { $this->root = sys_get_temp_dir().'/imperium-cpa-batch5-'.bin2hex(random_bytes(6)); mkdir($this->root, 0770, true); }
    protected function tearDown(): void { if (!is_dir($this->root)) return; $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); rmdir($this->root); }

    public function testExactGrantCommitsPendingThenSeparateActivationPermitsOneCallerAuthority(): void
    {
        $f = $this->fixtures(); $producer = new Producer($this->root);
        $commit = $producer->commitSuccessor($f['grant'], $f['successor'], $f['source'], $f['pending'], $f['at']);
        self::assertSame('PENDING_ACTIVATION', $commit['principal_version']['status']); self::assertFalse($commit['principal_activated']);
        $activation = $producer->activateSuccessor($f['activation'], $f['at']); self::assertSame('ACTIVE', $activation['effective_status']); self::assertFalse($activation['principal_record_mutated']);
        $issued = $producer->issueCallerAuthority($f['authorization'], $f['successor'], $f['activation'], $f['target'], $f['dossier'], $f['eligibility'], $f['at']);
        self::assertSame($f['authorization']['result_authority_id'], $issued['caller_authority']['authority_id']); self::assertFalse($issued['disposition_selected']); self::assertFalse($issued['disposition_sealed']); self::assertFalse($issued['external_action_performed']);
        self::assertSame($issued, $producer->issueCallerAuthority($f['authorization'], $f['successor'], $f['activation'], $f['target'], $f['dossier'], $f['eligibility'], $f['at']->modify('+1 second')));
    }

    public function testCallerAuthorityCannotIssueBeforeSeparateActivation(): void
    {
        $f = $this->fixtures(); $producer = new Producer($this->root); $producer->commitSuccessor($f['grant'], $f['successor'], $f['source'], $f['pending'], $f['at']);
        $this->expectExceptionMessage('CPA520_SEPARATE_ACTIVATION_REQUIRED'); $producer->issueCallerAuthority($f['authorization'], $f['successor'], $f['activation'], $f['target'], $f['dossier'], $f['eligibility'], $f['at']);
    }

    public function testCompetingGrantEvidenceAndRevocationFailClosed(): void
    {
        $f = $this->fixtures(); $producer = new Producer($this->root); $producer->commitSuccessor($f['grant'], $f['successor'], $f['source'], $f['pending'], $f['at']);
        $changed = $f['grant']; $changed['rationale'] = 'Competing exact-scope rationale.'; $changed = $this->invoke('seal', $changed);
        try { $producer->commitSuccessor($changed, $f['successor'], $f['source'], $f['pending'], $f['at']); self::fail('Competing evidence accepted.'); } catch (\RuntimeException $error) { self::assertContains($error->getMessage(), ['CPA210_SCOPE_SUCCESSOR_INVALID', 'PST131_AUTHORITY_CONSUMPTION_CONFLICT']); }
        $revoked = $this->fixtures(); $revoked['grant']['revocation'] = ['id' => 'scope-revocation', 'digest' => str_repeat('a', 64), 'schema' => 'imperium.operator-root.revocation/v1']; $revoked['grant'] = $this->invoke('seal', $revoked['grant']);
        $this->expectExceptionMessage('CPA200_SCOPE_GRANT_INVALID'); (new Producer($this->root))->commitSuccessor($revoked['grant'], $revoked['successor'], $revoked['source'], $revoked['pending'], $revoked['at']);
    }

    public function testDocumentationAuthorizesTerminalAuditOnly(): void
    {
        $base = dirname(__DIR__, 3); $doc = preg_replace('/\s+/', ' ', (string) file_get_contents($base.'/docs/corridor-disposition-principal-authority-remediation-production.md')); $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($base.'/docs/handoffs/corridor-disposition-principal-authority-remediation-batch-5-complete.md'));
        foreach (['BATCH_5_SEPARATELY_AUTHORIZED_SCOPE_REMEDIATION_PRODUCER_COMPLETE', 'exact Operator Root', 'PENDING_ACTIVATION', 'separate activation', 'one exact corridor caller authority', 'single-use', 'expiry', 'revocation', 'contention', 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE'] as $claim) self::assertNotFalse(stripos($doc, $claim), $claim);
        foreach (['Only remediation terminal audit is authorized', 'current generation uniqueness', 'authority custody', 'no scope beyond corridor disposition', 'no artifact mutation', 'Reconsideration Batch 5 remains paused', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }

    private function fixtures(): array
    {
        $source = $this->invoke('principal', 1, false, 'ACTIVE'); $pending = $this->invoke('principal', 2, true, 'PENDING_ACTIVATION');
        $pending['lifecycle']['prior_version'] = ['id' => $source['principal_version_id'], 'digest' => $source['record_digest'], 'schema' => $source['schema']]; $pending = $this->invoke('seal', $pending);
        $grant = $this->invoke('grant', $source, $pending); $successor = $this->invoke('successor', $grant); [$target, $dossier, $eligibility] = $this->invoke('basis', $pending); $activation = $this->invoke('activation', $pending); $authorization = $this->invoke('issuance', $pending, $successor, $activation, $target, $dossier, $eligibility);
        (new ImmutableRecordStore($this->root, new AtomicTransition($this->root)))->put(FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS, $source['principal_version_id'], $source);
        return compact('source', 'pending', 'grant', 'successor', 'target', 'dossier', 'eligibility', 'activation', 'authorization') + ['at' => new \DateTimeImmutable('2026-08-30T12:05:00+00:00')];
    }
    private function invoke(string $method, mixed ...$arguments): mixed { $fixture = new CorridorDispositionPrincipalAuthorityRemediationBatch2Test(); $reflection = new \ReflectionMethod($fixture, $method); return $reflection->invoke($fixture, ...$arguments); }
}
