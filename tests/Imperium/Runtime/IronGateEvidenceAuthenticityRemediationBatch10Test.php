<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityConsumer;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityIssuanceService;
use PHPUnit\Framework\TestCase;

final class IronGateEvidenceAuthenticityRemediationBatch10Test extends TestCase
{
    private string $root;
    private string $bindingId = 'curia-seneschal-binding-99999999999999999999';
    private \DateTimeImmutable $time;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-iron-gate-authenticity-10-'.bin2hex(random_bytes(5));
        $this->time = new \DateTimeImmutable('2035-01-01T00:00:00+00:00');
        $this->writeOccupancy(1);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
    }

    public function testExactReplayIsRecoveryButChangedConsumerConflicts(): void
    {
        $authority = $this->authority($this->target('alpha'));
        $consumer = new DeterministicTransitionCallerAuthorityConsumer($this->root);
        $first = $consumer->consume($authority['authority_id'], 'REQUEST_EXACT_OUTBOUND_EMAIL_AUTHORIZATION', $this->target('alpha'), 'consumer.exact', $this->time->modify('+1 minute'));
        self::assertSame($first, $consumer->consume($authority['authority_id'], 'REQUEST_EXACT_OUTBOUND_EMAIL_AUTHORIZATION', $this->target('alpha'), 'consumer.exact', $this->time->modify('+2 minutes')));
        $this->expectExceptionMessage('PST131_AUTHORITY_CONSUMPTION_CONFLICT');
        $consumer->consume($authority['authority_id'], 'REQUEST_EXACT_OUTBOUND_EMAIL_AUTHORIZATION', $this->target('alpha'), 'consumer.competing', $this->time->modify('+2 minutes'));
    }

    public function testTargetAndTransitionSubstitutionFailBeforeConsumption(): void
    {
        $authority = $this->authority($this->target('alpha'));
        $consumer = new DeterministicTransitionCallerAuthorityConsumer($this->root);
        foreach ([['DECIDE_EXACT_OUTBOUND_EMAIL_REQUEST', $this->target('alpha')], ['REQUEST_EXACT_OUTBOUND_EMAIL_AUTHORIZATION', $this->target('beta')]] as [$transition, $target]) {
            try {
                $consumer->consume($authority['authority_id'], $transition, $target, 'consumer.exact', $this->time->modify('+1 minute'));
                self::fail('Substitution must fail.');
            } catch (\RuntimeException $exception) {
                self::assertSame('IGA112_CALLER_AUTHORITY_INVALID', $exception->getMessage());
            }
        }
        self::assertSame([], glob($this->root.'/var/imperium/runtime/authority-consumptions/*.json') ?: []);
    }

    public function testExpiryAndStaleGenerationFailClosed(): void
    {
        $authority = (new DeterministicTransitionCallerAuthorityIssuanceService($this->root))->issueSeneschal($this->bindingId, $this->target('expiry'), $this->time, $this->time->modify('+1 minute'));
        try {
            (new DeterministicTransitionCallerAuthorityConsumer($this->root))->consume($authority['authority_id'], 'REQUEST_EXACT_OUTBOUND_EMAIL_AUTHORIZATION', $this->target('expiry'), 'consumer.exact', $this->time->modify('+1 minute'));
            self::fail('Expired authority must fail.');
        } catch (\RuntimeException $exception) {
            self::assertSame('IGA112_CALLER_AUTHORITY_INVALID', $exception->getMessage());
        }

        $fresh = $this->authority($this->target('generation'));
        $this->writeOccupancy(2);
        $this->expectExceptionMessage('IGA114_CALLER_PRINCIPAL_STALE');
        (new DeterministicTransitionCallerAuthorityConsumer($this->root))->consume($fresh['authority_id'], 'REQUEST_EXACT_OUTBOUND_EMAIL_AUTHORIZATION', $this->target('generation'), 'consumer.exact', $this->time->modify('+1 minute'));
    }

    public function testTamperAndSecretExclusionProofsAreExplicit(): void
    {
        $authority = $this->authority($this->target('secret-marker-never-persisted'));
        $path = $this->root.'/'.DeterministicTransitionCallerAuthorityIssuanceService::AUTHORITIES.'/'.$authority['authority_id'].'.json';
        $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $record['target']['digest'] = hash('sha256', 'attacker');
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString('REAL_PROVIDER_SECRET', (string) file_get_contents($path));
        $this->expectExceptionMessage('IGA112_CALLER_AUTHORITY_INVALID');
        (new DeterministicTransitionCallerAuthorityConsumer($this->root))->consume($authority['authority_id'], 'REQUEST_EXACT_OUTBOUND_EMAIL_AUTHORIZATION', $this->target('secret-marker-never-persisted'), 'consumer.exact', $this->time->modify('+1 minute'));
    }

    public function testProofRecordsProviderAndThreatModelLimits(): void
    {
        $root = dirname(__DIR__, 3);
        $proof = (string) file_get_contents($root.'/docs/iron-gate-evidence-authenticity-adversarial-proof.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-evidence-authenticity-remediation-batch-10-complete.md');
        foreach (['Provider deduplication', '`DEFERRED_BOUNDARY`', 'remote cryptographic authorship', 'hostile writer', 'one authoritative filesystem root'] as $limit) self::assertStringContainsString($limit, $proof);
        foreach (['Only Batch 11 may next be considered', 'authorized by this handoff', 'documentation-only terminal audit'] as $boundary) self::assertStringContainsString($boundary, $handoff);
    }

    private function authority(array $target): array
    {
        return (new DeterministicTransitionCallerAuthorityIssuanceService($this->root))->issueSeneschal($this->bindingId, $target, $this->time, $this->time->modify('+5 minutes'));
    }

    private function target(string $value): array
    {
        return ['id' => 'intent-'.$value, 'digest' => hash('sha256', $value)];
    }

    private function writeOccupancy(int $generation): void
    {
        $record = ['schema' => 'imperium.curia-seneschal-occupancy/v1', 'binding_id' => $this->bindingId, 'instance_id' => 'imperium-test', 'office' => 'curia', 'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-seneschal-test', 'occupancy_generation' => $generation, 'status' => 'ACTIVE', 'outbound_email_request_authority' => true, 'execution_authority' => false, 'sealed' => true];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $directory = $this->root.'/var/imperium/offices/curia/occupancy';
        if (!is_dir($directory)) mkdir($directory, 0770, true);
        file_put_contents($directory.'/'.$this->bindingId.'.json', json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
