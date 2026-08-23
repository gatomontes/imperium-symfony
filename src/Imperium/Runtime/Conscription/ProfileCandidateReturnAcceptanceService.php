<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileCandidateReturnAcceptanceService
{
    private string $returnDirectory;
    private string $candidateDirectory;
    private string $custodyDirectory;
    private string $acceptanceDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir, private StateStore $bootstrap)
    {
        $this->returnDirectory = $projectDir.'/var/imperium/offices/conscription/profile-candidate-return-inbox';
        $this->candidateDirectory = $projectDir.'/var/imperium/offices/laboratorium/profile-candidates';
        $this->custodyDirectory = $projectDir.'/var/imperium/offices/garrison/custody';
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/conscription/profile-candidate-return-acceptances';
    }

    public function accept(string $returnId): array
    {
        if (!preg_match('/^profile-candidate-return-[a-f0-9]{20}$/', $returnId)) throw new \InvalidArgumentException('R83_PROFILE_CANDIDATE_RETURN_ID_INVALID');
        $return = $this->read($this->returnDirectory.'/'.$returnId.'.json', 'R84_PROFILE_CANDIDATE_RETURN_ABSENT');
        $candidateId = $return['source_profile_candidate']['id'] ?? null;
        $candidate = is_string($candidateId) ? $this->read($this->candidateDirectory.'/'.$candidateId.'.json', 'R85_PROFILE_CANDIDATE_ABSENT') : [];
        $custodyId = $return['custody_lease']['custody_id'] ?? null;
        $custody = is_string($custodyId) ? $this->read($this->custodyDirectory.'/'.$custodyId.'.json', 'R86_PROFILE_CANDIDATE_CUSTODY_ABSENT') : [];
        [$instanceId, $recruiter] = $this->ordinaryRecruiter();
        $this->validate($returnId, $return, $candidate, $custody, $instanceId);

        $actor = ['seat' => 'conscription.recruiter', 'manifestation_id' => $recruiter['manifestation_id'], 'occupancy_generation' => $recruiter['occupancy_generation']];
        $acceptanceId = 'profile-candidate-return-acceptance-'.substr(hash('sha256', CanonicalJson::encode([$returnId, $return['record_digest'], $actor])), 0, 20);
        return $this->persist($acceptanceId, [
            'schema' => 'imperium.conscription-profile-candidate-return-acceptance/v1',
            'acceptance_id' => $acceptanceId,
            'instance_id' => $instanceId,
            'proceeding_id' => $return['proceeding_id'],
            'actor' => $actor,
            'source_return' => ['id' => $returnId, 'digest' => $return['record_digest']],
            'source_profile_candidate' => $return['source_profile_candidate'],
            'source_acceptance' => $return['source_acceptance'],
            'source_commission' => $return['source_commission'],
            'source_authorization_act' => $return['source_authorization_act'],
            'source_reservation_disposition' => $return['source_reservation_disposition'],
            'source_plan' => $return['source_plan'],
            'profile' => $return['profile'],
            'persona' => $return['persona'],
            'profile_scope' => $return['profile_scope'],
            'custody_lease' => $return['custody_lease'],
            'disposition' => 'ACCEPTED_EXACT_RETURNED_PROFILE_CANDIDATE',
            'status' => 'PROFILE_CANDIDATE_ACCEPTED_PENDING_EXAMINATION_ASSEMBLY_AUTHORIZATION',
            'recipient_acceptance' => true,
            'profile_candidate_acceptance_authority_consumed' => true,
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

    private function validate(string $returnId, array $return, array $candidate, array $custody, string $instanceId): void
    {
        if (!$this->digestMatches($return) || !$this->digestMatches($candidate) || !$this->digestMatches($custody)
            || 'imperium.laboratorium-conscription-profile-candidate-return/v1' !== ($return['schema'] ?? null) || $returnId !== ($return['return_id'] ?? null)
            || $instanceId !== ($return['instance_id'] ?? null) || 'conscription.recruiter' !== ($return['recipient']['seat'] ?? null)
            || 'RETURN_ONE_EXACT_SEALED_PROFILE_CANDIDATE' !== ($return['delivery_scope'] ?? null)
            || 'PROFILE_CANDIDATE_RETURNED_PENDING_CONSCRIPTION_ACCEPTANCE' !== ($return['status'] ?? null)
            || true !== ($return['profile_candidate_return_authority_consumed'] ?? null) || true !== ($return['profile_candidate_returned'] ?? null)
            || false !== ($return['recipient_acceptance'] ?? null) || true !== ($return['sealed'] ?? null)
            || $this->hasDownstreamAuthority($return)
            || 'imperium.laboratorium-profile-candidate/v1' !== ($candidate['schema'] ?? null)
            || ($return['source_profile_candidate']['id'] ?? null) !== ($candidate['candidate_id'] ?? null)
            || ($return['source_profile_candidate']['digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || 'PROFILE_CANDIDATE_DERIVED_VERSIONED_SEALED_PENDING_RETURN_TO_CONSCRIPTION' !== ($candidate['status'] ?? null)
            || true !== ($candidate['profile_elaboration_complete'] ?? null) || true !== ($candidate['profile_candidate_created'] ?? null)
            || false !== ($candidate['profile_candidate_returned'] ?? null) || true !== ($candidate['profile_candidate_return_authority'] ?? null)
            || true !== ($candidate['sealed'] ?? null) || $this->hasDownstreamAuthority($candidate)
            || CanonicalJson::encode($return['source_acceptance'] ?? null) !== CanonicalJson::encode($candidate['source_acceptance'] ?? null)
            || CanonicalJson::encode($return['source_commission'] ?? null) !== CanonicalJson::encode($candidate['source_commission'] ?? null)
            || CanonicalJson::encode($return['source_authorization_act'] ?? null) !== CanonicalJson::encode($candidate['source_authorization_act'] ?? null)
            || CanonicalJson::encode($return['source_reservation_disposition'] ?? null) !== CanonicalJson::encode($candidate['source_reservation_disposition'] ?? null)
            || CanonicalJson::encode($return['source_plan'] ?? null) !== CanonicalJson::encode($candidate['source_plan'] ?? null)
            || CanonicalJson::encode($return['persona'] ?? null) !== CanonicalJson::encode($candidate['persona'] ?? null)
            || CanonicalJson::encode($return['profile_scope'] ?? null) !== CanonicalJson::encode($candidate['profile_scope'] ?? null)
            || CanonicalJson::encode($return['custody_lease'] ?? null) !== CanonicalJson::encode($candidate['custody_lease'] ?? null)
            || ($return['profile']['profile_id'] ?? null) !== ($candidate['profile_id'] ?? null)
            || ($return['profile']['profile_version'] ?? null) !== ($candidate['profile_version'] ?? null)
            || ($return['profile']['candidate_digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($return['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || ($return['persona']['persona_id'] ?? null) !== ($custody['persona_id'] ?? null)
            || $instanceId !== ($custody['instance_id'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || 'garrison' !== ($return['custody_lease']['custodian'] ?? null)
        ) throw new \RuntimeException('R87_PROFILE_CANDIDATE_RETURN_CHAIN_INVALID');
    }

    private function hasDownstreamAuthority(array $record): bool
    {
        foreach (['profile_approval_authority', 'profile_installation_authority', 'examination_assembly_authority', 'senate_examination_authority', 'custody_release_authority', 'persona_substitution_authority', 'spawning_authority', 'seat_binding_authority', 'deployment_authority', 'execution_authority'] as $field) {
            if (true === ($record[$field] ?? null)) return true;
        }
        return false;
    }

    private function ordinaryRecruiter(): array
    {
        $state = $this->bootstrap->read();
        if (!is_array($state) || BootstrapState::CuriaReady->value !== ($state['state'] ?? null)) throw new \RuntimeException('R88_RECRUITER_UNAVAILABLE');
        for ($index = count($state['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $state['events'][$index];
            $recruiter = 'T04' === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null) ? ($event['output']['successor'] ?? null) : null;
            if (is_array($recruiter) && 'conscription.recruiter' === ($recruiter['seat'] ?? null) && 'ordinary-recruiter' === ($recruiter['authority'] ?? null) && 2 === ($recruiter['occupancy_generation'] ?? null) && is_string($recruiter['manifestation_id'] ?? null)) return [(string) ($state['binding']['instance_id'] ?? ''), $recruiter];
        }
        throw new \RuntimeException('R88_RECRUITER_UNAVAILABLE');
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
        if (!is_dir($this->acceptanceDirectory) && !mkdir($this->acceptanceDirectory, 0770, true) && !is_dir($this->acceptanceDirectory)) throw new \RuntimeException('R89_PROFILE_CANDIDATE_ACCEPTANCE_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->acceptanceDirectory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'R90_PROFILE_CANDIDATE_ACCEPTANCE_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('R90_PROFILE_CANDIDATE_ACCEPTANCE_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('R89_PROFILE_CANDIDATE_ACCEPTANCE_FAILED'); }
        return $record;
    }
}
