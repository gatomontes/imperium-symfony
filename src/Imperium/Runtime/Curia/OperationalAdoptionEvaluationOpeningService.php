<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalAdoptionEvaluationOpeningService
{
    private const SENESCHAL_SEAT = 'curia.seneschal';
    private const REQUIRED_JUDGMENTS = [
        [
            'jurisdiction' => 'EVIDENCE_SUFFICIENCY',
            'question' => 'Does the unchanged result support its claims under the cited evidence, limitations, and uncertainty?',
            'required_officer_class' => 'LEGATE_OR_DELEGATE_AS_SEPARATELY_AUTHORIZED',
            'required_curial_role' => 'CURIALIS',
        ],
        [
            'jurisdiction' => 'MISSION_OPERATIONAL_FIT',
            'question' => 'Is the unchanged result applicable to the exact current mission mandate, constraints, dependencies, and stop conditions?',
            'required_officer_class' => 'LEGATE_OR_DELEGATE_AS_SEPARATELY_AUTHORIZED',
            'required_curial_role' => 'CURIALIS',
        ],
        [
            'jurisdiction' => 'RISK_AUTHORITY_REVERSIBILITY',
            'question' => 'What risks, protected commitments, authority boundaries, reversibility conditions, and residual uncertainty would adoption create?',
            'required_officer_class' => 'LEGATE_OR_DELEGATE_AS_SEPARATELY_AUTHORIZED',
            'required_curial_role' => 'CURIALIS',
        ],
    ];

    private string $intakeDispositions;
    private string $occupancy;
    private string $openings;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->intakeDispositions = $root.'/var/imperium/operational/legate-result-adoption-intake-dispositions';
        $this->occupancy = $root.'/var/imperium/operational/occupancy';
        $this->openings = $root.'/var/imperium/operational/legate-result-adoption-evaluation-openings';
    }

    public function open(string $intakeDispositionId, string $seneschalBindingId, \DateTimeImmutable $openedAt): array
    {
        if (!preg_match('/^legate-result-adoption-intake-disposition-[a-f0-9]{20}$/', $intakeDispositionId)) {
            throw new \InvalidArgumentException('CUR460_ADOPTION_INTAKE_DISPOSITION_ID_INVALID');
        }
        if (!preg_match('/^[a-z0-9][a-z0-9-]{2,127}$/', $seneschalBindingId)) {
            throw new \InvalidArgumentException('CUR461_SENESCHAL_BINDING_ID_INVALID');
        }

        $intake = $this->read($this->intakeDispositions.'/'.$intakeDispositionId.'.json', 'CUR462_ACCEPTED_ADOPTION_INTAKE_ABSENT');
        foreach (glob($this->openings.'/*.json') ?: [] as $path) {
            $prior = $this->read($path, 'CUR467_ADOPTION_EVALUATION_OPENING_CONFLICT');
            if (($prior['source_intake_disposition']['id'] ?? null) === $intakeDispositionId) {
                if (($prior['source_intake_disposition']['digest'] ?? null) !== ($intake['record_digest'] ?? null)
                    || ($prior['presiding_seneschal']['binding_id'] ?? null) !== $seneschalBindingId) {
                    throw new \RuntimeException('CUR467_ADOPTION_EVALUATION_OPENING_CONFLICT');
                }

                return $prior;
            }
        }

        $seneschal = $this->read($this->occupancy.'/'.$seneschalBindingId.'.json', 'CUR463_SENESCHAL_OCCUPANCY_ABSENT');
        $this->validate($intakeDispositionId, $intake, $seneschalBindingId, $seneschal);
        $this->assertSoleCurrentSeneschalOccupancy($seneschal);

        $presidingSeneschal = [
            'seat' => self::SENESCHAL_SEAT,
            'binding_id' => $seneschalBindingId,
            'binding_digest' => $seneschal['record_digest'],
            'manifestation_id' => $seneschal['manifestation_id'],
            'occupancy_generation' => $seneschal['occupancy_generation'],
            'may_preside' => true,
            'may_impersonate_curialis' => false,
        ];
        $openingId = 'legate-result-adoption-evaluation-opening-'.substr(hash('sha256', CanonicalJson::encode([$intakeDispositionId, $intake['record_digest'], $presidingSeneschal, self::REQUIRED_JUDGMENTS])), 0, 20);

        return $this->save($openingId, [
            'schema' => 'imperium.legate-result-adoption-evaluation-opening/v1',
            'opening_id' => $openingId,
            'instance_id' => $intake['instance_id'],
            'case_id' => $intake['case_id'],
            'case_digest' => $intake['case_digest'],
            'source_intake_disposition' => ['id' => $intakeDispositionId, 'digest' => $intake['record_digest']],
            'source_presentation' => $intake['source_presentation'],
            'source_review' => $intake['source_review'],
            'source_delivery' => $intake['source_delivery'],
            'source_cognition_turn' => $intake['source_cognition_turn'],
            'source_commission' => $intake['source_commission'],
            'presenter' => $intake['presenter'],
            'presiding_seneschal' => $presidingSeneschal,
            'legate' => $intake['legate'],
            'contract' => $intake['contract'],
            'result' => $intake['result'],
            'evaluation_contract' => [
                'purpose' => 'EVALUATE_ONE_EXACT_ACCEPTED_LEGATE_RESULT_FOR_POSSIBLE_OPERATIONAL_ADOPTION',
                'required_judgments' => self::REQUIRED_JUDGMENTS,
                'result_must_remain_unchanged' => true,
                'independent_assessments_required' => true,
                'voting_prohibited' => true,
                'aggregation_prohibited' => true,
                'adoption_disposition_prohibited' => true,
                'action_authorization_prohibited' => true,
            ],
            'opened_at' => $openedAt->format(DATE_ATOM),
            'status' => 'LEGATE_RESULT_ADOPTION_EVALUATION_OPENED_PENDING_CURIAL_COMPOSITION_NO_ASSESSMENT_AUTHORITY',
            'commission_closed' => true,
            'governing_intake_accepted' => true,
            'evaluation_opened' => true,
            'curial_composition_resolved' => false,
            'assessment_commissions_issued' => false,
            'assessment_commission_acceptance_authority' => false,
            'assessment_authority' => false,
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

    private function validate(string $intakeDispositionId, array $intake, string $seneschalBindingId, array $seneschal): void
    {
        $decisionMaker = $intake['decision_maker'] ?? [];
        if (!$this->valid($intake) || 'imperium.legate-result-adoption-intake-disposition/v1' !== ($intake['schema'] ?? null)
            || $intakeDispositionId !== ($intake['disposition_id'] ?? null)
            || 'LEGATE_RESULT_ADOPTION_INTAKE_ACCEPTED_PENDING_EVALUATION_OPENING_NO_EVALUATION_AUTHORITY' !== ($intake['status'] ?? null)
            || 'ACCEPTED' !== ($intake['decision'] ?? null) || true !== ($intake['commission_closed'] ?? null)
            || true !== ($intake['governing_intake_decided'] ?? null) || true !== ($intake['governing_intake_accepted'] ?? null)
            || false !== ($intake['governing_intake_refused'] ?? null) || false !== ($intake['adoption_lifecycle_closed'] ?? null)
            || true === ($intake['evaluation_opening_authority'] ?? null) || true === ($intake['result_evaluated_for_adoption'] ?? null)
            || true === ($intake['result_operationally_adopted'] ?? null) || true === ($intake['planning_amendment_authority'] ?? null)
            || true === ($intake['follow_up_commission_authority'] ?? null) || true === ($intake['commission_exercisable'] ?? null)
            || true === ($intake['governed_cognition_authority'] ?? null) || true === ($intake['provider_invocation_authority'] ?? null)
            || true === ($intake['credential_use_authority'] ?? null) || true === ($intake['operational_use_permitted'] ?? null)
            || true === ($intake['tool_use_authority'] ?? null) || true === ($intake['external_action_authority'] ?? null)
            || true === ($intake['execution_authority'] ?? null) || true === ($intake['continuing_turn_authority'] ?? null)
            || true !== ($intake['sealed'] ?? null) || $seneschalBindingId !== ($decisionMaker['binding_id'] ?? null)
            || !$this->valid($seneschal) || $seneschalBindingId !== ($seneschal['binding_id'] ?? null)
            || ($intake['instance_id'] ?? null) !== ($seneschal['instance_id'] ?? null)
            || self::SENESCHAL_SEAT !== ($seneschal['seat'] ?? null)
            || ($decisionMaker['binding_digest'] ?? null) !== ($seneschal['record_digest'] ?? null)
            || ($decisionMaker['manifestation_id'] ?? null) !== ($seneschal['manifestation_id'] ?? null)
            || ($decisionMaker['occupancy_generation'] ?? null) !== ($seneschal['occupancy_generation'] ?? null)
            || 'ACTIVE' !== ($seneschal['status'] ?? null) || true !== ($seneschal['binding_atomic'] ?? null)
            || true !== ($seneschal['sealed'] ?? null)) {
            throw new \RuntimeException('CUR464_ADOPTION_EVALUATION_OPENING_CHAIN_INVALID');
        }
    }

    private function assertSoleCurrentSeneschalOccupancy(array $seneschal): void
    {
        foreach (glob($this->occupancy.'/*.json') ?: [] as $path) {
            $other = $this->read($path, 'CUR468_SENESCHAL_OCCUPANCY_CONFLICT');
            if (self::SENESCHAL_SEAT === ($other['seat'] ?? null)
                && ($other['binding_id'] ?? null) !== ($seneschal['binding_id'] ?? null)
                && 'ACTIVE' === ($other['status'] ?? null)) {
                throw new \RuntimeException('CUR468_SENESCHAL_OCCUPANCY_CONFLICT');
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
        if (!is_dir($this->openings) && !mkdir($this->openings, 0770, true) && !is_dir($this->openings)) {
            throw new \RuntimeException('CUR465_ADOPTION_EVALUATION_OPENING_PERSISTENCE_FAILED');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->openings.'/'.$id.'.json';
        if (is_file($path)) {
            $prior = $this->read($path, 'CUR467_ADOPTION_EVALUATION_OPENING_CONFLICT');
            if (CanonicalJson::encode($prior) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('CUR467_ADOPTION_EVALUATION_OPENING_CONFLICT');
            }

            return $prior;
        }
        if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX)) {
            throw new \RuntimeException('CUR465_ADOPTION_EVALUATION_OPENING_PERSISTENCE_FAILED');
        }

        return $record;
    }
}
