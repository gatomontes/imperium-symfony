<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Laboratorium;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileCandidateReturnService
{
    private string $candidateDirectory;
    private string $acceptanceDirectory;
    private string $commissionDirectory;
    private string $authorizationDirectory;
    private string $custodyDirectory;
    private string $occupancyDirectory;
    private string $returnDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->candidateDirectory = $projectDir.'/var/imperium/offices/laboratorium/profile-candidates';
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/laboratorium/profile-derivation-commission-acceptances';
        $this->commissionDirectory = $projectDir.'/var/imperium/offices/laboratorium/profile-derivation-commission-inbox';
        $this->authorizationDirectory = $projectDir.'/var/imperium/curia/profile-derivation-authorization-decisions';
        $this->custodyDirectory = $projectDir.'/var/imperium/offices/garrison/custody';
        $this->occupancyDirectory = $projectDir.'/var/imperium/offices/laboratorium/occupancy';
        $this->returnDirectory = $projectDir.'/var/imperium/offices/conscription/profile-candidate-return-inbox';
    }

    public function returnCandidate(string $candidateId): array
    {
        if (!preg_match('/^profile-candidate-[a-f0-9]{20}$/', $candidateId)) throw new \InvalidArgumentException('L43_PROFILE_CANDIDATE_ID_INVALID');
        $candidate = $this->read($this->candidateDirectory.'/'.$candidateId.'.json', 'L44_PROFILE_CANDIDATE_ABSENT');
        $acceptanceId = $candidate['source_acceptance']['id'] ?? null;
        $acceptance = is_string($acceptanceId) ? $this->read($this->acceptanceDirectory.'/'.$acceptanceId.'.json', 'L45_PROFILE_CANDIDATE_ACCEPTANCE_ABSENT') : [];
        $commissionId = $candidate['source_commission']['id'] ?? null;
        $commission = is_string($commissionId) ? $this->read($this->commissionDirectory.'/'.$commissionId.'.json', 'L46_PROFILE_CANDIDATE_COMMISSION_ABSENT') : [];
        $authorizationId = $candidate['source_authorization_act']['id'] ?? null;
        $authorization = is_string($authorizationId) ? $this->read($this->authorizationDirectory.'/'.$authorizationId.'.json', 'L47_PROFILE_CANDIDATE_AUTHORIZATION_ABSENT') : [];
        $custodyId = $candidate['custody_lease']['custody_id'] ?? null;
        $custody = is_string($custodyId) ? $this->read($this->custodyDirectory.'/'.$custodyId.'.json', 'L48_PROFILE_CANDIDATE_CUSTODY_ABSENT') : [];
        $bindingId = $candidate['alchemist']['binding_id'] ?? null;
        $binding = is_string($bindingId) ? $this->read($this->occupancyDirectory.'/'.$bindingId.'.json', 'L49_ALCHEMIST_UNAVAILABLE') : [];
        $this->validate($candidateId, $candidate, $acceptance, $commission, $authorization, $custody, $binding);

        foreach (glob($this->returnDirectory.'/profile-candidate-return-*.json') ?: [] as $path) {
            $prior = $this->read($path, 'L52_PROFILE_CANDIDATE_RETURN_LEDGER_INVALID');
            if (!$this->digestMatches($prior)) throw new \RuntimeException('L52_PROFILE_CANDIDATE_RETURN_LEDGER_INVALID');
            if (($prior['source_profile_candidate']['id'] ?? null) === $candidateId) return $prior;
        }

        $returnId = 'profile-candidate-return-'.substr(hash('sha256', CanonicalJson::encode([$candidateId, $candidate['record_digest'], $candidate['return_destination']])), 0, 20);
        return $this->persist($returnId, [
            'schema' => 'imperium.laboratorium-conscription-profile-candidate-return/v1',
            'return_id' => $returnId,
            'instance_id' => $candidate['instance_id'],
            'proceeding_id' => $candidate['proceeding_id'],
            'sender' => $candidate['alchemist'],
            'recipient' => $candidate['return_destination'],
            'source_profile_candidate' => ['id' => $candidateId, 'digest' => $candidate['record_digest']],
            'source_acceptance' => $candidate['source_acceptance'],
            'source_commission' => $candidate['source_commission'],
            'source_authorization_act' => $candidate['source_authorization_act'],
            'source_reservation_disposition' => $candidate['source_reservation_disposition'],
            'source_plan' => $candidate['source_plan'],
            'profile' => ['profile_id' => $candidate['profile_id'], 'profile_version' => $candidate['profile_version'], 'candidate_id' => $candidateId, 'candidate_digest' => $candidate['record_digest']],
            'persona' => $candidate['persona'],
            'profile_scope' => $candidate['profile_scope'],
            'custody_lease' => $candidate['custody_lease'],
            'delivery_scope' => 'RETURN_ONE_EXACT_SEALED_PROFILE_CANDIDATE',
            'status' => 'PROFILE_CANDIDATE_RETURNED_PENDING_CONSCRIPTION_ACCEPTANCE',
            'profile_candidate_return_authority_consumed' => true,
            'profile_candidate_returned' => true,
            'recipient_acceptance' => false,
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

    private function validate(string $candidateId, array $candidate, array $acceptance, array $commission, array $authorization, array $custody, array $binding): void
    {
        if (!$this->digestMatches($candidate) || !$this->digestMatches($acceptance) || !$this->digestMatches($commission) || !$this->digestMatches($authorization) || !$this->digestMatches($custody) || !$this->digestMatches($binding)
            || 'imperium.laboratorium-profile-candidate/v1' !== ($candidate['schema'] ?? null) || $candidateId !== ($candidate['candidate_id'] ?? null)
            || 'PROFILE_CANDIDATE_DERIVED_VERSIONED_SEALED_PENDING_RETURN_TO_CONSCRIPTION' !== ($candidate['status'] ?? null)
            || true !== ($candidate['profile_elaboration_complete'] ?? null) || true !== ($candidate['profile_candidate_created'] ?? null)
            || false !== ($candidate['profile_candidate_returned'] ?? null) || true !== ($candidate['profile_candidate_return_authority'] ?? null)
            || 1 !== ($candidate['profile_version'] ?? null) || null !== ($candidate['supersedes'] ?? null) || true !== ($candidate['sealed'] ?? null)
            || true === ($candidate['profile_approval_authority'] ?? null) || true === ($candidate['profile_installation_authority'] ?? null)
            || true === ($candidate['examination_assembly_authority'] ?? null) || true === ($candidate['senate_examination_authority'] ?? null)
            || true === ($candidate['custody_release_authority'] ?? null) || true === ($candidate['persona_substitution_authority'] ?? null) || true === ($candidate['execution_authority'] ?? null)
            || !$this->elaborationValid($candidate['profile']['elaboration'] ?? null)
            || CanonicalJson::encode($candidate['persona'] ?? null) !== CanonicalJson::encode($candidate['profile']['persona'] ?? null)
            || ($candidate['profile_scope']['target_kind'] ?? null) !== ($candidate['profile']['target_kind'] ?? null)
            || ($candidate['profile_scope']['capability_slot_id'] ?? null) !== ($candidate['profile']['capability_slot_id'] ?? null)
            || ($candidate['profile_scope']['profession'] ?? null) !== ($candidate['profile']['profession'] ?? null)
            || ($candidate['profile_scope']['objective'] ?? null) !== ($candidate['profile']['mission']['objective'] ?? null)
            || CanonicalJson::encode($candidate['profile_scope']['scope'] ?? null) !== CanonicalJson::encode($candidate['profile']['mission']['scope'] ?? null)
            || CanonicalJson::encode($candidate['profile_scope']['capability_requirements'] ?? null) !== CanonicalJson::encode($candidate['profile']['mission']['capability_requirements'] ?? null)
            || CanonicalJson::encode($candidate['profile_scope']['tool_requirements'] ?? null) !== CanonicalJson::encode($candidate['profile']['mission']['tool_requirements'] ?? null)
            || CanonicalJson::encode($candidate['profile_scope']['data_requirements'] ?? null) !== CanonicalJson::encode($candidate['profile']['mission']['data_requirements'] ?? null)
            || CanonicalJson::encode($candidate['profile_scope']['constraints'] ?? null) !== CanonicalJson::encode($candidate['profile']['limitations']['constraints'] ?? null)
            || CanonicalJson::encode($candidate['profile_scope']['stop_conditions'] ?? null) !== CanonicalJson::encode($candidate['profile']['limitations']['stop_conditions'] ?? null)
            || 'imperium.laboratorium-profile-derivation-commission-acceptance/v1' !== ($acceptance['schema'] ?? null)
            || ($candidate['source_acceptance']['digest'] ?? null) !== ($acceptance['record_digest'] ?? null)
            || 'PROFILE_DERIVATION_COMMISSION_ACCEPTED_PENDING_PROFILE_DERIVATION' !== ($acceptance['status'] ?? null)
            || CanonicalJson::encode($candidate['source_commission'] ?? null) !== CanonicalJson::encode($acceptance['source_commission'] ?? null)
            || CanonicalJson::encode($candidate['persona'] ?? null) !== CanonicalJson::encode($acceptance['persona'] ?? null)
            || CanonicalJson::encode($candidate['profile_scope'] ?? null) !== CanonicalJson::encode($acceptance['profile_scope'] ?? null)
            || CanonicalJson::encode($candidate['custody_lease'] ?? null) !== CanonicalJson::encode($acceptance['custody_lease'] ?? null)
            || 'imperium.conscription-laboratorium-profile-derivation-commission/v1' !== ($commission['schema'] ?? null)
            || ($candidate['source_commission']['digest'] ?? null) !== ($commission['record_digest'] ?? null)
            || 'DERIVE_ONE_EXACT_MISSION_PROFILE' !== ($commission['commission_scope'] ?? null)
            || CanonicalJson::encode($candidate['persona'] ?? null) !== CanonicalJson::encode($commission['persona'] ?? null)
            || CanonicalJson::encode($candidate['profile_scope'] ?? null) !== CanonicalJson::encode($commission['profile_scope'] ?? null)
            || CanonicalJson::encode($candidate['return_destination'] ?? null) !== CanonicalJson::encode($commission['return_destination'] ?? null)
            || 'conscription.recruiter' !== ($candidate['return_destination']['seat'] ?? null)
            || 'imperium.imperator-profile-derivation-decision/v1' !== ($authorization['schema'] ?? null)
            || ($candidate['source_authorization_act']['digest'] ?? null) !== ($authorization['record_digest'] ?? null) || 'AUTHORIZED' !== ($authorization['disposition'] ?? null)
            || CanonicalJson::encode($candidate['profile_scope'] ?? null) !== CanonicalJson::encode($authorization['profile_scope'] ?? null)
            || ($candidate['profile']['limitations']['imperator_authorization_limitations'] ?? null) !== ($authorization['limitations'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($candidate['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || ($candidate['persona']['persona_id'] ?? null) !== ($custody['persona_id'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || 'garrison' !== ($candidate['custody_lease']['custodian'] ?? null) || ($candidate['instance_id'] ?? null) !== ($custody['instance_id'] ?? null)
            || ($candidate['alchemist']['binding_id'] ?? null) !== ($binding['binding_id'] ?? null) || ($candidate['alchemist']['binding_digest'] ?? null) !== ($binding['record_digest'] ?? null)
            || 'laboratorium.alchemist' !== ($binding['seat'] ?? null) || 'ACTIVE' !== ($binding['status'] ?? null) || true !== ($binding['binding_atomic'] ?? null)
            || ($candidate['instance_id'] ?? null) !== ($binding['instance_id'] ?? null) || true === ($binding['execution_authority'] ?? null)
        ) throw new \RuntimeException('L50_PROFILE_CANDIDATE_RETURN_CHAIN_INVALID');
    }

    private function elaborationValid(mixed $elaboration): bool
    {
        if (!is_array($elaboration)) return false;
        $expected = ['disposition', 'operating_posture', 'responsibilities', 'non_responsibilities', 'reasoning_priorities', 'evidence_discipline', 'tool_use_directives', 'input_handling', 'output_contract', 'escalation_conditions', 'uncertainty_behavior', 'failure_behavior', 'persona_adaptations'];
        $keys = array_keys($elaboration); sort($keys, SORT_STRING); sort($expected, SORT_STRING);
        if ($expected !== $keys || 'PROFILE_ELABORATION_COMPLETE' !== ($elaboration['disposition'] ?? null) || !is_string($elaboration['operating_posture'] ?? null) || '' === trim($elaboration['operating_posture'])) return false;
        foreach (array_diff($expected, ['disposition', 'operating_posture']) as $field) {
            if (!is_array($elaboration[$field]) || [] === $elaboration[$field]) return false;
            foreach ($elaboration[$field] as $item) if (!is_string($item) || '' === trim($item)) return false;
        }
        return true;
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
        if (!is_dir($this->returnDirectory) && !mkdir($this->returnDirectory, 0770, true) && !is_dir($this->returnDirectory)) throw new \RuntimeException('L51_PROFILE_CANDIDATE_RETURN_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->returnDirectory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'L53_PROFILE_CANDIDATE_RETURN_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('L53_PROFILE_CANDIDATE_RETURN_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('L51_PROFILE_CANDIDATE_RETURN_FAILED'); }
        return $record;
    }
}
