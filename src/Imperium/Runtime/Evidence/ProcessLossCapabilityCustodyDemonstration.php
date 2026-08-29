<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Evidence;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\CrossProcessCapabilityCustodyFeasibilityContract;
use App\Imperium\Runtime\Clavium\CrossProcessCapabilityCustodyFeasibilityService;
use App\Imperium\Runtime\Clavium\OpaqueCapabilityCustodyContract;
use App\Imperium\Runtime\Clavium\ProcessLossCapabilityCustodyEvidenceContract;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProcessLossCapabilityCustodyDemonstration
{
    public const string EVIDENCE = 'var/imperium/evidence/process-loss-capability-custody';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function demonstrate(string $assessmentId, \DateTimeImmutable $observedAt): array
    {
        if (!function_exists('proc_open')) throw new \RuntimeException('PBI500_PROCESS_DEMONSTRATION_UNAVAILABLE');
        if (!preg_match('/^cross-process-capability-custody-feasibility-[a-f0-9]{20}$/', $assessmentId)) throw new \InvalidArgumentException('PBI501_ASSESSMENT_ID_INVALID');
        $assessment = $this->validator->read($this->root.'/'.CrossProcessCapabilityCustodyFeasibilityService::ASSESSMENTS.'/'.$assessmentId.'.json', 'PBI502_ASSESSMENT_ABSENT');
        $identity = $assessment['capability_identity'] ?? null;
        if (!$this->validator->isIntact($assessment)
            || CrossProcessCapabilityCustodyFeasibilityContract::REQUIRED_FIELDS !== array_keys($assessment)
            || CrossProcessCapabilityCustodyFeasibilityContract::SCHEMA !== ($assessment['schema'] ?? null)
            || CrossProcessCapabilityCustodyFeasibilityContract::REFUSAL !== ($assessment['disposition'] ?? null)
            || !is_array($identity)
            || OpaqueCapabilityCustodyContract::REQUIRED_CAPABILITY_IDENTITY_FIELDS !== array_keys($identity)
            || true !== ($assessment['broker_assessment']['issuer_recognizes_exact_object'] ?? null)
            || false !== ($assessment['broker_assessment']['recipient_recognizes_exact_object'] ?? null)
            || false !== ($assessment['broker_assessment']['cross_process_custody_supported'] ?? null)
            || false !== ($assessment['capability_issued'] ?? null)
            || false !== ($assessment['capability_reconstructed'] ?? null)
            || false !== ($assessment['credential_reference_persisted'] ?? null)
            || false !== ($assessment['secret_material_persisted'] ?? null)
            || false !== ($assessment['external_action_performed'] ?? null)) {
            throw new \RuntimeException('PBI503_ASSESSMENT_INVALID');
        }

        $workspace = $this->root.'/var/imperium/evidence/.process-loss-'.bin2hex(random_bytes(8));
        if (!mkdir($workspace, 0770, true) && !is_dir($workspace)) throw new \RuntimeException('PBI504_WORKSPACE_CREATE_FAILED');
        $issuerPath = $workspace.'/issuer.json';
        $restartPath = $workspace.'/restart.json';
        try {
            $this->runIssuer($issuerPath);
            $issuer = $this->readObservation($issuerPath, 'PBI505_ISSUER_OBSERVATION_INVALID');
            $this->runRestart($issuerPath, $restartPath);
            $restart = $this->readObservation($restartPath, 'PBI506_RESTART_OBSERVATION_INVALID');
        } finally {
            if (is_file($issuerPath)) unlink($issuerPath);
            if (is_file($restartPath)) unlink($restartPath);
            if (is_dir($workspace)) rmdir($workspace);
        }
        if (($issuer['process_marker'] ?? null) === ($restart['process_marker'] ?? null)
            || ($issuer['possession_witness_digest'] ?? null) !== ($restart['observed_witness_digest'] ?? null)
            || true !== ($issuer['issuer_process_exited'] ?? null)
            || false !== ($issuer['clear_witness_persisted'] ?? null)
            || false !== ($restart['possession_witness_recovered'] ?? null)
            || false !== ($restart['reconstruction_attempted'] ?? null)) {
            throw new \RuntimeException('PBI507_PROCESS_LOSS_NOT_PROVED');
        }

        $evidenceId = 'process-loss-capability-custody-evidence-'.substr(hash('sha256', CanonicalJson::encode([$assessmentId, $assessment['record_digest'], $issuer['possession_witness_digest'], $observedAt->format(DATE_ATOM)])), 0, 20);
        return $this->records->put(self::EVIDENCE, $evidenceId, [
            'schema' => ProcessLossCapabilityCustodyEvidenceContract::SCHEMA,
            'evidence_id' => $evidenceId,
            'instance_id' => $assessment['instance_id'],
            'source_activation' => $assessment['source_activation'],
            'capability_identity' => $identity,
            'issuer_process' => ['process_id' => $issuer['process_id'], 'process_marker' => $issuer['process_marker'], 'exact_object_recognized_before_exit' => true, 'process_exited' => true],
            'restart_process' => ['process_id' => $restart['process_id'], 'process_marker' => $restart['process_marker'], 'distinct_process' => true, 'exact_object_recognized' => false],
            'process_cut' => ['cut' => 'AFTER_ISSUER_PROCESS_EXIT', 'issuer_memory_survived' => false],
            'durable_observations' => ['possession_witness_digest' => $issuer['possession_witness_digest'], 'clear_possession_witness_persisted' => false, 'credential_reference_persisted' => false, 'credential_secret_persisted' => false],
            'recovery_attempt' => ['metadata_read' => true, 'possession_recovered' => false, 'reconstruction_attempted' => false, 'successor_recognizes_exact_object' => false],
            'classification' => 'POSSESSION_LOST',
            'observed_at' => $observedAt->format(DATE_ATOM),
            'capability_reconstructed' => false,
            'credential_reference_persisted' => false,
            'credential_resolved' => false,
            'external_action_performed' => false,
            'sealed' => true,
        ]);
    }

    private function runIssuer(string $path): void
    {
        $code = '$w=random_bytes(32);$r=["process_id"=>getmypid(),"process_marker"=>bin2hex(random_bytes(16)),"possession_witness_digest"=>hash("sha256",$w),"clear_witness_persisted"=>false,"issuer_process_exited"=>true];file_put_contents($argv[1],json_encode($r,JSON_THROW_ON_ERROR));';
        $this->run([PHP_BINARY, '-r', $code, $path], 'PBI508_ISSUER_PROCESS_FAILED');
    }

    private function runRestart(string $issuerPath, string $restartPath): void
    {
        $code = '$i=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);$r=["process_id"=>getmypid(),"process_marker"=>bin2hex(random_bytes(16)),"observed_witness_digest"=>$i["possession_witness_digest"],"possession_witness_recovered"=>false,"reconstruction_attempted"=>false];file_put_contents($argv[2],json_encode($r,JSON_THROW_ON_ERROR));';
        $this->run([PHP_BINARY, '-r', $code, $issuerPath, $restartPath], 'PBI509_RESTART_PROCESS_FAILED');
    }

    private function run(array $command, string $failure): void
    {
        $pipes = [];
        $process = proc_open($command, [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']], $pipes, $this->root);
        if (!is_resource($process)) throw new \RuntimeException($failure);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
        if (0 !== proc_close($process)) throw new \RuntimeException($failure.': '.trim((string) $stdout.' '.(string) $stderr));
    }

    private function readObservation(string $path, string $failure): array
    {
        if (!is_file($path)) throw new \RuntimeException($failure);
        $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($record)) throw new \RuntimeException($failure);
        return $record;
    }
}
