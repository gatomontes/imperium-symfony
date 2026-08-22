<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ExaminationAssemblyAuthorizationRequestService
{
    private string $acceptanceDirectory;
    private string $returnDirectory;
    private string $candidateDirectory;
    private string $custodyDirectory;
    private string $requestDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir, private StateStore $bootstrap)
    {
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/conscription/profile-candidate-return-acceptances';
        $this->returnDirectory = $projectDir.'/var/imperium/offices/conscription/profile-candidate-return-inbox';
        $this->candidateDirectory = $projectDir.'/var/imperium/offices/laboratorium/profile-candidates';
        $this->custodyDirectory = $projectDir.'/var/imperium/offices/garrison/custody';
        $this->requestDirectory = $projectDir.'/var/imperium/offices/senate/examination-assembly-authorization-inbox';
    }

    public function request(string $acceptanceId): array
    {
        if (!preg_match('/^profile-candidate-return-acceptance-[a-f0-9]{20}$/', $acceptanceId)) throw new \InvalidArgumentException('R91_PROFILE_CANDIDATE_ACCEPTANCE_ID_INVALID');
        $acceptance = $this->read($this->acceptanceDirectory.'/'.$acceptanceId.'.json', 'R92_PROFILE_CANDIDATE_ACCEPTANCE_ABSENT');
        $returnId = $acceptance['source_return']['id'] ?? null;
        $return = is_string($returnId) ? $this->read($this->returnDirectory.'/'.$returnId.'.json', 'R93_PROFILE_CANDIDATE_RETURN_ABSENT') : [];
        $candidateId = $acceptance['source_profile_candidate']['id'] ?? null;
        $candidate = is_string($candidateId) ? $this->read($this->candidateDirectory.'/'.$candidateId.'.json', 'R94_PROFILE_CANDIDATE_ABSENT') : [];
        $custodyId = $acceptance['custody_lease']['custody_id'] ?? null;
        $custody = is_string($custodyId) ? $this->read($this->custodyDirectory.'/'.$custodyId.'.json', 'R95_PROFILE_CANDIDATE_CUSTODY_ABSENT') : [];
        [$instanceId, $recruiter] = $this->ordinaryRecruiter();
        $this->validate($acceptanceId, $acceptance, $return, $candidate, $custody, $instanceId, $recruiter);

        $requester = ['office' => 'conscription', 'seat' => 'conscription.recruiter', 'manifestation_id' => $recruiter['manifestation_id'], 'occupancy_generation' => $recruiter['occupancy_generation']];
        $requestId = 'examination-assembly-authorization-request-'.substr(hash('sha256', CanonicalJson::encode([$acceptanceId, $acceptance['record_digest'], $requester])), 0, 20);
        return $this->persist($requestId, [
            'schema' => 'imperium.conscription-senate-examination-assembly-authorization-request/v1',
            'request_id' => $requestId,
            'instance_id' => $instanceId,
            'proceeding_id' => $acceptance['proceeding_id'],
            'requester' => $requester,
            'recipient' => ['office' => 'senate', 'seat' => 'senate.lord-speaker'],
            'source_profile_candidate_acceptance' => ['id' => $acceptanceId, 'digest' => $acceptance['record_digest']],
            'source_return' => $acceptance['source_return'],
            'source_profile_candidate' => $acceptance['source_profile_candidate'],
            'source_acceptance' => $acceptance['source_acceptance'],
            'source_commission' => $acceptance['source_commission'],
            'source_authorization_act' => $acceptance['source_authorization_act'],
            'source_reservation_disposition' => $acceptance['source_reservation_disposition'],
            'source_plan' => $acceptance['source_plan'],
            'profile' => $acceptance['profile'],
            'persona' => $acceptance['persona'],
            'profile_scope' => $acceptance['profile_scope'],
            'custody_lease' => $acceptance['custody_lease'],
            'requested_authority' => 'ASSEMBLE_ONE_EXAMINATION_ONLY_MANIFESTATION',
            'assembly_contract' => [
                'assembler' => 'conscription.recruiter',
                'persona' => $acceptance['persona'],
                'profile_candidate' => $acceptance['profile'],
                'substrate' => ['kind' => 'generic-officer', 'version' => 0],
                'purpose' => 'SENATE_EXAMINATION_ONLY',
                'target' => 'senate.stand',
                'return_destination' => 'conscription.recruiter',
                'operational_use_permitted' => false,
            ],
            'status' => 'EXAMINATION_ASSEMBLY_AUTHORIZATION_REQUESTED_PENDING_SENATE_INTAKE',
            'recipient_acceptance' => null,
            'examination_assembly_request_authority_consumed' => true,
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

    private function validate(string $acceptanceId, array $acceptance, array $return, array $candidate, array $custody, string $instanceId, array $recruiter): void
    {
        if (!$this->digestMatches($acceptance) || !$this->digestMatches($return) || !$this->digestMatches($candidate) || !$this->digestMatches($custody)
            || 'imperium.conscription-profile-candidate-return-acceptance/v1' !== ($acceptance['schema'] ?? null) || $acceptanceId !== ($acceptance['acceptance_id'] ?? null)
            || $instanceId !== ($acceptance['instance_id'] ?? null) || 'ACCEPTED_EXACT_RETURNED_PROFILE_CANDIDATE' !== ($acceptance['disposition'] ?? null)
            || 'PROFILE_CANDIDATE_ACCEPTED_PENDING_EXAMINATION_ASSEMBLY_AUTHORIZATION' !== ($acceptance['status'] ?? null)
            || true !== ($acceptance['recipient_acceptance'] ?? null) || true !== ($acceptance['profile_candidate_acceptance_authority_consumed'] ?? null) || true !== ($acceptance['sealed'] ?? null)
            || $this->hasDownstreamAuthority($acceptance)
            || ($acceptance['actor']['seat'] ?? null) !== 'conscription.recruiter'
            || ($acceptance['actor']['manifestation_id'] ?? null) !== ($recruiter['manifestation_id'] ?? null)
            || ($acceptance['actor']['occupancy_generation'] ?? null) !== ($recruiter['occupancy_generation'] ?? null)
            || 'imperium.laboratorium-conscription-profile-candidate-return/v1' !== ($return['schema'] ?? null)
            || ($acceptance['source_return']['digest'] ?? null) !== ($return['record_digest'] ?? null)
            || 'PROFILE_CANDIDATE_RETURNED_PENDING_CONSCRIPTION_ACCEPTANCE' !== ($return['status'] ?? null) || false !== ($return['recipient_acceptance'] ?? null)
            || 'imperium.laboratorium-profile-candidate/v1' !== ($candidate['schema'] ?? null)
            || ($acceptance['source_profile_candidate']['digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || ($return['source_profile_candidate']['digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || 'PROFILE_CANDIDATE_DERIVED_VERSIONED_SEALED_PENDING_RETURN_TO_CONSCRIPTION' !== ($candidate['status'] ?? null)
            || true !== ($candidate['profile_elaboration_complete'] ?? null) || true !== ($candidate['sealed'] ?? null)
            || CanonicalJson::encode($acceptance['persona'] ?? null) !== CanonicalJson::encode($candidate['persona'] ?? null)
            || CanonicalJson::encode($acceptance['profile_scope'] ?? null) !== CanonicalJson::encode($candidate['profile_scope'] ?? null)
            || CanonicalJson::encode($acceptance['custody_lease'] ?? null) !== CanonicalJson::encode($candidate['custody_lease'] ?? null)
            || ($acceptance['profile']['candidate_digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($acceptance['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || ($acceptance['persona']['persona_id'] ?? null) !== ($custody['persona_id'] ?? null)
            || $instanceId !== ($custody['instance_id'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || 'garrison' !== ($acceptance['custody_lease']['custodian'] ?? null)
        ) throw new \RuntimeException('R96_EXAMINATION_ASSEMBLY_REQUEST_CHAIN_INVALID');
    }

    private function hasDownstreamAuthority(array $record): bool
    {
        foreach (['profile_approval_authority', 'profile_installation_authority', 'examination_assembly_authority', 'senate_examination_authority', 'custody_release_authority', 'persona_substitution_authority', 'spawning_authority', 'seat_binding_authority', 'deployment_authority', 'execution_authority'] as $field) if (true === ($record[$field] ?? null)) return true;
        return false;
    }

    private function ordinaryRecruiter(): array
    {
        $state = $this->bootstrap->read();
        if (!is_array($state) || BootstrapState::CuriaReady->value !== ($state['state'] ?? null)) throw new \RuntimeException('R97_RECRUITER_UNAVAILABLE');
        for ($index = count($state['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $state['events'][$index];
            $recruiter = 'T04' === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null) ? ($event['output']['successor'] ?? null) : null;
            if (is_array($recruiter) && 'conscription.recruiter' === ($recruiter['seat'] ?? null) && 'ordinary-recruiter' === ($recruiter['authority'] ?? null) && 2 === ($recruiter['occupancy_generation'] ?? null) && is_string($recruiter['manifestation_id'] ?? null)) return [(string) ($state['binding']['instance_id'] ?? ''), $recruiter];
        }
        throw new \RuntimeException('R97_RECRUITER_UNAVAILABLE');
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
        if (!is_dir($this->requestDirectory) && !mkdir($this->requestDirectory, 0770, true) && !is_dir($this->requestDirectory)) throw new \RuntimeException('R98_EXAMINATION_ASSEMBLY_REQUEST_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->requestDirectory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'R99_EXAMINATION_ASSEMBLY_REQUEST_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('R99_EXAMINATION_ASSEMBLY_REQUEST_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('R98_EXAMINATION_ASSEMBLY_REQUEST_FAILED'); }
        return $record;
    }
}
