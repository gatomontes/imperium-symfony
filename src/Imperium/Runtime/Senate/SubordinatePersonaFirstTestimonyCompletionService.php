<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinatePersonaFirstTestimonyCompletionService
{
    private string $senate;
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, private PersonaWitnessTestimonyCognitionGateway $cognition, ?RecordReferenceValidator $validator = null, ?ImmutableRecordStore $records = null)
    {
        $this->senate = $root.'/var/imperium/offices/senate';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
        $this->records = $records ?? new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function complete(string $questionRecordId): array
    {
        if (!preg_match('/^senate-persona-question-[a-f0-9]{20}$/', $questionRecordId)) {
            throw new \InvalidArgumentException('S139_PERSONA_QUESTION_ID_INVALID');
        }
        $question = $this->read('persona-questions', $questionRecordId, 'S140_PERSONA_QUESTION_ABSENT');
        $deposition = $this->read('depositions', (string) ($question['deposition_id'] ?? ''), 'S141_PERSONA_TESTIMONY_CHAIN_INVALID');
        $witness = $this->read('persona-witnesses', (string) ($question['manifestation_id'] ?? ''), 'S141_PERSONA_TESTIMONY_CHAIN_INVALID');
        $authority = $question['testimony_authority'] ?? null;
        if (!is_array($authority) || true !== ($authority['authority_single_use'] ?? null) || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null) || 'practice' !== ($question['jurisdiction'] ?? null)
            || 'FIRST_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION' !== ($question['status'] ?? null)
            || ($question['deposition_digest'] ?? null) !== ($deposition['record_digest'] ?? null)
            || ($question['manifestation_digest'] ?? null) !== ($witness['record_digest'] ?? null)
            || null !== ($question['testimony'] ?? null) || true === ($question['testimony_sealed'] ?? null)) {
            throw new \RuntimeException('S141_PERSONA_TESTIMONY_CHAIN_INVALID');
        }
        $answer = $this->cognition->answer($question, $deposition, $witness);
        $this->validateAnswer($answer);
        $id = 'senate-persona-testimony-turn-'.substr(hash('sha256', CanonicalJson::encode([$questionRecordId, $question['record_digest'], $authority['authority_id'], $answer])), 0, 20);
        return $this->records->put('var/imperium/offices/senate/testimony-turns', $id, [
            'schema' => 'imperium.senate-persona-testimony-turn/v2',
            'turn_id' => $id,
            'instance_id' => $question['instance_id'],
            'deposition_id' => $question['deposition_id'],
            'deposition_digest' => $question['deposition_digest'],
            'manifestation_id' => $question['manifestation_id'],
            'manifestation_digest' => $question['manifestation_digest'],
            'candidate_id' => $question['candidate_id'],
            'candidate_digest' => $question['candidate_digest'],
            'originating_guildhall_commission_id' => $question['originating_guildhall_commission_id'],
            'originating_guildhall_commission_digest' => $question['originating_guildhall_commission_digest'],
            'review_target_lineage' => $question['review_target_lineage'],
            'jurisdiction' => 'practice',
            'assignment' => $question['assignment'],
            'question' => $question['question'],
            'testimony' => $answer,
            'source_question_record' => ['id' => $questionRecordId, 'digest' => $question['record_digest']],
            'testimony_authority' => ['id' => $authority['authority_id'], 'consumed' => true, 'continuing_authority' => false],
            'question_dispatched_unchanged' => true,
            'question_authority_consumed' => true,
            'testimony_authority_consumed' => true,
            'testimony_sealed' => true,
            'status' => 'FIRST_TESTIMONY_SEALED_PENDING_REMAINING_TRIALS',
            'senator_finding' => null,
            'senate_disposition' => null,
            'admission_authority' => false,
            'profile_approval_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
    }

    private function read(string $directory, string $id, string $error): array
    {
        $record = $this->validator->read($this->senate.'/'.$directory.'/'.$id.'.json', $error);
        return $this->validator->requireIntact($record, $error);
    }

    private function validateAnswer(array $answer): void
    {
        $keys = array_keys($answer); sort($keys, SORT_STRING);
        if (['answer','evidence_claims','refusals','uncertainties'] !== $keys || !is_string($answer['answer']) || '' === trim($answer['answer'])) throw new \RuntimeException('S142_PERSONA_TESTIMONY_INVALID');
        foreach (['evidence_claims','refusals','uncertainties'] as $field) {
            if (!is_array($answer[$field] ?? null) || !array_is_list($answer[$field])) throw new \RuntimeException('S142_PERSONA_TESTIMONY_INVALID');
            foreach ($answer[$field] as $value) if (!is_string($value) || '' === trim($value)) throw new \RuntimeException('S142_PERSONA_TESTIMONY_INVALID');
        }
    }
}
