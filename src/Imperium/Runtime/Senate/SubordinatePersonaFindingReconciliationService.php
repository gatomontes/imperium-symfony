<?php declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaFindingReconciliationService
{
    private string $senateRoot;
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private PersonaFindingReconciliationCognitionGateway $cognition, private SenatePersonaReconciliationGovernanceCognitionAuthorityResolver $resolver, ?RecordReferenceValidator $validator = null, ?ImmutableRecordStore $records = null)
    {
        $this->senateRoot = $root.'/var/imperium/offices/senate';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
        $this->records = $records ?? new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function reconcile(string $openingId): array
    {
        if (!preg_match('/^senate-persona-reconciliation-opening-[a-f0-9]{20}$/', $openingId)) throw new \InvalidArgumentException('S901_RECONCILIATION_OPENING_ID_INVALID');
        $existing = $this->existing($openingId);
        if (null !== $existing) return $existing;
        $opening = $this->read('persona-reconciliation-openings', $openingId);
        $authorityId = (string) ($opening['reconciliation_authority']['authority_id'] ?? '');
        [$surface, $findings] = $this->resolver->inputs($opening, $authorityId);
        $decision = $this->cognition->reconcile($surface, $findings);
        $this->validateDecision($decision, $surface['available_finding_references']);
        $nextAuthorityId = 'persona-disposition-phase-opening-authority-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $opening['record_digest'], $decision, $opening['mandatory_security_blocking_condition']])), 0, 20);
        $recordId = 'senate-persona-reconciliation-'.substr(hash('sha256', CanonicalJson::encode([$openingId, $opening['record_digest'], $authorityId, $decision])), 0, 20);

        return $this->records->put('var/imperium/offices/senate/persona-reconciliations', $recordId, [
            'schema' => 'imperium.senate-persona-reconciliation/v1',
            'reconciliation_id' => $recordId,
            'instance_id' => $opening['instance_id'],
            'authority_set_id' => $opening['authority_set_id'],
            'authority_set_digest' => $opening['authority_set_digest'],
            'required_trial_ledger_id' => $opening['required_trial_ledger_id'],
            'required_trial_ledger_digest' => $opening['required_trial_ledger_digest'],
            'source_reconciliation_opening' => ['id' => $openingId, 'digest' => $opening['record_digest']],
            'lord_speaker' => $opening['lord_speaker'],
            'admitted_findings' => $opening['admitted_findings'],
            'mandatory_security_blocking_condition' => $opening['mandatory_security_blocking_condition'],
            'mandatory_security_block_preserved' => true,
            'reconciliation' => $decision,
            'reconciliation_authority' => ['id' => $authorityId, 'consumed' => true, 'continuing_authority' => false],
            'disposition_phase_opening_authority' => ['authority_id' => $nextAuthorityId, 'authority_single_use' => true, 'authority_exercisable' => true, 'holder' => $opening['lord_speaker'], 'purpose' => 'OPEN_ONE_PERSONA_SENATE_DISPOSITION_AUTHORITY', 'security_block_must_be_preserved' => true, 'consumed' => false, 'continuing_authority' => false],
            'status' => 'PERSONA_FINDINGS_RECONCILED_PENDING_DISPOSITION_AUTHORITY_OPENING',
            'deliberation_open' => true,
            'vote_authority' => false,
            'aggregation_authority' => false,
            'senate_disposition_authority' => false,
            'admission_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validateDecision(array $decision, array $references): void
    {
        $keys = array_keys($decision); sort($keys, SORT_STRING);
        if (['agreements', 'attribution_treatment', 'disagreements', 'finding_references', 'limitations', 'rationale', 'severity_treatment', 'uncertainties'] !== $keys || $references !== ($decision['finding_references'] ?? null) || !is_string($decision['rationale'] ?? null) || '' === trim($decision['rationale'])) throw new \RuntimeException('S902_PERSONA_RECONCILIATION_INVALID');
        foreach (['agreements', 'disagreements', 'attribution_treatment', 'severity_treatment', 'limitations', 'uncertainties'] as $field) {
            if (!is_array($decision[$field] ?? null) || !array_is_list($decision[$field])) throw new \RuntimeException('S902_PERSONA_RECONCILIATION_INVALID');
            foreach ($decision[$field] as $value) if (!is_string($value) || '' === trim($value)) throw new \RuntimeException('S902_PERSONA_RECONCILIATION_INVALID');
        }
    }

    private function read(string $directory, string $id): array
    {
        $record = $this->validator->read($this->senateRoot.'/'.$directory.'/'.$id.'.json', 'S903_PERSONA_RECONCILIATION_CHAIN_INVALID');
        return $this->validator->requireIntact($record, 'S903_PERSONA_RECONCILIATION_CHAIN_INVALID');
    }

    private function existing(string $openingId): ?array
    {
        $matches = [];
        foreach (glob($this->senateRoot.'/persona-reconciliations/*.json') ?: [] as $path) {
            $record = $this->validator->read($path, 'S903_PERSONA_RECONCILIATION_CHAIN_INVALID');
            if ($this->validator->isIntact($record) && $openingId === ($record['source_reconciliation_opening']['id'] ?? null)) $matches[] = $record;
        }
        if (1 < count($matches)) throw new \RuntimeException('S903_PERSONA_RECONCILIATION_CHAIN_INVALID');
        return $matches[0] ?? null;
    }
}
