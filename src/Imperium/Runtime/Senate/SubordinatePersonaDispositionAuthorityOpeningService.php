<?php declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaDispositionAuthorityOpeningService
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

    public function open(string $reconciliationId, \DateTimeImmutable $openedAt): array
    {
        if (!preg_match('/^senate-persona-reconciliation-[a-f0-9]{20}$/', $reconciliationId)) throw new \InvalidArgumentException('S904_PERSONA_RECONCILIATION_ID_INVALID');
        $existing = $this->existing($reconciliationId);
        if (null !== $existing) return $existing;
        $reconciliation = $this->read('persona-reconciliations', $reconciliationId);
        $phase = $reconciliation['disposition_phase_opening_authority'] ?? null;
        $lordSpeaker = $this->lordSpeaker($reconciliation);
        $findings = $this->findings($reconciliation);
        if ('imperium.senate-persona-reconciliation/v1' !== ($reconciliation['schema'] ?? null)
            || 'PERSONA_FINDINGS_RECONCILED_PENDING_DISPOSITION_AUTHORITY_OPENING' !== ($reconciliation['status'] ?? null)
            || !is_array($phase)
            || true !== ($phase['authority_single_use'] ?? null)
            || true !== ($phase['authority_exercisable'] ?? null)
            || ($phase['holder'] ?? null) !== ($reconciliation['lord_speaker'] ?? null)
            || 'OPEN_ONE_PERSONA_SENATE_DISPOSITION_AUTHORITY' !== ($phase['purpose'] ?? null)
            || true !== ($phase['security_block_must_be_preserved'] ?? null)
            || false !== ($phase['consumed'] ?? null)
            || true !== ($reconciliation['reconciliation_authority']['consumed'] ?? null)
            || true !== ($reconciliation['mandatory_security_block_preserved'] ?? null)
            || false !== ($reconciliation['vote_authority'] ?? null)
            || false !== ($reconciliation['aggregation_authority'] ?? null)
            || false !== ($reconciliation['senate_disposition_authority'] ?? null)
            || true === ($reconciliation['admission_authority'] ?? null)
            || true === ($reconciliation['execution_authority'] ?? null)) throw new \RuntimeException('S905_PERSONA_DISPOSITION_OPENING_CHAIN_INVALID');
        $authorityId = 'persona-senate-disposition-authority-'.substr(hash('sha256', CanonicalJson::encode([$reconciliationId, $reconciliation['record_digest'], $lordSpeaker, array_column($findings, 'record_digest')])), 0, 20);
        $openingId = 'senate-persona-disposition-authority-opening-'.substr(hash('sha256', CanonicalJson::encode([$authorityId, $phase['authority_id']])), 0, 20);
        return $this->records->put('var/imperium/offices/senate/persona-disposition-authority-openings', $openingId, [
            'schema' => 'imperium.senate-persona-disposition-authority-opening/v1',
            'opening_id' => $openingId,
            'instance_id' => $reconciliation['instance_id'],
            'authority_set_id' => $reconciliation['authority_set_id'],
            'authority_set_digest' => $reconciliation['authority_set_digest'],
            'required_trial_ledger_id' => $reconciliation['required_trial_ledger_id'],
            'required_trial_ledger_digest' => $reconciliation['required_trial_ledger_digest'],
            'source_reconciliation' => ['id' => $reconciliationId, 'digest' => $reconciliation['record_digest']],
            'lord_speaker' => $lordSpeaker,
            'admitted_findings' => $reconciliation['admitted_findings'],
            'reconciliation' => $reconciliation['reconciliation'],
            'mandatory_security_blocking_condition' => $reconciliation['mandatory_security_blocking_condition'],
            'mandatory_security_block_preserved' => true,
            'disposition_phase_opening_authority' => ['id' => $phase['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'disposition_authority' => ['authority_id' => $authorityId, 'authority_single_use' => true, 'authority_exercisable' => true, 'holder' => $lordSpeaker, 'permitted_dispositions' => ['CONFIRMED', 'RETURN_TO_FOUNDRY', 'REFUSED', 'UNRESOLVED'], 'security_block_must_be_preserved' => true, 'consumed' => false, 'continuing_authority' => false],
            'opened_at' => $openedAt->format(DATE_ATOM),
            'status' => 'PERSONA_DISPOSITION_AUTHORITY_OPENED_PENDING_LORD_SPEAKER_DISPOSITION',
            'senate_disposition_authority' => true,
            'senate_disposition' => null,
            'vote_authority' => false,
            'aggregation_authority' => false,
            'admission_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
    }

    private function lordSpeaker(array $reconciliation): array
    {
        $holder = $reconciliation['lord_speaker'] ?? null;
        $bindingId = is_array($holder) ? $holder['binding_id'] ?? null : null;
        if (!is_string($bindingId)) throw new \RuntimeException('S905_PERSONA_DISPOSITION_OPENING_CHAIN_INVALID');
        $record = $this->read('occupancy', $bindingId);
        if (($holder['binding_digest'] ?? null) !== ($record['record_digest'] ?? null) || ($reconciliation['instance_id'] ?? null) !== ($record['instance_id'] ?? null) || 'senate.lord-speaker' !== ($record['seat'] ?? null) || 'ACTIVE' !== ($record['status'] ?? null) || true !== ($record['binding_atomic'] ?? null) || true !== ($record['senate_disposition_authority'] ?? null) || true === ($record['execution_authority'] ?? null)) throw new \RuntimeException('S905_PERSONA_DISPOSITION_OPENING_CHAIN_INVALID');
        return $holder;
    }

    private function findings(array $reconciliation): array
    {
        $snapshots = $reconciliation['admitted_findings'] ?? null;
        if (!is_array($snapshots) || 4 !== count($snapshots)) throw new \RuntimeException('S905_PERSONA_DISPOSITION_OPENING_CHAIN_INVALID');
        $records = [];
        foreach (['practice', 'governance', 'consistency', 'security'] as $index => $jurisdiction) {
            $snapshot = $snapshots[$index] ?? null;
            if (!is_array($snapshot) || $jurisdiction !== ($snapshot['jurisdiction'] ?? null) || !is_string($snapshot['finding_record_id'] ?? null)) throw new \RuntimeException('S905_PERSONA_DISPOSITION_OPENING_CHAIN_INVALID');
            $record = $this->read('persona-findings', $snapshot['finding_record_id']);
            if (($snapshot['finding_record_digest'] ?? null) !== ($record['record_digest'] ?? null) || CanonicalJson::encode($snapshot['finding'] ?? null) !== CanonicalJson::encode($record['finding'] ?? null) || true !== ($record['finding']['sealed'] ?? null)) throw new \RuntimeException('S905_PERSONA_DISPOSITION_OPENING_CHAIN_INVALID');
            $records[] = $record;
        }
        return $records;
    }

    private function read(string $directory, string $id): array
    {
        $record = $this->validator->read($this->senateRoot.'/'.$directory.'/'.$id.'.json', 'S905_PERSONA_DISPOSITION_OPENING_CHAIN_INVALID');
        return $this->validator->requireIntact($record, 'S905_PERSONA_DISPOSITION_OPENING_CHAIN_INVALID');
    }

    private function existing(string $reconciliationId): ?array
    {
        $matches = [];
        foreach (glob($this->senateRoot.'/persona-disposition-authority-openings/*.json') ?: [] as $path) {
            $record = $this->validator->read($path, 'S906_PERSONA_DISPOSITION_OPENING_CONFLICT');
            if ($this->validator->isIntact($record) && $reconciliationId === ($record['source_reconciliation']['id'] ?? null)) $matches[] = $record;
        }
        if (1 < count($matches)) throw new \RuntimeException('S906_PERSONA_DISPOSITION_OPENING_CONFLICT');
        return $matches[0] ?? null;
    }
}
