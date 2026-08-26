<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Authorship;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityResolver;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SectionAuthorshipGovernanceCognitionAuthorityResolver implements GovernanceCognitionAuthorityResolver
{
    private const TYPES = [
        'hagiography-section-authorship' => ['hagiography', 'hagiography.sanctographer', 'EVIDENCE_DERIVED_PERSONA_SECTIONS'],
        'studium-section-authorship' => ['studium', 'studium.chancellor', 'PERSONA_GOVERNANCE_DOCTRINE_SECTIONS'],
    ];

    private string $offices;
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, ?RecordReferenceValidator $validator = null)
    {
        $this->offices = $root.'/var/imperium/offices';
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function supports(string $cluster, string $authorityType): bool
    {
        return 'section-authorship' === $cluster && isset(self::TYPES[$authorityType]);
    }

    public function resolve(string $cluster, string $authorityType, string $authorityId): array
    {
        if (!$this->supports($cluster, $authorityType)) { throw new \RuntimeException('GCA520_SECTION_AUTHORSHIP_AUTHORITY_UNSUPPORTED'); }
        [$office, $seat, $class] = self::TYPES[$authorityType];
        $officeRoot = $this->offices.'/'.$office;
        $foundry = $this->offices.'/foundry';
        $acceptance = $this->validator->read($officeRoot.'/subordinate-acceptances/'.$authorityId.'.json', 'GCA521_SECTION_AUTHORSHIP_AUTHORITY_ABSENT');
        $commission = $this->record($officeRoot.'/inbox', $acceptance['commission_id'] ?? null);
        $specification = $this->record($foundry.'/subordinate-persona-specifications', $acceptance['persona_specification_id'] ?? null);
        $case = $this->record($foundry.'/subordinate-construction-cases', $acceptance['subordinate_construction_case_id'] ?? null);
        $inputs = [$acceptance, $commission, $specification, $case];
        if (!$this->valid($office, $seat, $class, $authorityId, $acceptance, $commission, $specification, $case)) { throw new \RuntimeException('GCA522_SECTION_AUTHORSHIP_AUTHORITY_INVALID'); }

        return [
            'cluster' => 'section-authorship', 'authority_type' => $authorityType, 'authority_id' => $authorityId,
            'instance_id' => $acceptance['instance_id'], 'case_id' => $case['case_id'], 'case_digest' => $case['record_digest'],
            'seat' => $seat, 'purpose' => 'author-persona-sections', 'input_digest' => hash('sha256', CanonicalJson::encode($inputs)),
            'source' => ['id' => $authorityId, 'digest' => $acceptance['record_digest']], 'single_use' => true, 'exercisable' => true,
            'consumed' => $this->consumed($officeRoot, $authorityId), 'expires_at' => '9999-12-31T23:59:59+00:00',
        ];
    }

    private function valid(string $office, string $seat, string $class, string $id, array $a, array $c, array $s, array $case): bool
    {
        return $this->validator->isIntact($a) && $this->validator->isIntact($c) && $this->validator->isIntact($s) && $this->validator->isIntact($case)
            && $id === ($a['acceptance_id'] ?? null) && $office === ($a['office'] ?? null)
            && 'ACCEPTED_FOR_EXACT_SUBORDINATE_AUTHORSHIP' === ($a['disposition'] ?? null) && true === ($a['recipient_acceptance'] ?? null)
            && true === ($a['authorship_authority_exercisable'] ?? null) && $class === ($a['authorship_class'] ?? null)
            && $seat === ($a['actor']['seat'] ?? null) && ($a['commission_digest'] ?? null) === ($c['record_digest'] ?? null)
            && $office === ($c['office'] ?? null) && $class === ($c['authorship_class'] ?? null) && true === ($c['authorship_authority'] ?? null)
            && ($a['persona_specification_digest'] ?? null) === ($s['record_digest'] ?? null)
            && ($a['subordinate_construction_case_digest'] ?? null) === ($case['record_digest'] ?? null)
            && ($s['case_id'] ?? null) === ($case['case_id'] ?? null) && ($s['case_digest'] ?? null) === ($case['record_digest'] ?? null)
            && 'SEALED_PENDING_PERSONA_CONSTRUCTION' === ($s['status'] ?? null) && true === ($s['sealed'] ?? null)
            && 'OPEN_PENDING_PERSONA_SPECIFICATION' === ($case['status'] ?? null) && true === ($case['construction_authority'] ?? null)
            && false === ($a['execution_authority'] ?? null) && false === ($c['execution_authority'] ?? null);
    }

    private function record(string $directory, mixed $id): array
    {
        if (!is_string($id) || '' === $id) { throw new \RuntimeException('GCA522_SECTION_AUTHORSHIP_AUTHORITY_INVALID'); }
        return $this->validator->read($directory.'/'.$id.'.json', 'GCA522_SECTION_AUTHORSHIP_AUTHORITY_INVALID');
    }

    private function consumed(string $officeRoot, string $authorityId): bool
    {
        foreach (glob($officeRoot.'/subordinate-products/*-subordinate-product-*.json') ?: [] as $path) {
            try { $record = $this->validator->read($path, 'GCA523_SECTION_AUTHORSHIP_CONSUMPTION_INVALID'); } catch (\Throwable) { continue; }
            if ($authorityId === ($record['acceptance_id'] ?? null)) { return true; }
        }
        return false;
    }
}
