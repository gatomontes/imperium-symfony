<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Authorship;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityResolver;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class AuthorshipGovernanceCognitionAuthorityResolver implements GovernanceCognitionAuthorityResolver
{
    private const TYPES = [
        'hagiography-subordinate-requirements' => ['hagiography', 'hagiography.sanctographer', 'Chronicler'],
        'studium-subordinate-requirements' => ['studium', 'studium.chancellor', 'Notary'],
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
        return 'resident-requirements' === $cluster && isset(self::TYPES[$authorityType]);
    }

    public function resolve(string $cluster, string $authorityType, string $authorityId): array
    {
        if (!$this->supports($cluster, $authorityType)) { throw new \RuntimeException('GCA500_AUTHORSHIP_GOVERNANCE_AUTHORITY_UNSUPPORTED'); }
        [$office, $seat, $subordinate] = self::TYPES[$authorityType];
        $root = $this->offices.'/'.$office;
        $acceptance = $this->validator->read($root.'/acceptances/'.$authorityId.'.json', 'GCA501_AUTHORSHIP_GOVERNANCE_AUTHORITY_ABSENT');
        $commission = $this->record($root.'/inbox', $acceptance['commission_id'] ?? null);
        $occupancy = $this->record($root.'/occupancy', $acceptance['binding_id'] ?? null);
        $inputs = [$acceptance, $commission, $occupancy];
        if (!$this->valid($office, $seat, $subordinate, $authorityId, $acceptance, $commission, $occupancy)) { throw new \RuntimeException('GCA502_AUTHORSHIP_GOVERNANCE_AUTHORITY_INVALID'); }

        return [
            'cluster' => 'resident-requirements', 'authority_type' => $authorityType, 'authority_id' => $authorityId,
            'instance_id' => $acceptance['instance_id'], 'case_id' => $acceptance['production_case_id'], 'case_digest' => $acceptance['production_case_digest'],
            'seat' => $seat, 'purpose' => 'resolve-subordinate-requirements', 'input_digest' => hash('sha256', CanonicalJson::encode($inputs)),
            'source' => ['id' => $authorityId, 'digest' => $acceptance['record_digest']], 'single_use' => true, 'exercisable' => true,
            'consumed' => $this->consumed($root, $authorityId), 'expires_at' => '9999-12-31T23:59:59+00:00',
        ];
    }

    private function valid(string $office, string $seat, string $subordinate, string $id, array $acceptance, array $commission, array $occupancy): bool
    {
        return $this->validator->isIntact($acceptance) && $this->validator->isIntact($commission) && $this->validator->isIntact($occupancy)
            && $id === ($acceptance['acceptance_id'] ?? null) && $office === ($acceptance['office'] ?? null)
            && 'ACCEPTED_FOR_RESIDENT_AUTHORSHIP' === ($acceptance['disposition'] ?? null) && true === ($acceptance['recipient_acceptance'] ?? null)
            && true === ($acceptance['authorship_authority_exercisable'] ?? null) && true === ($acceptance['subordinate_staff_resolution_authority'] ?? null)
            && true === ($acceptance['subordinate_staff_resolution_pending'] ?? null) && $subordinate === ($acceptance['subordinate_staff_class'] ?? null)
            && ($acceptance['commission_digest'] ?? null) === ($commission['record_digest'] ?? null) && $seat === ($commission['target_seat'] ?? null)
            && true === ($commission['authorship_authority'] ?? null) && ($acceptance['binding_digest'] ?? null) === ($occupancy['record_digest'] ?? null)
            && 'ACTIVE' === ($occupancy['status'] ?? null) && $seat === ($occupancy['seat'] ?? null)
            && true === ($occupancy['subordinate_staff_resolution_authority'] ?? null) && false === ($acceptance['execution_authority'] ?? null)
            && false === ($commission['execution_authority'] ?? null) && false === ($occupancy['execution_authority'] ?? null);
    }

    private function record(string $directory, mixed $id): array
    {
        if (!is_string($id) || '' === $id) { throw new \RuntimeException('GCA502_AUTHORSHIP_GOVERNANCE_AUTHORITY_INVALID'); }
        return $this->validator->read($directory.'/'.$id.'.json', 'GCA502_AUTHORSHIP_GOVERNANCE_AUTHORITY_INVALID');
    }

    private function consumed(string $root, string $authorityId): bool
    {
        foreach (glob($root.'/subordinate-resolutions/*-subordinate-resolution-*.json') ?: [] as $path) {
            try { $record = $this->validator->read($path, 'GCA503_AUTHORSHIP_GOVERNANCE_CONSUMPTION_INVALID'); } catch (\Throwable) { continue; }
            if ($authorityId === ($record['acceptance_id'] ?? null)) { return true; }
        }
        return false;
    }
}
