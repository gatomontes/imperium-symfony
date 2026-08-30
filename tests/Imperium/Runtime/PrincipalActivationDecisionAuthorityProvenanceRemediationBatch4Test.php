<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Evidence\PrincipalActivationDecisionAuthorityProvenanceRemediationInterruptionDemonstration as Interruption;
use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceAggregateResultContract as Result;
use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceReadOnlyAggregateReconstructionService as Reconstruction;
use PHPUnit\Framework\TestCase;

final class PrincipalActivationDecisionAuthorityProvenanceRemediationBatch4Test extends TestCase
{
    private \DateTimeImmutable $at;

    protected function setUp(): void
    {
        $this->at = new \DateTimeImmutable('2026-08-30T13:05:00+00:00');
    }

    public function testMissingEvidenceIsIncompleteAndCreatesNothing(): void
    {
        $result = (new Reconstruction())->reconstruct([], $this->at);

        self::assertSame('INCOMPLETE', $result['classification']);
        self::assertSame(Result::REQUIRED_FIELDS, array_keys($result));
        $this->assertNonAuthority($result);
    }

    public function testRevokedConsumedExpiredAndLifecycleIneligibleChainsRefuse(): void
    {
        $cases = [];

        $revoked = $this->completeShape();
        $revoked['scope_grant']['revocation'] = ['id' => 'revoked'];
        $cases[] = $revoked;

        $consumed = $this->completeShape();
        $consumed['issuance_authorization']['consumed'] = true;
        $cases[] = $consumed;

        $expired = $this->completeShape();
        $expired['scope_grant']['expires_at'] = '2026-08-30T13:04:00+00:00';
        $cases[] = $expired;

        $lifecycle = $this->completeShape();
        $lifecycle['activation_disposition']['source_status'] = 'ACTIVE';
        $cases[] = $lifecycle;

        foreach ($cases as $basis) {
            $result = (new Reconstruction())->reconstruct($basis, $this->at);
            self::assertSame('REFUSED', $result['classification']);
            $this->assertNonAuthority($result);
        }
    }

    public function testCoverageClassifiesIncompleteAndConflicted(): void
    {
        $basis = $this->completeShape();
        self::assertSame(
            'INCOMPLETE',
            (new Reconstruction())->reconstruct($basis, $this->at)['classification'],
        );

        $basis['interruption_evidence'] = [
            $this->interruption('UNKNOWN', 'UNKNOWN'),
        ];
        self::assertSame(
            'INCOMPLETE',
            (new Reconstruction())->reconstruct($basis, $this->at)['classification'],
        );

        $basis['interruption_evidence'] = array_fill(
            0,
            6,
            $this->interruption('UNKNOWN', 'UNKNOWN'),
        );
        self::assertSame(
            'CONFLICTED',
            (new Reconstruction())->reconstruct($basis, $this->at)['classification'],
        );

        $semantic = $this->completeShape();
        $semantic['interruption_evidence'] = $this->completeCoverage();
        $semantic['interruption_evidence'][0]['provider_invoked'] = true;
        self::assertSame(
            'CONFLICTED',
            (new Reconstruction())->reconstruct($semantic, $this->at)['classification'],
        );
    }

    public function testMalformedCompleteChainConflictsWithoutWrites(): void
    {
        $basis = $this->completeShape();
        $basis['interruption_evidence'] = $this->completeCoverage();

        $result = (new Reconstruction())->reconstruct($basis, $this->at);

        self::assertSame('CONFLICTED', $result['classification']);
        self::assertStringStartsWith(
            'EXACT_FIXTURE_VALIDATION_CONFLICT:',
            $result['reasons'][0],
        );
        $this->assertNonAuthority($result);
    }

    public function testReconstructionSourceHasNoPersistenceOrEffectDependency(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/'
                .'PrincipalActivationDecisionAuthorityProvenanceReadOnlyAggregateReconstructionService.php',
        );

        foreach ([
            'ImmutableRecordStore',
            'AtomicTransition',
            'AuthorityConsumptionStore',
            'CredentialBroker',
            'AgentMailEmailTransport',
            'public function issue',
            'public function consume',
            'public function activate',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesSeparateProductionOnly(): void
    {
        $doc = $this->document(
            'docs/principal-activation-decision-authority-provenance-remediation-read-only-aggregate-reconstruction.md',
        );
        $handoff = $this->document(
            'docs/handoffs/principal-activation-decision-authority-provenance-remediation-batch-4-complete.md',
        );

        foreach ([
            'BATCH_4_READ_ONLY_AGGREGATE_RECONSTRUCTION_COMPLETE',
            'ELIGIBLE',
            'INCOMPLETE',
            'CONFLICTED',
            'REFUSED',
            'writes no record',
            'exact caller-supplied evidence',
            'six interruption cases',
            'does not repair',
        ] as $claim) {
            self::assertNotFalse(stripos($doc, $claim), $claim);
        }
        foreach ([
            'Only remediation Batch 5 may next be considered',
            'separately authorized scope remediation producer',
            'exact Operator Root scope grant',
            'one pending successor generation',
            'separate lifecycle activation',
            'one decision-issuance authorization',
            'may not create an activation decision',
            'Iron Gate',
            'Lazaretto',
            'approximately three batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function completeShape(): array
    {
        $authority = [
            'issued_at' => '2026-08-30T13:00:00+00:00',
            'expires_at' => '2026-08-30T13:10:00+00:00',
            'revocation' => null,
            'consumed' => false,
        ];

        return [
            'source_principal' => [],
            'scope_grant' => $authority + ['instance_id' => 'imperium-test'],
            'scope_successor' => [],
            'activation_disposition' => [
                'source_status' => 'PENDING_ACTIVATION',
                'disposition' => 'ACTIVATE',
                'effective_at' => '2026-08-30T13:01:00+00:00',
                'caller_authority_issuance_permitted_after_effective_at' => true,
                'authority_scope_changed' => false,
                'external_action_performed' => false,
            ],
            'principal_attestation' => [],
            'provider_assurance_admission' => [],
            'execution_boundary' => [],
            'issuance_authorization' => $authority,
            'interruption_evidence' => [],
        ];
    }

    private function completeCoverage(): array
    {
        $records = [];
        foreach (Interruption::FIXTURE_PATHS as $fixturePath) {
            foreach (Interruption::CUTS as $cut) {
                $records[] = $this->interruption($fixturePath, $cut);
            }
        }
        return $records;
    }

    private function interruption(string $fixturePath, string $cut): array
    {
        return [
            'fixture_path' => $fixturePath,
            'cut' => $cut,
            'classification' => 'CONVERGENT_RECOVERABLE',
            'recovery' => [
                'read_only' => true,
                'repair_performed' => false,
            ],
            'authority_issued_or_consumed' => false,
            'principal_or_binding_activated' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_action_performed' => false,
        ];
    }

    private function assertNonAuthority(array $result): void
    {
        self::assertTrue($result['read_only']);
        foreach ([
            'record_created',
            'record_repaired',
            'scope_granted',
            'authority_issued',
            'authority_consumed',
            'principal_created',
            'principal_activated',
            'binding_activated',
            'activation_decision_created',
            'source_artifact_mutated',
            'credential_or_capability_handled',
            'provider_invoked',
            'external_action_performed',
        ] as $field) {
            self::assertFalse($result[$field], $field);
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
