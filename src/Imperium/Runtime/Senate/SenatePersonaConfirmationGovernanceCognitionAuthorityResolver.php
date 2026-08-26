<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityResolver;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SenatePersonaConfirmationGovernanceCognitionAuthorityResolver implements GovernanceCognitionAuthorityResolver
{
    private string $senate;
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, ?RecordReferenceValidator $validator = null)
    {
        $this->senate = $root.'/var/imperium/offices/senate';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function supports(string $cluster, string $authorityType): bool
    {
        return 'senate-persona-confirmation' === $cluster && in_array($authorityType, ['testimony-practice', 'testimony-governance', 'testimony-consistency', 'testimony-security'], true);
    }

    public function resolve(string $cluster, string $authorityType, string $authorityId): array
    {
        if (!$this->supports($cluster, $authorityType)) {
            throw new \RuntimeException('GCA570_SENATE_PERSONA_CONFIRMATION_AUTHORITY_UNSUPPORTED');
        }
        $question = $this->findQuestion($authorityId);
        $deposition = $this->read('depositions', (string) ($question['deposition_id'] ?? ''));
        $witness = $this->read('persona-witnesses', (string) ($question['manifestation_id'] ?? ''));
        $jurisdiction = substr($authorityType, 10);
        $expectedStatus = match ($jurisdiction) {
            'practice' => 'FIRST_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION',
            'governance' => 'GOVERNANCE_BASELINE_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION',
            'consistency' => 'CONSISTENCY_BASELINE_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION',
            'security' => 'SECURITY_BASELINE_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION',
        };
        $context = $deposition;
        if ('practice' !== $jurisdiction) $context['prior_testimony'] = $question['prior_testimony'] ?? null;
        $authority = $question['testimony_authority'] ?? null;
        if (!is_array($authority)
            || $authorityId !== ($authority['authority_id'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || $jurisdiction !== ($question['jurisdiction'] ?? null)
            || ($question['deposition_digest'] ?? null) !== ($deposition['record_digest'] ?? null)
            || ($question['manifestation_digest'] ?? null) !== ($witness['record_digest'] ?? null)
            || ($authority['witness_manifestation_digest'] ?? null) !== ($witness['record_digest'] ?? null)
            || $expectedStatus !== ($question['status'] ?? null)
            || ('governance' === $jurisdiction && (!is_array($question['prior_testimony'] ?? null) || 1 !== count($question['prior_testimony'])))
            || ('consistency' === $jurisdiction && (!is_array($question['prior_testimony'] ?? null) || 2 !== count($question['prior_testimony'])))
            || ('security' === $jurisdiction && (!is_array($question['prior_testimony'] ?? null) || 3 !== count($question['prior_testimony'])))
            || null !== ($question['testimony'] ?? null)
            || true === ($question['testimony_sealed'] ?? null)) {
            throw new \RuntimeException('GCA572_SENATE_PERSONA_CONFIRMATION_AUTHORITY_INVALID');
        }
        return [
            'cluster' => $cluster,
            'authority_type' => $authorityType,
            'authority_id' => $authorityId,
            'instance_id' => $question['instance_id'],
            'case_id' => $question['deposition_id'],
            'case_digest' => $question['deposition_digest'],
            'seat' => 'senate.stand',
            'purpose' => 'answer-persona-question',
            'input_digest' => hash('sha256', CanonicalJson::encode([$question['question'], $context, $witness])),
            'source' => ['id' => $question['question_record_id'], 'digest' => $question['record_digest']],
            'single_use' => true,
            'exercisable' => true,
            'consumed' => $this->consumed((string) $question['question_record_id']),
            'expires_at' => '9999-12-31T23:59:59+00:00',
        ];
    }

    private function findQuestion(string $authorityId): array
    {
        $found = [];
        foreach (glob($this->senate.'/persona-questions/*.json') ?: [] as $path) {
            $record = $this->validator->read($path, 'GCA571_SENATE_PERSONA_CONFIRMATION_AUTHORITY_ABSENT');
            if ($this->validator->isIntact($record) && $authorityId === ($record['testimony_authority']['authority_id'] ?? null)) {
                $found[] = $record;
            }
        }
        if (1 !== count($found)) {
            throw new \RuntimeException('GCA571_SENATE_PERSONA_CONFIRMATION_AUTHORITY_ABSENT');
        }
        return $found[0];
    }

    private function read(string $directory, string $id): array
    {
        $record = $this->validator->read($this->senate.'/'.$directory.'/'.$id.'.json', 'GCA571_SENATE_PERSONA_CONFIRMATION_AUTHORITY_ABSENT');
        return $this->validator->requireIntact($record, 'GCA572_SENATE_PERSONA_CONFIRMATION_AUTHORITY_INVALID');
    }

    private function consumed(string $questionRecordId): bool
    {
        foreach (glob($this->senate.'/testimony-turns/*.json') ?: [] as $path) {
            $record = $this->validator->read($path, 'GCA572_SENATE_PERSONA_CONFIRMATION_AUTHORITY_INVALID');
            if ($this->validator->isIntact($record) && $questionRecordId === ($record['source_question_record']['id'] ?? null)) {
                return true;
            }
        }
        return false;
    }
}
