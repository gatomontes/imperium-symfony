<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Foundry;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityResolver;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class FoundryGovernanceCognitionAuthorityResolver implements GovernanceCognitionAuthorityResolver
{
    private const STAGES = [
        'persona-specification' => ['subordinate-construction-cases', 'subordinate-persona-specifications', 'case_id', 'foundry.artificer', 'specify-persona'],
        'persona-specification-revision' => ['subordinate-clarification-returns', 'subordinate-persona-specifications', 'revision_basis.return_id', 'foundry.artificer', 'revise-persona-specification'],
        'persona-review' => ['subordinate-persona-candidates', 'subordinate-persona-reviews', 'candidate_id', 'foundry.artificer', 'review-persona'],
        'adversarial-persona-review' => ['adversarial-review-acceptances', 'adversarial-review-results', 'acceptance_id', 'foundry.reviewer.adversarial', 'adversarial-review-persona'],
    ];

    private string $foundry;
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, ?RecordReferenceValidator $validator = null)
    {
        $this->foundry = $root.'/var/imperium/offices/foundry';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function supports(string $cluster, string $authorityType): bool
    {
        return 'foundry' === $cluster && isset(self::STAGES[$authorityType]);
    }

    public function resolve(string $cluster, string $authorityType, string $authorityId): array
    {
        if (!$this->supports($cluster, $authorityType)) {
            throw new \RuntimeException('GCF170_FOUNDRY_GOVERNANCE_AUTHORITY_UNSUPPORTED');
        }
        [$directory, $consumptionDirectory, $consumptionField, $seat, $purpose] = self::STAGES[$authorityType];
        $source = $this->validator->read($this->foundry.'/'.$directory.'/'.$authorityId.'.json', 'GCF171_FOUNDRY_GOVERNANCE_AUTHORITY_ABSENT');
        $inputs = $this->inputs($authorityType, $source);
        $this->validateStage($authorityType, $authorityId, $source, $inputs);

        return [
            'cluster' => 'foundry',
            'authority_type' => $authorityType,
            'authority_id' => $authorityId,
            'instance_id' => $source['instance_id'],
            'case_id' => $inputs['case']['case_id'],
            'case_digest' => $inputs['case']['record_digest'],
            'seat' => $seat,
            'purpose' => $purpose,
            'input_digest' => hash('sha256', CanonicalJson::encode($inputs['ordered'])),
            'source' => ['id' => $authorityId, 'digest' => $source['record_digest']],
            'single_use' => true,
            'exercisable' => true,
            'consumed' => $this->consumed($consumptionDirectory, $consumptionField, $authorityId),
            'expires_at' => '9999-12-31T23:59:59+00:00',
        ];
    }

    private function inputs(string $type, array $source): array
    {
        if ('persona-specification' === $type) {
            return ['case' => $source, 'ordered' => [$source]];
        }
        if ('persona-specification-revision' === $type) {
            $specification = $this->record('subordinate-persona-specifications', $source['persona_specification_id'] ?? null);
            $case = $this->record('subordinate-construction-cases', $source['subordinate_construction_case_id'] ?? null);
            return ['case' => $case, 'ordered' => [$case, $specification, $source]];
        }
        if ('persona-review' === $type) {
            $specification = $this->record('subordinate-persona-specifications', $source['persona_specification_id'] ?? null);
            $case = $this->record('subordinate-construction-cases', $source['subordinate_construction_case_id'] ?? null);
            return ['case' => $case, 'ordered' => [$source, $specification, $case]];
        }
        $candidate = $this->record('subordinate-persona-candidates', $source['candidate_id'] ?? null);
        $specification = $this->record('subordinate-persona-specifications', $source['review_target_lineage']['persona_specification_id'] ?? null);
        $case = $this->record('subordinate-construction-cases', $candidate['subordinate_construction_case_id'] ?? null);
        return ['case' => $case, 'ordered' => [$candidate, $specification, $case, $source]];
    }

    private function validateStage(string $type, string $id, array $source, array $inputs): void
    {
        $valid = $this->validator->isIntact($source) && array_reduce($inputs['ordered'], fn (bool $ok, array $record): bool => $ok && $this->validator->isIntact($record), true);
        $valid = $valid && match ($type) {
            'persona-specification' => $id === ($source['case_id'] ?? null) && 'OPEN_PENDING_PERSONA_SPECIFICATION' === ($source['status'] ?? null) && true === ($source['construction_authority'] ?? null),
            'persona-specification-revision' => $id === ($source['return_id'] ?? null) && 'PENDING_FOUNDRY_SPECIFICATION_REVISION' === ($source['status'] ?? null) && true === ($source['specification_revision_authority'] ?? null),
            'persona-review' => $id === ($source['candidate_id'] ?? null) && 'ASSEMBLED_PENDING_FOUNDRY_REVIEW' === ($source['status'] ?? null) && true === ($source['assembly_complete'] ?? null),
            'adversarial-persona-review' => $id === ($source['acceptance_id'] ?? null) && 'ACCEPTED_FOR_EXACT_ADVERSARIAL_REVIEW' === ($source['disposition'] ?? null) && true === ($source['review_authority_exercisable'] ?? null),
            default => false,
        };
        if (!$valid || ($source['instance_id'] ?? null) !== ($inputs['case']['instance_id'] ?? null)) {
            throw new \RuntimeException('GCF172_FOUNDRY_GOVERNANCE_AUTHORITY_INVALID');
        }
    }

    private function record(string $directory, mixed $id): array
    {
        if (!is_string($id) || '' === $id) {
            throw new \RuntimeException('GCF172_FOUNDRY_GOVERNANCE_AUTHORITY_INVALID');
        }
        return $this->validator->read($this->foundry.'/'.$directory.'/'.$id.'.json', 'GCF172_FOUNDRY_GOVERNANCE_AUTHORITY_INVALID');
    }

    private function consumed(string $directory, string $field, string $id): bool
    {
        foreach (glob($this->foundry.'/'.$directory.'/*.json') ?: [] as $path) {
            try { $record = $this->validator->read($path, 'GCF173_FOUNDRY_GOVERNANCE_CONSUMPTION_INVALID'); } catch (\Throwable) { continue; }
            $value = $record;
            foreach (explode('.', $field) as $part) { $value = is_array($value) ? ($value[$part] ?? null) : null; }
            if ($id === $value) { return true; }
        }
        return false;
    }
}
