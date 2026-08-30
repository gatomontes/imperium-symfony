<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\CorridorDispositionPrincipalAuthorityRemediationProducer as Producer;
use App\Imperium\Runtime\Imperator\CorridorDispositionPrincipalAuthorityRemediationTerminalAudit as Audit;
use App\Imperium\Runtime\Imperator\FutureInstanceImperatorPrincipalConstitutionService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class CorridorDispositionPrincipalAuthorityRemediationTerminalAuditTest extends TestCase
{
    private string $root;
    protected function setUp(): void { $this->root = sys_get_temp_dir().'/imperium-cpa-audit-'.bin2hex(random_bytes(6)); mkdir($this->root, 0770, true); }
    protected function tearDown(): void { if (!is_dir($this->root)) return; $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); rmdir($this->root); }

    public function testExactChainSatisfiesReturnGateWithoutWriting(): void
    {
        $f = $this->produce(); $before = $this->snapshot(); $result = (new Audit($this->root))->audit($f['grant'], $f['successor'], $f['activation'], $f['authorization'], $f['target'], $f['dossier'], $f['eligibility'], $f['at']);
        self::assertSame('RETURN_GATE_SATISFIED', $result['classification']); self::assertSame($before, $this->snapshot()); self::assertTrue($result['read_only']);
        foreach (['state_repaired', 'authority_created', 'authority_issued', 'authority_consumed', 'principal_activated', 'binding_activated', 'disposition_selected', 'disposition_sealed', 'source_artifact_mutated', 'credential_or_capability_handled', 'external_action_performed'] as $field) self::assertFalse($result[$field], $field);
        self::assertSame('REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', $result['continuing_custody_refusal']);
    }

    public function testChangedEvidenceRefusesAndAuditStillWritesNothing(): void
    {
        $f = $this->produce(); $before = $this->snapshot(); $changed = $f['authorization']; $changed['proposed_disposition'] = 'QUARANTINED_PENDING_REMEDIATION'; $changed = $this->invoke('seal', $changed);
        $result = (new Audit($this->root))->audit($f['grant'], $f['successor'], $f['activation'], $changed, $f['target'], $f['dossier'], $f['eligibility'], $f['at']);
        self::assertSame('RETURN_GATE_REFUSED', $result['classification']); self::assertSame($before, $this->snapshot());
    }

    public function testDocumentationConditionallyReturnsToReconsiderationBatch5Only(): void
    {
        $base = dirname(__DIR__, 3); $doc = preg_replace('/\s+/', ' ', (string) file_get_contents($base.'/docs/corridor-disposition-principal-authority-remediation-terminal-audit.md')); $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($base.'/docs/handoffs/corridor-disposition-principal-authority-remediation-campaign-complete.md'));
        foreach (['TERMINAL_AUDIT_IMPLEMENTED', 'RETURN_GATE_SATISFIED', 'RETURN_GATE_REFUSED', 'current generation uniqueness', 'authority custody', 'effective lifecycle', 'scope confinement', 'secret exclusion', 'exact caller-authority binding', 'writes no record'] as $claim) self::assertNotFalse(stripos($doc, $claim), $claim);
        foreach (['CAMPAIGN_COMPLETE', 'Provider Binding Activation Corridor Disposition Reconsideration Batch 5 may resume only', 'RETURN_GATE_SATISFIED', 'does not select or seal a disposition', 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }

    private function produce(): array
    {
        $source = $this->invoke('principal', 1, false, 'ACTIVE'); $pending = $this->invoke('principal', 2, true, 'PENDING_ACTIVATION'); $pending['lifecycle']['prior_version'] = ['id' => $source['principal_version_id'], 'digest' => $source['record_digest'], 'schema' => $source['schema']]; $pending = $this->invoke('seal', $pending);
        $grant = $this->invoke('grant', $source, $pending); $successor = $this->invoke('successor', $grant); [$target, $dossier, $eligibility] = $this->invoke('basis', $pending); $activation = $this->invoke('activation', $pending); $authorization = $this->invoke('issuance', $pending, $successor, $activation, $target, $dossier, $eligibility); $at = new \DateTimeImmutable('2026-08-30T12:05:00+00:00');
        (new ImmutableRecordStore($this->root, new AtomicTransition($this->root)))->put(FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS, $source['principal_version_id'], $source); $producer = new Producer($this->root); $producer->commitSuccessor($grant, $successor, $source, $pending, $at); $producer->activateSuccessor($activation, $at); $producer->issueCallerAuthority($authorization, $successor, $activation, $target, $dossier, $eligibility, $at);
        return compact('grant', 'successor', 'activation', 'authorization', 'target', 'dossier', 'eligibility', 'at');
    }
    private function invoke(string $method, mixed ...$arguments): mixed { $fixture = new CorridorDispositionPrincipalAuthorityRemediationBatch2Test('testExactOfflineFixturesStoreIdempotentlyWithoutCreatingAuthority'); return (new \ReflectionMethod($fixture, $method))->invoke($fixture, ...$arguments); }
    private function snapshot(): array { $files = []; $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS)); foreach ($iterator as $file) if ($file->isFile()) $files[substr($file->getPathname(), strlen($this->root))] = hash_file('sha256', $file->getPathname()); ksort($files); return $files; }
}
