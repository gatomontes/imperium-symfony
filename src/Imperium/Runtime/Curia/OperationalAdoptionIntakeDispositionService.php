<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalAdoptionIntakeDispositionService
{
    private const DECISIONS = ['ACCEPTED', 'REFUSED'];
    private const SENESCHAL_SEAT = 'curia.seneschal';

    private string $presentations;
    private string $occupancy;
    private string $dispositions;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->presentations = $root.'/var/imperium/operational/legate-result-adoption-presentations';
        $this->occupancy = $root.'/var/imperium/operational/occupancy';
        $this->dispositions = $root.'/var/imperium/operational/legate-result-adoption-intake-dispositions';
    }

    public function decide(string $presentationId, string $seneschalBindingId, string $decision, string $rationale, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^legate-result-adoption-presentation-[a-f0-9]{20}$/', $presentationId)) {
            throw new \InvalidArgumentException('CUR450_ADOPTION_PRESENTATION_ID_INVALID');
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $seneschalBindingId)) {
            throw new \InvalidArgumentException('CUR451_SENESCHAL_BINDING_ID_INVALID');
        }
        $decision = strtoupper(trim($decision));
        $rationale = trim($rationale);
        if (!in_array($decision, self::DECISIONS, true) || '' === $rationale) {
            throw new \InvalidArgumentException('CUR452_ADOPTION_INTAKE_DISPOSITION_INVALID');
        }

        $presentation = $this->read($this->presentations.'/'.$presentationId.'.json', 'CUR453_ADOPTION_PRESENTATION_ABSENT');
        foreach (glob($this->dispositions.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CUR458_ADOPTION_INTAKE_DISPOSITION_CONFLICT');
            if (($prior['source_presentation']['id'] ?? null) === $presentationId) {
                if (($prior['source_presentation']['digest'] ?? null) !== ($presentation['record_digest'] ?? null)
                    || ($prior['decision_maker']['binding_id'] ?? null) !== $seneschalBindingId
                    || ($prior['decision'] ?? null) !== $decision
                    || ($prior['rationale'] ?? null) !== $rationale) {
                    throw new \RuntimeException('CUR458_ADOPTION_INTAKE_DISPOSITION_CONFLICT');
                }

                return $prior;
            }
        }

        $seneschal = $this->read($this->occupancy.'/'.$seneschalBindingId.'.json', 'CUR454_SENESCHAL_OCCUPANCY_ABSENT');
        $this->validate($presentationId, $presentation, $seneschalBindingId, $seneschal);
        $this->assertSoleCurrentSeneschalOccupancy($seneschal);

        $decisionMaker = [
            'seat' => self::SENESCHAL_SEAT,
            'binding_id' => $seneschalBindingId,
            'binding_digest' => $seneschal['record_digest'],
            'manifestation_id' => $seneschal['manifestation_id'],
            'occupancy_generation' => $seneschal['occupancy_generation'],
        ];
        $dispositionId = 'legate-result-adoption-intake-disposition-'.substr(hash('sha256', CanonicalJson::encode([$presentationId, $presentation['record_digest'], $decisionMaker, $decision, $rationale])), 0, 20);

        return $this->save($dispositionId, [
            'schema' => 'imperium.legate-result-adoption-intake-disposition/v1',
            'disposition_id' => $dispositionId,
            'instance_id' => $presentation['instance_id'],
            'case_id' => $presentation['case_id'],
            'case_digest' => $presentation['case_digest'],
            'source_presentation' => ['id' => $presentationId, 'digest' => $presentation['record_digest']],
            'source_review' => $presentation['source_review'],
            'source_delivery' => $presentation['source_delivery'],
            'source_cognition_turn' => $presentation['source_cognition_turn'],
            'source_commission' => $presentation['source_commission'],
            'presenter' => $presentation['presenter'],
            'decision_maker' => $decisionMaker,
            'legate' => $presentation['legate'],
            'contract' => $presentation['contract'],
            'result' => $presentation['result'],
            'commissioner_review_rationale' => $presentation['commissioner_review_rationale'],
            'presentation_rationale' => $presentation['presentation_rationale'],
            'decision' => $decision,
            'rationale' => $rationale,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'status' => 'ACCEPTED' === $decision
                ? 'LEGATE_RESULT_ADOPTION_INTAKE_ACCEPTED_PENDING_EVALUATION_OPENING_NO_EVALUATION_AUTHORITY'
                : 'LEGATE_RESULT_ADOPTION_INTAKE_REFUSED_LIFECYCLE_CLOSED_NO_AUTHORITY',
            'commission_closed' => true,
            'governing_intake_decided' => true,
            'governing_intake_accepted' => 'ACCEPTED' === $decision,
            'governing_intake_refused' => 'REFUSED' === $decision,
            'adoption_lifecycle_closed' => 'REFUSED' === $decision,
            'evaluation_opening_authority' => false,
            'result_evaluated_for_adoption' => false,
            'result_operationally_adopted' => false,
            'planning_amendment_authority' => false,
            'follow_up_commission_authority' => false,
            'commission_exercisable' => false,
            'governed_cognition_authority' => false,
            'provider_invocation_authority' => false,
            'credential_use_authority' => false,
            'operational_use_permitted' => false,
            'tool_use_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'continuing_turn_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validate(string $presentationId, array $presentation, string $seneschalBindingId, array $seneschal): void
    {
        $recipient = $presentation['recipient'] ?? [];
        if (!$this->valid($presentation) || 'imperium.legate-result-adoption-presentation/v1' !== ($presentation['schema'] ?? null)
            || $presentationId !== ($presentation['presentation_id'] ?? null)
            || 'LEGATE_RESULT_ADOPTION_REQUEST_PRESENTED_PENDING_GOVERNING_INTAKE' !== ($presentation['status'] ?? null)
            || 'curia' !== ($recipient['office'] ?? null) || self::SENESCHAL_SEAT !== ($recipient['seat'] ?? null)
            || true !== ($recipient['intake_pending'] ?? null) || true !== ($presentation['commission_closed'] ?? null)
            || false !== ($presentation['governing_intake_decided'] ?? null)
            || true === ($presentation['result_evaluated_for_adoption'] ?? null) || true === ($presentation['result_operationally_adopted'] ?? null)
            || true === ($presentation['planning_amendment_authority'] ?? null) || true === ($presentation['follow_up_commission_authority'] ?? null)
            || true === ($presentation['commission_exercisable'] ?? null) || true === ($presentation['governed_cognition_authority'] ?? null)
            || true === ($presentation['provider_invocation_authority'] ?? null) || true === ($presentation['credential_use_authority'] ?? null)
            || true === ($presentation['operational_use_permitted'] ?? null) || true === ($presentation['tool_use_authority'] ?? null)
            || true === ($presentation['external_action_authority'] ?? null) || true === ($presentation['execution_authority'] ?? null)
            || true === ($presentation['continuing_turn_authority'] ?? null) || true !== ($presentation['sealed'] ?? null)
            || !$this->valid($seneschal) || $seneschalBindingId !== ($seneschal['binding_id'] ?? null)
            || ($presentation['instance_id'] ?? null) !== ($seneschal['instance_id'] ?? null)
            || self::SENESCHAL_SEAT !== ($seneschal['seat'] ?? null) || 'ACTIVE' !== ($seneschal['status'] ?? null)
            || true !== ($seneschal['binding_atomic'] ?? null) || true !== ($seneschal['sealed'] ?? null)) {
            throw new \RuntimeException('CUR455_ADOPTION_INTAKE_CHAIN_INVALID');
        }
    }

    private function assertSoleCurrentSeneschalOccupancy(array $seneschal): void
    {
        foreach (glob($this->occupancy.'/*.json') ?: [] as $path) {
            $other = $this->read($path, 'CUR459_SENESCHAL_OCCUPANCY_CONFLICT');
            if (self::SENESCHAL_SEAT === ($other['seat'] ?? null)
                && ($other['binding_id'] ?? null) !== ($seneschal['binding_id'] ?? null)
                && 'ACTIVE' === ($other['status'] ?? null)) {
                throw new \RuntimeException('CUR459_SENESCHAL_OCCUPANCY_CONFLICT');
            }
        }
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function valid(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function save(string $id, array $record): array
    {
        if (!is_dir($this->dispositions) && !mkdir($this->dispositions, 0770, true) && !is_dir($this->dispositions)) {
            throw new \RuntimeException('CUR456_ADOPTION_INTAKE_DISPOSITION_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->dispositions.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'CUR458_ADOPTION_INTAKE_DISPOSITION_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('CUR458_ADOPTION_INTAKE_DISPOSITION_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('CUR456_ADOPTION_INTAKE_DISPOSITION_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
