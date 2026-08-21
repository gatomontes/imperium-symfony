<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Laboratorium;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileCandidateDerivationService
{
    private string $acceptanceDirectory;
    private string $commissionDirectory;
    private string $dispositionDirectory;
    private string $authorizationDirectory;
    private string $custodyDirectory;
    private string $occupancyDirectory;
    private string $candidateDirectory;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        private ProfileElaborationCognitionGateway $cognition,
    )
    {
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/laboratorium/profile-derivation-commission-acceptances';
        $this->commissionDirectory = $projectDir.'/var/imperium/offices/laboratorium/profile-derivation-commission-inbox';
        $this->dispositionDirectory = $projectDir.'/var/imperium/offices/garrison/profile-derivation-handoff-dispositions';
        $this->authorizationDirectory = $projectDir.'/var/imperium/curia/profile-derivation-authorization-decisions';
        $this->custodyDirectory = $projectDir.'/var/imperium/offices/garrison/custody';
        $this->occupancyDirectory = $projectDir.'/var/imperium/offices/laboratorium/occupancy';
        $this->candidateDirectory = $projectDir.'/var/imperium/offices/laboratorium/profile-candidates';
    }

    public function derive(string $acceptanceId): array
    {
        if (!preg_match('/^profile-derivation-commission-acceptance-[a-f0-9]{20}$/', $acceptanceId)) {
            throw new \InvalidArgumentException('L29_PROFILE_DERIVATION_ACCEPTANCE_ID_INVALID');
        }

        $acceptance = $this->read($this->acceptanceDirectory.'/'.$acceptanceId.'.json', 'L30_PROFILE_DERIVATION_ACCEPTANCE_ABSENT');
        $commissionId = $acceptance['source_commission']['id'] ?? null;
        $commission = is_string($commissionId) ? $this->read($this->commissionDirectory.'/'.$commissionId.'.json', 'L31_PROFILE_DERIVATION_COMMISSION_ABSENT') : [];
        $dispositionId = $acceptance['source_handoff_disposition']['id'] ?? null;
        $disposition = is_string($dispositionId) ? $this->read($this->dispositionDirectory.'/'.$dispositionId.'.json', 'L32_PROFILE_DERIVATION_HANDOFF_DISPOSITION_ABSENT') : [];
        $authorizationId = $acceptance['source_authorization_act']['id'] ?? null;
        $authorization = is_string($authorizationId) ? $this->read($this->authorizationDirectory.'/'.$authorizationId.'.json', 'L39_PROFILE_DERIVATION_AUTHORIZATION_ABSENT') : [];
        $custodyId = $acceptance['custody_lease']['custody_id'] ?? null;
        $custody = is_string($custodyId) ? $this->read($this->custodyDirectory.'/'.$custodyId.'.json', 'L33_PROFILE_DERIVATION_CUSTODY_ABSENT') : [];
        $bindingId = $acceptance['alchemist']['binding_id'] ?? null;
        $binding = is_string($bindingId) ? $this->read($this->occupancyDirectory.'/'.$bindingId.'.json', 'L34_ALCHEMIST_UNAVAILABLE') : [];
        $this->validateChain($acceptanceId, $acceptance, $commission, $disposition, $authorization, $custody, $binding);

        foreach (glob($this->candidateDirectory.'/profile-candidate-*.json') ?: [] as $path) {
            $prior = $this->read($path, 'L37_PROFILE_CANDIDATE_LEDGER_INVALID');
            if (!$this->digestMatches($prior)) throw new \RuntimeException('L37_PROFILE_CANDIDATE_LEDGER_INVALID');
            if (($prior['source_acceptance']['id'] ?? null) === $acceptanceId) return $prior;
        }

        $elaboration = $this->cognition->elaborate($acceptance, $authorization);
        $this->validateElaboration($elaboration);
        $profile = [
            'target_kind' => $acceptance['profile_scope']['target_kind'],
            'persona' => $acceptance['persona'],
            'capability_slot_id' => $acceptance['profile_scope']['capability_slot_id'],
            'profession' => $acceptance['profile_scope']['profession'],
            'mission' => [
                'objective' => $acceptance['profile_scope']['objective'],
                'scope' => $acceptance['profile_scope']['scope'],
                'capability_requirements' => $acceptance['profile_scope']['capability_requirements'],
                'tool_requirements' => $acceptance['profile_scope']['tool_requirements'],
                'data_requirements' => $acceptance['profile_scope']['data_requirements'],
            ],
            'limitations' => [
                'constraints' => $acceptance['profile_scope']['constraints'],
                'stop_conditions' => $acceptance['profile_scope']['stop_conditions'],
                'imperator_authorization_limitations' => $authorization['limitations'],
                'custody_bound' => true,
                'persona_substitution_permitted' => false,
                'mission_scope_mutation_permitted' => false,
            ],
            'governance' => [
                'profile_steward' => $acceptance['profile_scope']['profile_steward'],
                'commissioner_and_prospective_installer' => $acceptance['profile_scope']['prospective_commissioner_and_installer'],
                'transformer' => $acceptance['profile_scope']['prospective_transformer'],
                'prospective_examiner' => $acceptance['profile_scope']['prospective_examiner'],
                'prospective_approver' => $acceptance['profile_scope']['prospective_approver'],
            ],
            'elaboration' => $elaboration,
        ];
        $profileId = 'profile-'.substr(hash('sha256', CanonicalJson::encode([$acceptanceId, $acceptance['record_digest'], 1, $profile])), 0, 20);
        $candidateId = 'profile-candidate-'.substr(hash('sha256', CanonicalJson::encode([$profileId, 1, $profile, $acceptance['record_digest']])), 0, 20);

        return $this->persist($candidateId, [
            'schema' => 'imperium.laboratorium-profile-candidate/v1',
            'candidate_id' => $candidateId,
            'profile_id' => $profileId,
            'profile_version' => 1,
            'supersedes' => null,
            'instance_id' => $acceptance['instance_id'],
            'proceeding_id' => $acceptance['proceeding_id'],
            'alchemist' => $acceptance['alchemist'],
            'source_acceptance' => ['id' => $acceptanceId, 'digest' => $acceptance['record_digest']],
            'source_commission' => $acceptance['source_commission'],
            'source_handoff_disposition' => $acceptance['source_handoff_disposition'],
            'source_authorization_act' => $acceptance['source_authorization_act'],
            'source_reservation_disposition' => $acceptance['source_reservation_disposition'],
            'source_plan' => $acceptance['source_plan'],
            'persona' => $acceptance['persona'],
            'profile_scope' => $acceptance['profile_scope'],
            'custody_lease' => $acceptance['custody_lease'],
            'return_destination' => $acceptance['return_destination'],
            'profile' => $profile,
            'profile_elaboration_complete' => true,
            'status' => 'PROFILE_CANDIDATE_DERIVED_VERSIONED_SEALED_PENDING_RETURN_TO_CONSCRIPTION',
            'profile_candidate_created' => true,
            'profile_candidate_returned' => false,
            'profile_approval_authority' => false,
            'profile_installation_authority' => false,
            'examination_assembly_authority' => false,
            'senate_examination_authority' => false,
            'custody_release_authority' => false,
            'persona_substitution_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'deployment_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ]);
    }

    private function validateChain(string $acceptanceId, array $acceptance, array $commission, array $disposition, array $authorization, array $custody, array $binding): void
    {
        if (!$this->digestMatches($acceptance) || !$this->digestMatches($commission) || !$this->digestMatches($disposition) || !$this->digestMatches($authorization) || !$this->digestMatches($custody) || !$this->digestMatches($binding)
            || 'imperium.laboratorium-profile-derivation-commission-acceptance/v1' !== ($acceptance['schema'] ?? null) || $acceptanceId !== ($acceptance['acceptance_id'] ?? null)
            || 'PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION' !== ($acceptance['status'] ?? null) || true !== ($acceptance['recipient_acceptance'] ?? null)
            || true !== ($acceptance['profile_derivation_authority_exercisable'] ?? null) || true !== ($acceptance['profile_candidate_creation_authority'] ?? null) || false !== ($acceptance['profile_artifact_created'] ?? null)
            || true === ($acceptance['profile_approval_authority'] ?? null) || true === ($acceptance['profile_installation_authority'] ?? null) || true === ($acceptance['custody_release_authority'] ?? null)
            || true === ($acceptance['persona_substitution_authority'] ?? null) || true === ($acceptance['execution_authority'] ?? null) || true !== ($acceptance['sealed'] ?? null)
            || 'imperium.conscription-laboratorium-profile-derivation-commission/v1' !== ($commission['schema'] ?? null)
            || ($acceptance['source_commission']['digest'] ?? null) !== ($commission['record_digest'] ?? null)
            || 'DERIVE_ONE_EXACT_MISSION_PROFILE' !== ($commission['commission_scope'] ?? null)
            || CanonicalJson::encode($acceptance['persona'] ?? null) !== CanonicalJson::encode($commission['persona'] ?? null)
            || CanonicalJson::encode($acceptance['profile_scope'] ?? null) !== CanonicalJson::encode($commission['profile_scope'] ?? null)
            || CanonicalJson::encode($acceptance['custody_lease'] ?? null) !== CanonicalJson::encode($commission['custody_lease'] ?? null)
            || CanonicalJson::encode($acceptance['return_destination'] ?? null) !== CanonicalJson::encode($commission['return_destination'] ?? null)
            || 'imperium.garrison-profile-derivation-handoff-disposition/v1' !== ($disposition['schema'] ?? null)
            || ($acceptance['source_handoff_disposition']['digest'] ?? null) !== ($disposition['record_digest'] ?? null) || 'APPROVED' !== ($disposition['disposition'] ?? null)
            || 'imperium.imperator-profile-derivation-decision/v1' !== ($authorization['schema'] ?? null)
            || ($acceptance['source_authorization_act']['digest'] ?? null) !== ($authorization['record_digest'] ?? null) || 'AUTHORIZED' !== ($authorization['disposition'] ?? null)
            || !is_string($authorization['limitations'] ?? null) || '' === trim($authorization['limitations'])
            || CanonicalJson::encode($acceptance['profile_scope'] ?? null) !== CanonicalJson::encode($authorization['profile_scope'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null) || ($acceptance['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || ($acceptance['persona']['persona_id'] ?? null) !== ($custody['persona_id'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || ($acceptance['instance_id'] ?? null) !== ($custody['instance_id'] ?? null) || 'garrison' !== ($acceptance['custody_lease']['custodian'] ?? null)
            || !in_array($binding['schema'] ?? null, ['imperium.laboratorium-alchemist-occupancy/v1', 'imperium.operator-root-seat-occupancy/v1'], true)
            || ($acceptance['alchemist']['binding_id'] ?? null) !== ($binding['binding_id'] ?? null) || ($acceptance['alchemist']['binding_digest'] ?? null) !== ($binding['record_digest'] ?? null)
            || 'laboratorium.alchemist' !== ($binding['seat'] ?? null) || 'ACTIVE' !== ($binding['status'] ?? null) || true !== ($binding['binding_atomic'] ?? null)
            || true !== ($binding['profile_derivation_commission_acceptance_authority'] ?? null) || true === ($binding['execution_authority'] ?? null)
        ) throw new \RuntimeException('L35_PROFILE_DERIVATION_CHAIN_INVALID');
    }

    private function validateElaboration(array $elaboration): void
    {
        $expected = [
            'disposition', 'operating_posture', 'responsibilities', 'non_responsibilities',
            'reasoning_priorities', 'evidence_discipline', 'tool_use_directives',
            'input_handling', 'output_contract', 'escalation_conditions',
            'uncertainty_behavior', 'failure_behavior', 'persona_adaptations',
        ];
        $keys = array_keys($elaboration); sort($keys, SORT_STRING); sort($expected, SORT_STRING);
        if ($expected !== $keys || 'PROFILE_ELABORATION_COMPLETE' !== ($elaboration['disposition'] ?? null)
            || !is_string($elaboration['operating_posture'] ?? null) || '' === trim($elaboration['operating_posture'])) {
            throw new \RuntimeException('L41_PROFILE_ELABORATION_CONTRACT_INVALID');
        }
        foreach (array_diff($expected, ['disposition', 'operating_posture']) as $field) {
            if (!is_array($elaboration[$field]) || [] === $elaboration[$field]) throw new \RuntimeException('L41_PROFILE_ELABORATION_CONTRACT_INVALID');
            foreach ($elaboration[$field] as $item) if (!is_string($item) || '' === trim($item)) throw new \RuntimeException('L41_PROFILE_ELABORATION_CONTRACT_INVALID');
        }
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) throw new \RuntimeException($error);
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record['record_digest'] ?? null; unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function persist(string $id, array $record): array
    {
        if (!is_dir($this->candidateDirectory) && !mkdir($this->candidateDirectory, 0770, true) && !is_dir($this->candidateDirectory)) throw new \RuntimeException('L36_PROFILE_CANDIDATE_PERSISTENCE_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->candidateDirectory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'L38_PROFILE_CANDIDATE_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('L38_PROFILE_CANDIDATE_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('L36_PROFILE_CANDIDATE_PERSISTENCE_FAILED'); }
        return $record;
    }
}
