<?php declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaGovernanceFindingService
{
    private string $senateRoot;
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private SenatorFindingCognitionGateway $cognition, ?RecordReferenceValidator $validator = null, ?ImmutableRecordStore $records = null)
    {
        $this->senateRoot = $root.'/var/imperium/offices/senate';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
        $this->records = $records ?? new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function issue(string $authoritySetId): array
    {
        if (!preg_match('/^senate-persona-finding-authority-set-[a-f0-9]{20}$/', $authoritySetId)) throw new \InvalidArgumentException('S889_FINDING_AUTHORITY_SET_ID_INVALID');
        $set = $this->read('finding-authority-sets', $authoritySetId);
        $practice = $this->prior($authoritySetId, 'practice');
        $ledger = $this->read('required-trial-ledgers', $set['required_trial_ledger_id']);
        $baseline = $this->read('jurisdiction-baselines', $ledger['baseline_id']);
        $authority = $this->authority($set, 'governance');
        $turn = $this->turn($baseline, 'governance');
        $pressure = $this->pressure($ledger, 'governance');
        $senator = $this->senator($ledger['instance_id'], $turn['assignment']['senator'] ?? null);
        $evidence = ['baseline_turn' => $turn, 'pressure_trial' => $pressure, 'available_evidence_references' => ['baseline:governance:'.$turn['turn_digest'], 'pressure:governance:'.$pressure['trial_digest']]];
        $assignment = ['jurisdiction' => 'governance', 'authority_id' => $authority['authority_id'], 'senator' => $this->actor($senator), 'required_trial_ledger_id' => $ledger['ledger_id'], 'required_trial_ledger_digest' => $ledger['record_digest'], 'available_evidence_references' => $evidence['available_evidence_references'], 'finding_authority' => true, 'vote_authority' => false, 'senate_disposition_authority' => false];
        if (($practice['authority_set_digest'] ?? null) !== ($set['record_digest'] ?? null)
            || ($practice['required_trial_ledger_digest'] ?? null) !== ($ledger['record_digest'] ?? null)
            || 'PRACTICE_FINDING_SEALED_PENDING_REMAINING_FINDINGS' !== ($practice['status'] ?? null)
            || 'SENATOR_FINDING_AUTHORITIES_OPEN_PENDING_SEPARATE_FINDINGS' !== ($set['status'] ?? null)
            || ($set['required_trial_ledger_digest'] ?? null) !== ($ledger['record_digest'] ?? null)
            || 'REQUIRED_TRIALS_SEALED_PENDING_SENATOR_FINDINGS' !== ($ledger['status'] ?? null)
            || true === ($set['execution_authority'] ?? null)) throw new \RuntimeException('S890_GOVERNANCE_FINDING_CHAIN_INVALID');
        $decision = $this->cognition->find('governance', $assignment, $evidence);
        $this->validateDecision($decision, $evidence);
        $finding = ['jurisdiction' => 'governance', 'senator' => $this->actor($senator), 'assignment' => $assignment, 'decision' => $decision, 'evidence_package_digest' => hash('sha256', CanonicalJson::encode($evidence)), 'attributable' => true, 'sealed' => true];
        $finding['finding_digest'] = hash('sha256', CanonicalJson::encode($finding));
        $recordId = 'senate-persona-finding-governance-'.substr(hash('sha256', CanonicalJson::encode([$authoritySetId, $set['record_digest'], $authority['authority_id'], $finding['finding_digest']])), 0, 20);
        return $this->records->put('var/imperium/offices/senate/persona-findings', $recordId, ['schema' => 'imperium.senate-persona-finding/v1', 'finding_record_id' => $recordId, 'instance_id' => $ledger['instance_id'], 'authority_set_id' => $authoritySetId, 'authority_set_digest' => $set['record_digest'], 'required_trial_ledger_id' => $ledger['ledger_id'], 'required_trial_ledger_digest' => $ledger['record_digest'], 'jurisdiction' => 'governance', 'source_practice_finding' => ['id' => $practice['finding_record_id'], 'digest' => $practice['record_digest']], 'finding_authority' => ['id' => $authority['authority_id'], 'consumed' => true, 'continuing_authority' => false], 'finding' => $finding, 'remaining_jurisdictions' => ['consistency', 'security'], 'status' => 'GOVERNANCE_FINDING_SEALED_PENDING_REMAINING_FINDINGS', 'senate_disposition_authority' => false, 'admission_authority' => false, 'execution_authority' => false, 'sealed' => true]);
    }

    private function prior(string $authoritySetId, string $jurisdiction): array
    {
        $matches = [];
        foreach (glob($this->senateRoot.'/persona-findings/*.json') ?: [] as $path) {
            $record = $this->validator->read($path, 'S890_GOVERNANCE_FINDING_CHAIN_INVALID');
            if ($this->validator->isIntact($record) && $authoritySetId === ($record['authority_set_id'] ?? null) && $jurisdiction === ($record['jurisdiction'] ?? null)) $matches[] = $record;
        }
        if (1 !== count($matches)) throw new \RuntimeException('S890_GOVERNANCE_FINDING_CHAIN_INVALID');
        return $matches[0];
    }

    private function authority(array $set, string $jurisdiction): array
    {
        $matches = array_values(array_filter($set['authorities'] ?? [], static fn ($authority): bool => is_array($authority) && $jurisdiction === ($authority['jurisdiction'] ?? null)));
        if (1 !== count($matches) || true !== ($matches[0]['authority_exercisable'] ?? null) || false !== ($matches[0]['consumed'] ?? null)) throw new \RuntimeException('S890_GOVERNANCE_FINDING_CHAIN_INVALID');
        return $matches[0];
    }

    private function turn(array $baseline, string $jurisdiction): array
    {
        $matches = array_values(array_filter($baseline['turns'] ?? [], static fn ($turn): bool => is_array($turn) && $jurisdiction === ($turn['jurisdiction'] ?? null)));
        if (1 !== count($matches)) throw new \RuntimeException('S890_GOVERNANCE_FINDING_CHAIN_INVALID');
        return $matches[0];
    }

    private function pressure(array $ledger, string $jurisdiction): array
    {
        $matches = array_values(array_filter($ledger['pressure_trials'] ?? [], static fn ($trial): bool => is_array($trial) && $jurisdiction === ($trial['jurisdiction'] ?? null)));
        if (1 !== count($matches)) throw new \RuntimeException('S890_GOVERNANCE_FINDING_CHAIN_INVALID');
        return $matches[0];
    }

    private function senator(string $instanceId, mixed $reference): array
    {
        $matches = [];
        foreach (glob($this->senateRoot.'/occupancy/*.json') ?: [] as $path) {
            $record = $this->validator->read($path, 'S890_GOVERNANCE_FINDING_CHAIN_INVALID');
            if ($this->validator->isIntact($record) && 'senate.committee.governance' === ($record['seat'] ?? null)) $matches[] = $record;
        }
        if (1 !== count($matches) || $instanceId !== ($matches[0]['instance_id'] ?? null) || true !== ($matches[0]['senator_finding_authority'] ?? null) || !is_array($reference) || ($reference['binding_digest'] ?? null) !== ($matches[0]['record_digest'] ?? null)) throw new \RuntimeException('S890_GOVERNANCE_FINDING_CHAIN_INVALID');
        return $matches[0];
    }

    private function actor(array $binding): array
    {
        return ['seat' => $binding['seat'], 'binding_id' => $binding['binding_id'], 'binding_digest' => $binding['record_digest'], 'manifestation_id' => $binding['manifestation_id'], 'occupancy_generation' => $binding['occupancy_generation'], 'founding_class' => $binding['founding_class'] ?? 'ARTIFACT_BACKED', 'placeholder_version' => $binding['placeholder_version'] ?? null];
    }

    private function validateDecision(array $decision, array $evidence): void
    {
        $keys = array_keys($decision); sort($keys, SORT_STRING);
        if (['disposition', 'evidence_references', 'limitations', 'mandatory_failure', 'rationale', 'severity'] !== $keys || true === ($decision['mandatory_failure'] ?? null) || !is_array($decision['evidence_references'] ?? null) || [] === $decision['evidence_references']) throw new \RuntimeException('S891_GOVERNANCE_FINDING_INVALID');
        foreach ($decision['evidence_references'] as $reference) if (!in_array($reference, $evidence['available_evidence_references'], true)) throw new \RuntimeException('S891_GOVERNANCE_FINDING_INVALID');
    }

    private function read(string $directory, string $id): array
    {
        $record = $this->validator->read($this->senateRoot.'/'.$directory.'/'.$id.'.json', 'S890_GOVERNANCE_FINDING_CHAIN_INVALID');
        return $this->validator->requireIntact($record, 'S890_GOVERNANCE_FINDING_CHAIN_INVALID');
    }
}
