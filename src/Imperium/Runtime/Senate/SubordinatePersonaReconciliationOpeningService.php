<?php declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaReconciliationOpeningService
{
    private string $senateRoot;
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, ?RecordReferenceValidator $validator = null, ?ImmutableRecordStore $records = null)
    {
        $this->senateRoot = $root.'/var/imperium/offices/senate';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
        $this->records = $records ?? new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function open(string $securityFindingId): array
    {
        if (!preg_match('/^senate-persona-finding-security-[a-f0-9]{20}$/', $securityFindingId)) throw new \InvalidArgumentException('S898_SECURITY_FINDING_ID_INVALID');
        $security = $this->read('persona-findings', $securityFindingId);
        $practice = $this->referencedFinding($security['source_practice_finding'] ?? null, 'practice');
        $governance = $this->referencedFinding($security['source_governance_finding'] ?? null, 'governance');
        $consistency = $this->referencedFinding($security['source_consistency_finding'] ?? null, 'consistency');
        $ledger = $this->read('required-trial-ledgers', (string) ($security['required_trial_ledger_id'] ?? ''));
        $baseline = $this->read('jurisdiction-baselines', (string) ($ledger['baseline_id'] ?? ''));
        $witness = $this->read('persona-witnesses', (string) ($baseline['manifestation_id'] ?? ''));
        $lordSpeaker = $this->lordSpeaker((string) ($security['instance_id'] ?? ''), $witness['lord_speaker'] ?? null);
        $ordered = [$practice, $governance, $consistency, $security];

        if ('SECURITY_FINDING_SEALED_PENDING_SEPARATE_RECONCILIATION' !== ($security['status'] ?? null)
            || 'PRACTICE_FINDING_SEALED_PENDING_REMAINING_FINDINGS' !== ($practice['status'] ?? null)
            || 'GOVERNANCE_FINDING_SEALED_PENDING_REMAINING_FINDINGS' !== ($governance['status'] ?? null)
            || 'CONSISTENCY_FINDING_SEALED_PENDING_SECURITY_FINDING' !== ($consistency['status'] ?? null)
            || ($security['required_trial_ledger_digest'] ?? null) !== ($ledger['record_digest'] ?? null)
            || ($ledger['baseline_digest'] ?? null) !== ($baseline['record_digest'] ?? null)
            || ($baseline['manifestation_digest'] ?? null) !== ($witness['record_digest'] ?? null)
            || true !== ($ledger['evidentiary_phase_complete'] ?? null)
            || true === ($security['reconciliation_authority'] ?? null)
            || true === ($security['execution_authority'] ?? null)) throw new \RuntimeException('S899_RECONCILIATION_OPENING_CHAIN_INVALID');

        foreach ($ordered as $index => $finding) {
            $jurisdiction = ['practice', 'governance', 'consistency', 'security'][$index];
            if ($jurisdiction !== ($finding['jurisdiction'] ?? null)
                || ($security['authority_set_id'] ?? null) !== ($finding['authority_set_id'] ?? null)
                || ($security['authority_set_digest'] ?? null) !== ($finding['authority_set_digest'] ?? null)
                || ($ledger['record_digest'] ?? null) !== ($finding['required_trial_ledger_digest'] ?? null)
                || true !== ($finding['finding_authority']['consumed'] ?? null)
                || true !== ($finding['finding']['attributable'] ?? null)
                || true !== ($finding['finding']['sealed'] ?? null)
                || true === ($finding['senate_disposition_authority'] ?? null)
                || true === ($finding['execution_authority'] ?? null)) throw new \RuntimeException('S899_RECONCILIATION_OPENING_CHAIN_INVALID');
        }

        $admitted = array_map(static fn (array $record): array => [
            'jurisdiction' => $record['jurisdiction'],
            'finding_record_id' => $record['finding_record_id'],
            'finding_record_digest' => $record['record_digest'],
            'finding' => $record['finding'],
        ], $ordered);
        $mandatoryBlock = true === ($security['finding']['decision']['mandatory_failure'] ?? false);
        $authorityId = 'persona-reconciliation-authority-'.substr(hash('sha256', CanonicalJson::encode([$securityFindingId, $security['record_digest'], array_column($admitted, 'finding_record_digest'), $lordSpeaker['record_digest']])), 0, 20);
        $openingId = 'senate-persona-reconciliation-opening-'.substr(hash('sha256', CanonicalJson::encode([$authorityId, $security['authority_set_digest'], $ledger['record_digest']])), 0, 20);

        return $this->records->put('var/imperium/offices/senate/persona-reconciliation-openings', $openingId, [
            'schema' => 'imperium.senate-persona-reconciliation-opening/v1',
            'reconciliation_opening_id' => $openingId,
            'instance_id' => $security['instance_id'],
            'authority_set_id' => $security['authority_set_id'],
            'authority_set_digest' => $security['authority_set_digest'],
            'required_trial_ledger_id' => $ledger['ledger_id'],
            'required_trial_ledger_digest' => $ledger['record_digest'],
            'source_security_finding' => ['id' => $securityFindingId, 'digest' => $security['record_digest']],
            'lord_speaker' => $this->actor($lordSpeaker),
            'admitted_findings' => $admitted,
            'jurisdictions' => ['practice', 'governance', 'consistency', 'security'],
            'mandatory_security_blocking_condition' => $mandatoryBlock,
            'reconciliation_authority' => ['authority_id' => $authorityId, 'authority_single_use' => true, 'authority_exercisable' => true, 'holder' => $this->actor($lordSpeaker), 'purpose' => 'RECONCILE_FOUR_UNCHANGED_PERSONA_FINDINGS', 'security_block_must_be_preserved' => true, 'voting_included' => false, 'aggregation_included' => false, 'consumed' => false, 'continuing_authority' => false],
            'status' => 'PERSONA_FINDINGS_ADMITTED_UNCHANGED_RECONCILIATION_AUTHORITY_OPENED',
            'deliberation_open' => true,
            'vote_authority' => false,
            'aggregation_authority' => false,
            'senate_disposition_authority' => false,
            'admission_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
    }

    private function referencedFinding(mixed $reference, string $jurisdiction): array
    {
        if (!is_array($reference) || !is_string($reference['id'] ?? null) || !is_string($reference['digest'] ?? null)) throw new \RuntimeException('S899_RECONCILIATION_OPENING_CHAIN_INVALID');
        $finding = $this->read('persona-findings', $reference['id']);
        if ($jurisdiction !== ($finding['jurisdiction'] ?? null) || $reference['digest'] !== ($finding['record_digest'] ?? null)) throw new \RuntimeException('S899_RECONCILIATION_OPENING_CHAIN_INVALID');
        return $finding;
    }

    private function lordSpeaker(string $instanceId, mixed $reference): array
    {
        $matches = [];
        foreach (glob($this->senateRoot.'/occupancy/*.json') ?: [] as $path) {
            $record = $this->validator->read($path, 'S899_RECONCILIATION_OPENING_CHAIN_INVALID');
            if ($this->validator->isIntact($record) && 'senate.lord-speaker' === ($record['seat'] ?? null)) $matches[] = $record;
        }
        if (1 !== count($matches) || $instanceId !== ($matches[0]['instance_id'] ?? null) || 'ACTIVE' !== ($matches[0]['status'] ?? null) || true !== ($matches[0]['binding_atomic'] ?? null) || true !== ($matches[0]['senate_disposition_authority'] ?? null) || true === ($matches[0]['execution_authority'] ?? null) || !is_array($reference) || ($reference['binding_id'] ?? null) !== ($matches[0]['binding_id'] ?? null) || ($reference['binding_digest'] ?? null) !== ($matches[0]['record_digest'] ?? null)) throw new \RuntimeException('S899_RECONCILIATION_OPENING_CHAIN_INVALID');
        return $matches[0];
    }

    private function actor(array $binding): array
    {
        return ['seat' => $binding['seat'], 'binding_id' => $binding['binding_id'], 'binding_digest' => $binding['record_digest'], 'manifestation_id' => $binding['manifestation_id'], 'occupancy_generation' => $binding['occupancy_generation'], 'founding_class' => $binding['founding_class'] ?? 'ARTIFACT_BACKED', 'placeholder_version' => $binding['placeholder_version'] ?? null];
    }

    private function read(string $directory, string $id): array
    {
        $record = $this->validator->read($this->senateRoot.'/'.$directory.'/'.$id.'.json', 'S899_RECONCILIATION_OPENING_CHAIN_INVALID');
        return $this->validator->requireIntact($record, 'S899_RECONCILIATION_OPENING_CHAIN_INVALID');
    }
}
