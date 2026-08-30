<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionProducer;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionTerminalAudit;
use App\Imperium\Runtime\Imperator\CorridorDispositionPrincipalAuthorityRemediationProducer as Remediation;
use App\Imperium\Runtime\Imperator\FutureInstanceImperatorPrincipalConstitutionService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationCorridorDispositionReconsiderationTerminalAuditTest extends TestCase
{
    private string $root;
    protected function setUp(): void { $this->root = sys_get_temp_dir().'/imperium-acd-audit-'.bin2hex(random_bytes(6)); mkdir($this->root, 0770, true); }
    protected function tearDown(): void { if (!is_dir($this->root)) return; $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); rmdir($this->root); }

    public function testExactTerminalOutcomePassesWithoutWriting(): void
    {
        $f = $this->produce(); $before = $this->snapshot(); $result = (new ActivationCorridorDispositionTerminalAudit($this->root))->audit($f['grant'], $f['successor'], $f['activation'], $f['authorization'], $f['target'], $f['dossier'], $f['eligibility'], $f['at']->modify('+10 minutes'));
        self::assertSame('TERMINAL_AUDIT_SATISFIED', $result['classification']); self::assertSame($before, $this->snapshot()); self::assertTrue($result['read_only']); self::assertSame('REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', $result['continuing_custody_refusal']);
        foreach (['state_repaired', 'authority_created', 'authority_issued', 'authority_consumed', 'principal_activated', 'binding_activated', 'disposition_selected', 'disposition_resealed', 'source_artifact_mutated', 'successor_authority_created', 'credential_or_capability_handled', 'external_action_performed'] as $field) self::assertFalse($result[$field], $field);
    }

    public function testChangedHistoricalEvidenceRefusesWithoutRepair(): void
    {
        $f = $this->produce(); $before = $this->snapshot(); $changed = $f['eligibility']; $changed['reasons'] = ['Changed after disposition.']; $changed = $this->invoke('seal', $changed); $result = (new ActivationCorridorDispositionTerminalAudit($this->root))->audit($f['grant'], $f['successor'], $f['activation'], $f['authorization'], $f['target'], $f['dossier'], $changed, $f['at']);
        self::assertSame('TERMINAL_AUDIT_REFUSED', $result['classification']); self::assertSame($before, $this->snapshot()); self::assertFalse($result['state_repaired']);
    }

    public function testCampaignClosesWithoutProviderExecutionAuthority(): void
    {
        $base = dirname(__DIR__, 3); $doc = preg_replace('/\s+/', ' ', (string) file_get_contents($base.'/docs/provider-binding-activation-corridor-disposition-terminal-audit.md')); $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($base.'/docs/handoffs/provider-binding-activation-corridor-disposition-reconsideration-campaign-complete.md'));
        foreach (['TERMINAL_AUDIT_IMPLEMENTED', 'TERMINAL_AUDIT_SATISFIED', 'TERMINAL_AUDIT_REFUSED', 'one immutable outcome', 'exact caller-authority consumption', 'intact historical evidence', 'preserved consequences and attribution', 'writes no record'] as $claim) self::assertNotFalse(stripos($doc, $claim), $claim);
        foreach (['CAMPAIGN_COMPLETE', 'no Batch 7', 'grants no Provider Execution Assurance authority', 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', 'Provider Execution Assurance remains paused', 'Iron Gate', 'Lazaretto'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }

    private function produce(): array
    {
        $source = $this->invoke('principal', 1, false, 'ACTIVE'); $pending = $this->invoke('principal', 2, true, 'PENDING_ACTIVATION'); $pending['lifecycle']['prior_version'] = ['id' => $source['principal_version_id'], 'digest' => $source['record_digest'], 'schema' => $source['schema']]; $pending = $this->invoke('seal', $pending); $grant = $this->invoke('grant', $source, $pending); $successor = $this->invoke('successor', $grant); [$target, $dossier, $eligibility] = $this->invoke('basis', $pending); $activation = $this->invoke('activation', $pending); $authorization = $this->invoke('issuance', $pending, $successor, $activation, $target, $dossier, $eligibility); $at = new \DateTimeImmutable('2026-08-30T12:05:00+00:00');
        (new ImmutableRecordStore($this->root, new AtomicTransition($this->root)))->put(FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS, $source['principal_version_id'], $source); $remediation = new Remediation($this->root); $remediation->commitSuccessor($grant, $successor, $source, $pending, $at); $remediation->activateSuccessor($activation, $at); $remediation->issueCallerAuthority($authorization, $successor, $activation, $target, $dossier, $eligibility, $at); (new ActivationCorridorDispositionProducer($this->root))->decide($grant, $successor, $activation, $authorization, $target, $dossier, $eligibility, 'Retire the unusable corridor.', ['Historical evidence remains readable.', 'Replacement requires new authority.'], $at);
        return compact('grant', 'successor', 'activation', 'authorization', 'target', 'dossier', 'eligibility', 'at');
    }
    private function invoke(string $method, mixed ...$arguments): mixed { $fixture = new CorridorDispositionPrincipalAuthorityRemediationBatch2Test('testExactOfflineFixturesStoreIdempotentlyWithoutCreatingAuthority'); return (new \ReflectionMethod($fixture, $method))->invoke($fixture, ...$arguments); }
    private function snapshot(): array { $files = []; $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS)); foreach ($iterator as $file) if ($file->isFile()) $files[substr($file->getPathname(), strlen($this->root))] = hash_file('sha256', $file->getPathname()); ksort($files); return $files; }
}
