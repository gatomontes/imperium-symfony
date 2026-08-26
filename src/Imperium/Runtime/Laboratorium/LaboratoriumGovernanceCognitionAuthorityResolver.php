<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Laboratorium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityResolver;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class LaboratoriumGovernanceCognitionAuthorityResolver implements GovernanceCognitionAuthorityResolver
{
    private string $root;
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, ?RecordReferenceValidator $validator = null)
    { $this->root = $root; $this->validator = $validator ?? new RecordReferenceValidator($root); }

    public function supports(string $cluster, string $authorityType): bool
    { return 'laboratorium' === $cluster && in_array($authorityType, ['profile-elaboration', 'delegate-profile-elaboration'], true); }

    public function resolve(string $cluster, string $type, string $id): array
    {
        if (!$this->supports($cluster, $type)) { throw new \RuntimeException('GCA540_LABORATORIUM_AUTHORITY_UNSUPPORTED'); }
        $delegate = 'delegate-profile-elaboration' === $type;
        $directory = $delegate ? 'var/imperium/offices/laboratorium/delegate-mission-profile-derivation-commission-dispositions' : 'var/imperium/offices/laboratorium/profile-derivation-commission-acceptances';
        $source = $this->validator->read($this->root.'/'.$directory.'/'.$id.'.json', 'GCA541_LABORATORIUM_AUTHORITY_ABSENT');
        $context = $delegate
            ? $this->record('var/imperium/offices/laboratorium/delegate-mission-profile-derivation-commission-inbox', $source['source_commission']['id'] ?? null)
            : $this->record('var/imperium/curia/profile-derivation-authorization-decisions', $source['source_authorization_act']['id'] ?? null);
        if (!$this->valid($delegate, $id, $source, $context)) { throw new \RuntimeException('GCA542_LABORATORIUM_AUTHORITY_INVALID'); }
        return [
            'cluster' => 'laboratorium', 'authority_type' => $type, 'authority_id' => $id,
            'instance_id' => $source['instance_id'], 'case_id' => $id, 'case_digest' => $source['record_digest'],
            'seat' => 'laboratorium.alchemist', 'purpose' => 'elaborate-profile',
            'input_digest' => hash('sha256', CanonicalJson::encode([$source, $context])),
            'source' => ['id' => $id, 'digest' => $source['record_digest']], 'single_use' => true, 'exercisable' => true,
            'consumed' => $this->consumed($delegate, $id), 'expires_at' => '9999-12-31T23:59:59+00:00',
        ];
    }

    private function valid(bool $delegate, string $id, array $source, array $context): bool
    {
        if (!$this->validator->isIntact($source) || !$this->validator->isIntact($context) || true !== ($source['sealed'] ?? null)) { return false; }
        if ($delegate) {
            $authority = $source['profile_derivation_authority'] ?? [];
            return $id === ($source['disposition_id'] ?? null) && 'ACCEPTED' === ($source['disposition'] ?? null)
                && 'DELEGATE_MISSION_PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION' === ($source['status'] ?? null)
                && true === ($source['profile_derivation_authority_exercisable'] ?? null) && true === ($authority['authority_exercisable'] ?? null)
                && false === ($authority['consumed'] ?? null) && ($source['source_commission']['digest'] ?? null) === ($context['record_digest'] ?? null)
                && CanonicalJson::encode($source['persona'] ?? null) === CanonicalJson::encode($context['persona'] ?? null)
                && CanonicalJson::encode($source['profile_scope'] ?? null) === CanonicalJson::encode($context['profile_scope'] ?? null);
        }
        return $id === ($source['acceptance_id'] ?? null)
            && 'PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION' === ($source['status'] ?? null)
            && true === ($source['profile_derivation_authority_exercisable'] ?? null) && true === ($source['profile_candidate_creation_authority'] ?? null)
            && false === ($source['profile_artifact_created'] ?? null) && ($source['source_authorization_act']['digest'] ?? null) === ($context['record_digest'] ?? null)
            && 'AUTHORIZED' === ($context['disposition'] ?? null)
            && CanonicalJson::encode($source['profile_scope'] ?? null) === CanonicalJson::encode($context['profile_scope'] ?? null);
    }

    private function record(string $directory, mixed $id): array
    { if (!is_string($id) || '' === $id) { throw new \RuntimeException('GCA542_LABORATORIUM_AUTHORITY_INVALID'); } return $this->validator->read($this->root.'/'.$directory.'/'.$id.'.json', 'GCA542_LABORATORIUM_AUTHORITY_INVALID'); }

    private function consumed(bool $delegate, string $id): bool
    {
        $directory = $delegate ? 'var/imperium/offices/laboratorium/delegate-mission-profile-candidates' : 'var/imperium/offices/laboratorium/profile-candidates';
        $field = $delegate ? 'source_commission_disposition' : 'source_acceptance';
        foreach (glob($this->root.'/'.$directory.'/*.json') ?: [] as $path) {
            try { $record = $this->validator->read($path, 'GCA543_LABORATORIUM_CONSUMPTION_INVALID'); } catch (\Throwable) { continue; }
            if ($id === ($record[$field]['id'] ?? null)) { return true; }
        }
        return false;
    }
}
