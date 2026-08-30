<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\CorridorDispositionPrincipalAuthorityRemediationAggregateResultContract as Result;
use App\Imperium\Runtime\Imperator\CorridorDispositionPrincipalAuthorityRemediationReadOnlyAggregateReconstructionService as Reconstruction;
use PHPUnit\Framework\TestCase;

final class CorridorDispositionPrincipalAuthorityRemediationBatch4Test extends TestCase
{
    private \DateTimeImmutable $at;
    protected function setUp(): void { $this->at = new \DateTimeImmutable('2026-08-30T13:05:00+00:00'); }

    public function testMissingEvidenceIsIncompleteAndCreatesNothing(): void
    {
        $result = (new Reconstruction())->reconstruct([], $this->at);
        self::assertSame('INCOMPLETE', $result['classification']); self::assertSame(Result::REQUIRED_FIELDS, array_keys($result));
        $this->assertNonAuthority($result);
    }

    public function testRevocationAndIneligiblePrincipalRefuseBeforeValidation(): void
    {
        $basis = $this->completeShape(); $basis['scope_grant']['revocation'] = ['id' => 'revoked'];
        self::assertSame('REFUSED', (new Reconstruction())->reconstruct($basis, $this->at)['classification']);
        $basis = $this->completeShape(); $basis['issuer_principal']['status'] = 'PENDING_ACTIVATION';
        self::assertSame('REFUSED', (new Reconstruction())->reconstruct($basis, $this->at)['classification']);
    }

    public function testMalformedCompleteChainIsConflictedWithoutWrites(): void
    {
        $basis = $this->completeShape(); $basis['interruption_evidence'] = $this->completeCoverage();
        $result = (new Reconstruction())->reconstruct($basis, $this->at);
        self::assertSame('CONFLICTED', $result['classification']); $this->assertNonAuthority($result);
    }

    public function testCoverageClassifiesIncompleteAndConflicted(): void
    {
        $basis = $this->completeShape(); $basis['interruption_evidence'] = [];
        self::assertSame('INCOMPLETE', (new Reconstruction())->reconstruct($basis, $this->at)['classification']);
        $basis = $this->completeShape(); $basis['interruption_evidence'] = [$this->interruption('UNKNOWN', 'UNKNOWN')];
        self::assertSame('INCOMPLETE', (new Reconstruction())->reconstruct($basis, $this->at)['classification']);
        $basis['interruption_evidence'] = array_fill(0, 12, $this->interruption('UNKNOWN', 'UNKNOWN'));
        self::assertSame('CONFLICTED', (new Reconstruction())->reconstruct($basis, $this->at)['classification']);
    }

    public function testDocumentationAuthorizesOnlySeparatelyAuthorizedProducerNext(): void
    {
        $root = dirname(__DIR__, 3); $doc = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/corridor-disposition-principal-authority-remediation-read-only-aggregate-reconstruction.md')); $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/handoffs/corridor-disposition-principal-authority-remediation-batch-4-complete.md'));
        foreach (['BATCH_4_READ_ONLY_AGGREGATE_RECONSTRUCTION_COMPLETE', 'ELIGIBLE', 'INCOMPLETE', 'CONFLICTED', 'REFUSED', 'writes no record', 'exact caller-supplied', 'twelve interruption cases', 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE'] as $claim) self::assertNotFalse(stripos($doc, $claim), $claim);
        foreach (['Only remediation Batch 5 is authorized', 'separately authorized scope remediation producer', 'exact Operator Root authority', 'one successor generation', 'separate activation', 'one exact corridor caller authority', 'may not select or seal a disposition', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }

    private function completeShape(): array
    {
        $record = ['issued_at' => '2026-08-30T13:00:00+00:00', 'expires_at' => '2026-08-30T13:10:00+00:00', 'revocation' => null];
        return ['source_principal' => [], 'scope_grant' => $record, 'scope_successor' => [], 'issuer_principal' => ['status' => 'ACTIVE', 'authority_scope' => ['corridor_disposition_authority' => true]], 'activation_disposition' => [], 'target' => ['instance_id' => 'imperium-test'], 'evidence_dossier' => [], 'eligibility' => ['continuing_custody_refusal' => 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE'], 'issuance_authorization' => $record + ['custody_refusal' => 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE'], 'interruption_evidence' => []];
    }
    private function interruption(string $transition, string $cut): array { return ['transition' => $transition, 'cut' => $cut, 'classification' => 'CONVERGENT_RECOVERABLE', 'recovery' => ['read_only' => true], 'live_authority_issued_or_consumed' => false, 'live_principal_or_binding_activated' => false, 'activation_artifact_mutated' => false, 'external_action_performed' => false, 'continuing_custody_refusal' => 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE']; }
    private function completeCoverage(): array { $records = []; foreach (\App\Imperium\Runtime\Evidence\CorridorDispositionPrincipalAuthorityRemediationInterruptionDemonstration::TRANSITIONS as $transition) foreach (\App\Imperium\Runtime\Evidence\CorridorDispositionPrincipalAuthorityRemediationInterruptionDemonstration::CUTS as $cut) $records[] = $this->interruption($transition, $cut); return $records; }
    private function assertNonAuthority(array $result): void { self::assertTrue($result['read_only']); foreach (['authority_created', 'authority_issued', 'authority_consumed', 'principal_created', 'principal_activated', 'binding_activated', 'caller_authority_created', 'disposition_selected', 'disposition_sealed', 'source_artifact_mutated', 'external_action_performed'] as $field) self::assertFalse($result[$field], $field); }
}
