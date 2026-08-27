<?php declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityResolver;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SenatePersonaReconciliationGovernanceCognitionAuthorityResolver implements GovernanceCognitionAuthorityResolver
{
    private string $senateRoot;
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, ?RecordReferenceValidator $validator = null)
    {
        $this->senateRoot = $root.'/var/imperium/offices/senate';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function supports(string $cluster, string $authorityType): bool
    {
        return 'senate-persona-confirmation' === $cluster && 'reconciliation' === $authorityType;
    }

    public function resolve(string $cluster, string $authorityType, string $authorityId): array
    {
        if (!$this->supports($cluster, $authorityType)) throw new \RuntimeException('GCA660_PERSONA_RECONCILIATION_AUTHORITY_UNSUPPORTED');
        $opening = $this->opening($authorityId);
        [$surface, $findings] = $this->inputs($opening, $authorityId);
        return [
            'cluster' => $cluster,
            'authority_type' => $authorityType,
            'authority_id' => $authorityId,
            'instance_id' => $opening['instance_id'],
            'case_id' => $opening['reconciliation_opening_id'],
            'case_digest' => $opening['record_digest'],
            'seat' => 'senate.lord-speaker',
            'purpose' => 'reconcile-persona-findings',
            'input_digest' => hash('sha256', CanonicalJson::encode([$surface, $findings])),
            'source' => ['id' => $opening['reconciliation_opening_id'], 'digest' => $opening['record_digest']],
            'single_use' => true,
            'exercisable' => true,
            'consumed' => $this->consumed($opening['reconciliation_opening_id']),
            'expires_at' => '9999-12-31T23:59:59+00:00',
        ];
    }

    public function inputs(array $opening, string $authorityId): array
    {
        $authority = $opening['reconciliation_authority'] ?? null;
        $this->lordSpeaker($opening);
        if ('imperium.senate-persona-reconciliation-opening/v1' !== ($opening['schema'] ?? null)
            || 'PERSONA_FINDINGS_ADMITTED_UNCHANGED_RECONCILIATION_AUTHORITY_OPENED' !== ($opening['status'] ?? null)
            || !is_array($authority)
            || $authorityId !== ($authority['authority_id'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || ($authority['holder'] ?? null) !== ($opening['lord_speaker'] ?? null)
            || 'RECONCILE_FOUR_UNCHANGED_PERSONA_FINDINGS' !== ($authority['purpose'] ?? null)
            || true !== ($authority['security_block_must_be_preserved'] ?? null)
            || false !== ($authority['voting_included'] ?? null)
            || false !== ($authority['aggregation_included'] ?? null)
            || false !== ($opening['vote_authority'] ?? null)
            || false !== ($opening['aggregation_authority'] ?? null)
            || false !== ($opening['senate_disposition_authority'] ?? null)
            || true === ($opening['execution_authority'] ?? null)) throw new \RuntimeException('GCA662_PERSONA_RECONCILIATION_AUTHORITY_INVALID');
        $snapshots = $opening['admitted_findings'] ?? null;
        if (!is_array($snapshots) || 4 !== count($snapshots)) throw new \RuntimeException('GCA662_PERSONA_RECONCILIATION_AUTHORITY_INVALID');
        $findings = []; $references = [];
        foreach (['practice', 'governance', 'consistency', 'security'] as $index => $jurisdiction) {
            $snapshot = $snapshots[$index] ?? null;
            $record = $this->finding($snapshot, $jurisdiction);
            $findings[] = $record['finding'];
            $references[] = 'persona-finding:'.$jurisdiction.':'.$record['record_digest'];
        }
        if ((true === ($findings[3]['decision']['mandatory_failure'] ?? false)) !== (true === ($opening['mandatory_security_blocking_condition'] ?? false))) throw new \RuntimeException('GCA662_PERSONA_RECONCILIATION_AUTHORITY_INVALID');
        $surface = [
            'reconciliation_authority_id' => $authorityId,
            'reconciliation_opening_id' => $opening['reconciliation_opening_id'],
            'reconciliation_opening_digest' => $opening['record_digest'],
            'lord_speaker' => $opening['lord_speaker'],
            'available_finding_references' => $references,
            'mandatory_security_blocking_condition' => $opening['mandatory_security_blocking_condition'],
            'reconciliation_authority_exercisable' => true,
            'vote_authority' => false,
            'aggregation_authority' => false,
            'senate_disposition_authority' => false,
        ];
        return [$surface, $findings];
    }

    private function lordSpeaker(array $opening): void
    {
        $matches = [];
        foreach (glob($this->senateRoot.'/occupancy/*.json') ?: [] as $path) {
            $record = $this->validator->read($path, 'GCA662_PERSONA_RECONCILIATION_AUTHORITY_INVALID');
            if ($this->validator->isIntact($record) && 'senate.lord-speaker' === ($record['seat'] ?? null)) $matches[] = $record;
        }
        $holder = $opening['lord_speaker'] ?? null;
        if (1 !== count($matches) || !is_array($holder) || ($opening['instance_id'] ?? null) !== ($matches[0]['instance_id'] ?? null) || 'ACTIVE' !== ($matches[0]['status'] ?? null) || true !== ($matches[0]['binding_atomic'] ?? null) || true !== ($matches[0]['senate_disposition_authority'] ?? null) || true === ($matches[0]['execution_authority'] ?? null) || ($holder['binding_id'] ?? null) !== ($matches[0]['binding_id'] ?? null) || ($holder['binding_digest'] ?? null) !== ($matches[0]['record_digest'] ?? null)) throw new \RuntimeException('GCA662_PERSONA_RECONCILIATION_AUTHORITY_INVALID');
    }

    private function finding(mixed $snapshot, string $jurisdiction): array
    {
        if (!is_array($snapshot) || $jurisdiction !== ($snapshot['jurisdiction'] ?? null) || !is_string($snapshot['finding_record_id'] ?? null) || !is_string($snapshot['finding_record_digest'] ?? null) || !is_array($snapshot['finding'] ?? null)) throw new \RuntimeException('GCA662_PERSONA_RECONCILIATION_AUTHORITY_INVALID');
        $path = $this->senateRoot.'/persona-findings/'.$snapshot['finding_record_id'].'.json';
        $record = $this->validator->read($path, 'GCA662_PERSONA_RECONCILIATION_AUTHORITY_INVALID');
        if (!$this->validator->isIntact($record) || $jurisdiction !== ($record['jurisdiction'] ?? null) || $snapshot['finding_record_digest'] !== ($record['record_digest'] ?? null) || CanonicalJson::encode($snapshot['finding']) !== CanonicalJson::encode($record['finding'] ?? null) || true !== ($record['finding']['sealed'] ?? null)) throw new \RuntimeException('GCA662_PERSONA_RECONCILIATION_AUTHORITY_INVALID');
        return $record;
    }

    private function opening(string $authorityId): array
    {
        $matches = [];
        foreach (glob($this->senateRoot.'/persona-reconciliation-openings/*.json') ?: [] as $path) {
            $record = $this->validator->read($path, 'GCA661_PERSONA_RECONCILIATION_AUTHORITY_ABSENT');
            if ($this->validator->isIntact($record) && $authorityId === ($record['reconciliation_authority']['authority_id'] ?? null)) $matches[] = $record;
        }
        if (1 !== count($matches)) throw new \RuntimeException('GCA661_PERSONA_RECONCILIATION_AUTHORITY_ABSENT');
        return $matches[0];
    }

    private function consumed(string $openingId): bool
    {
        foreach (glob($this->senateRoot.'/persona-reconciliations/*.json') ?: [] as $path) {
            $record = $this->validator->read($path, 'GCA662_PERSONA_RECONCILIATION_AUTHORITY_INVALID');
            if ($this->validator->isIntact($record) && $openingId === ($record['source_reconciliation_opening']['id'] ?? null)) return true;
        }
        return false;
    }
}
