<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionContract;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionProducer;
use App\Imperium\Runtime\Imperator\CorridorDispositionPrincipalAuthorityRemediationProducer as Remediation;
use App\Imperium\Runtime\Imperator\FutureInstanceImperatorPrincipalConstitutionService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationCorridorDispositionReconsiderationBatch5Test extends TestCase
{
    private string $root;
    protected function setUp(): void { $this->root = sys_get_temp_dir().'/imperium-acd-batch5-'.bin2hex(random_bytes(6)); mkdir($this->root, 0770, true); }
    protected function tearDown(): void { if (!is_dir($this->root)) return; $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); rmdir($this->root); }

    public function testExactAuthoritySealsOneDispositionAndReplayConverges(): void
    {
        $f = $this->prepare(); $before = hash('sha256', CanonicalJson::encode([$f['target'], $f['dossier']])); $producer = new ActivationCorridorDispositionProducer($this->root);
        $first = $producer->decide($f['grant'], $f['successor'], $f['activation'], $f['authorization'], $f['target'], $f['dossier'], $f['eligibility'], 'Retire the unusable corridor.', ['Historical evidence remains readable.', 'Replacement requires new authority.'], $f['at']);
        $second = $producer->decide($f['grant'], $f['successor'], $f['activation'], $f['authorization'], $f['target'], $f['dossier'], $f['eligibility'], 'Retire the unusable corridor.', ['Historical evidence remains readable.', 'Replacement requires new authority.'], $f['at']->modify('+1 second'));
        self::assertSame($first, $second); self::assertSame('RETIRE_CORRIDOR', $first['disposition']); self::assertSame(ActivationCorridorDispositionContract::CUSTODY_REFUSAL, $first['terminal_custody_refusal']); self::assertFalse($first['source_artifact_mutated']); self::assertFalse($first['successor_authority_created']); self::assertFalse($first['binding_activated']); self::assertFalse($first['external_action_performed']); self::assertSame($before, hash('sha256', CanonicalJson::encode([$f['target'], $f['dossier']])));
    }

    public function testChangedOutcomeEvidenceAndExpiredAuthorityRefuse(): void
    {
        $f = $this->prepare(); $producer = new ActivationCorridorDispositionProducer($this->root); $producer->decide($f['grant'], $f['successor'], $f['activation'], $f['authorization'], $f['target'], $f['dossier'], $f['eligibility'], 'Retire the unusable corridor.', ['Historical evidence remains readable.'], $f['at']);
        $this->expectExceptionMessage('ACD505_DISPOSITION_CONTENTION'); $producer->decide($f['grant'], $f['successor'], $f['activation'], $f['authorization'], $f['target'], $f['dossier'], $f['eligibility'], 'Changed rationale.', ['Historical evidence remains readable.'], $f['at']);
    }

    public function testReturnGateAndAuthorityExpiryFailBeforeDisposition(): void
    {
        $f = $this->prepare(); $this->expectExceptionMessage('ACD500_REMEDIATION_RETURN_GATE_REFUSED'); (new ActivationCorridorDispositionProducer($this->root))->decide($f['grant'], $f['successor'], $f['activation'], $f['authorization'], $f['target'], $f['dossier'], $f['eligibility'], 'Too late.', ['No execution.'], new \DateTimeImmutable($f['authorization']['expires_at']));
    }

    public function testDocumentationAuthorizesTerminalAuditOnly(): void
    {
        $base = dirname(__DIR__, 3); $doc = preg_replace('/\s+/', ' ', (string) file_get_contents($base.'/docs/provider-binding-activation-corridor-disposition-production.md')); $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($base.'/docs/handoffs/provider-binding-activation-corridor-disposition-reconsideration-batch-5-complete.md'));
        foreach (['BATCH_5_SEPARATELY_AUTHORIZED_DISPOSITION_PRODUCER_COMPLETE', 'RETURN_GATE_SATISFIED', 'single-winner', 'exact caller authority', 'QUARANTINED_PENDING_REMEDIATION', 'RETIRE_CORRIDOR', 'source artifacts remain immutable', 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE'] as $claim) self::assertNotFalse(stripos($doc, $claim), $claim);
        foreach (['Only Reconsideration terminal audit is authorized', 'one outcome', 'intact historical evidence', 'no source mutation', 'no successor authority', 'Provider Execution Assurance remains paused', 'Iron Gate', 'Lazaretto'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }

    private function prepare(): array
    {
        $source = $this->invoke('principal', 1, false, 'ACTIVE'); $pending = $this->invoke('principal', 2, true, 'PENDING_ACTIVATION'); $pending['lifecycle']['prior_version'] = ['id' => $source['principal_version_id'], 'digest' => $source['record_digest'], 'schema' => $source['schema']]; $pending = $this->invoke('seal', $pending); $grant = $this->invoke('grant', $source, $pending); $successor = $this->invoke('successor', $grant); [$target, $dossier, $eligibility] = $this->invoke('basis', $pending); $activation = $this->invoke('activation', $pending); $authorization = $this->invoke('issuance', $pending, $successor, $activation, $target, $dossier, $eligibility); $at = new \DateTimeImmutable('2026-08-30T12:05:00+00:00');
        (new ImmutableRecordStore($this->root, new AtomicTransition($this->root)))->put(FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS, $source['principal_version_id'], $source); $remediation = new Remediation($this->root); $remediation->commitSuccessor($grant, $successor, $source, $pending, $at); $remediation->activateSuccessor($activation, $at); $remediation->issueCallerAuthority($authorization, $successor, $activation, $target, $dossier, $eligibility, $at);
        return compact('grant', 'successor', 'activation', 'authorization', 'target', 'dossier', 'eligibility', 'at');
    }
    private function invoke(string $method, mixed ...$arguments): mixed { $fixture = new CorridorDispositionPrincipalAuthorityRemediationBatch2Test('testExactOfflineFixturesStoreIdempotentlyWithoutCreatingAuthority'); return (new \ReflectionMethod($fixture, $method))->invoke($fixture, ...$arguments); }
}
