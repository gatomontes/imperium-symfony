<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Clavium\CrossProcessCapabilityCustodyFeasibilityContract;
use App\Imperium\Runtime\Clavium\CrossProcessCapabilityCustodyFeasibilityService;
use App\Imperium\Runtime\Clavium\ProcessLossCapabilityCustodyEvidenceContract;
use App\Imperium\Runtime\Evidence\ProcessLossCapabilityCustodyDemonstration;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationIntegrityRemediationBatch5Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-pbi-batch5-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0770, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->root)) return;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        rmdir($this->root);
    }

    public function testDistinctRestartProcessProvesOfflinePossessionLossWithoutReconstruction(): void
    {
        if (!function_exists('proc_open')) self::markTestSkipped('proc_open is required for process-loss evidence.');
        $assessmentId = 'cross-process-capability-custody-feasibility-'.str_repeat('a', 20);
        $identity = ['capability_id' => 'capability.offline-identity', 'identity_digest' => str_repeat('2', 64), 'credential_reference_digest' => str_repeat('3', 64), 'issuer_id' => 'environment-broker'];
        $assessment = (new ImmutableRecordStore($this->root, new AtomicTransition($this->root)))->put(CrossProcessCapabilityCustodyFeasibilityService::ASSESSMENTS, $assessmentId, [
            'schema' => CrossProcessCapabilityCustodyFeasibilityContract::SCHEMA, 'assessment_id' => $assessmentId, 'instance_id' => 'imperium-test',
            'source_activation' => ['id' => 'activation.test', 'digest' => str_repeat('1', 64), 'schema' => 'activation/v1'], 'capability_identity' => $identity,
            'broker_assessment' => ['issuer_recognizes_exact_object' => true, 'recipient_recognizes_exact_object' => false, 'cross_process_custody_supported' => false, 'metadata_reconstruction_permitted' => false],
            'disposition' => CrossProcessCapabilityCustodyFeasibilityContract::REFUSAL, 'reasons' => ['Process-local possession only.'], 'assessed_at' => '2026-08-29T22:00:00+00:00',
            'custody_created' => false, 'delivery_created' => false, 'capability_issued' => false, 'capability_reconstructed' => false, 'credential_reference_persisted' => false, 'secret_material_persisted' => false, 'external_action_performed' => false, 'sealed' => true,
        ]);

        $evidence = (new ProcessLossCapabilityCustodyDemonstration($this->root))->demonstrate($assessmentId, new \DateTimeImmutable('2026-08-29T22:10:00+00:00'));
        self::assertSame('POSSESSION_LOST', $evidence['classification']);
        self::assertNotSame($evidence['issuer_process']['process_marker'], $evidence['restart_process']['process_marker']);
        self::assertFalse($evidence['recovery_attempt']['possession_recovered']);
        self::assertFalse($evidence['capability_reconstructed']);
        self::assertFalse($evidence['credential_reference_persisted']);
        self::assertFalse($evidence['credential_resolved']);
        self::assertFalse($evidence['external_action_performed']);
        self::assertSame($assessment['record_digest'], (new ImmutableRecordStore($this->root, new AtomicTransition($this->root)))->read(CrossProcessCapabilityCustodyFeasibilityService::ASSESSMENTS, $assessmentId)['record_digest']);
        foreach (ProcessLossCapabilityCustodyEvidenceContract::NON_AUTHORITIES as $permission) self::assertFalse($permission);
    }

    public function testDocumentationAuthorizesOnlyCorridorDispositionNext(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-integrity-remediation-batch-5-complete.md');
        foreach (['Only Batch 6 is authorized', 'corridor disposition', 'POSSESSION_LOST', 'may not produce', 'principal provenance', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
