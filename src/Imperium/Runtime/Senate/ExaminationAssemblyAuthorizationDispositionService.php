<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ExaminationAssemblyAuthorizationDispositionService
{
    private string $requestDirectory;
    private string $acceptanceDirectory;
    private string $candidateDirectory;
    private string $custodyDirectory;
    private string $occupancyDirectory;
    private string $dispositionDirectory;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->requestDirectory = $projectDir.'/var/imperium/offices/senate/examination-assembly-authorization-inbox';
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/conscription/profile-candidate-return-acceptances';
        $this->candidateDirectory = $projectDir.'/var/imperium/offices/laboratorium/profile-candidates';
        $this->custodyDirectory = $projectDir.'/var/imperium/offices/garrison/custody';
        $this->occupancyDirectory = $projectDir.'/var/imperium/offices/senate/occupancy';
        $this->dispositionDirectory = $projectDir.'/var/imperium/offices/conscription/examination-assembly-authorization-dispositions';
    }

    public function decide(string $requestId, string $bindingId, string $disposition, string $rationale): array
    {
        if (!preg_match('/^examination-assembly-authorization-request-[a-f0-9]{20}$/', $requestId)) throw new \InvalidArgumentException('S174_EXAMINATION_ASSEMBLY_REQUEST_ID_INVALID');
        if (!preg_match('/^senate-lord-speaker-binding-[a-f0-9]{20}$/', $bindingId)) throw new \InvalidArgumentException('S175_LORD_SPEAKER_BINDING_ID_INVALID');
        $disposition = strtoupper(trim($disposition));
        if (!in_array($disposition, ['ACCEPTED', 'REFUSED'], true)) throw new \InvalidArgumentException('S176_EXAMINATION_ASSEMBLY_DISPOSITION_INVALID');
        $rationale = trim($rationale);
        if ('' === $rationale) throw new \InvalidArgumentException('S177_EXAMINATION_ASSEMBLY_RATIONALE_REQUIRED');

        $request = $this->read($this->requestDirectory.'/'.$requestId.'.json', 'S178_EXAMINATION_ASSEMBLY_REQUEST_ABSENT');
        $acceptanceId = $request['source_profile_candidate_acceptance']['id'] ?? null;
        $acceptance = is_string($acceptanceId) ? $this->read($this->acceptanceDirectory.'/'.$acceptanceId.'.json', 'S179_PROFILE_CANDIDATE_ACCEPTANCE_ABSENT') : [];
        $candidateId = $request['source_profile_candidate']['id'] ?? null;
        $candidate = is_string($candidateId) ? $this->read($this->candidateDirectory.'/'.$candidateId.'.json', 'S180_PROFILE_CANDIDATE_ABSENT') : [];
        $custodyId = $request['custody_lease']['custody_id'] ?? null;
        $custody = is_string($custodyId) ? $this->read($this->custodyDirectory.'/'.$custodyId.'.json', 'S181_PROFILE_CANDIDATE_CUSTODY_ABSENT') : [];
        $binding = $this->read($this->occupancyDirectory.'/'.$bindingId.'.json', 'S182_LORD_SPEAKER_UNAVAILABLE');
        $this->validate($requestId, $request, $acceptance, $candidate, $custody, $bindingId, $binding);

        $actor = [
            'seat' => 'senate.lord-speaker', 'binding_id' => $bindingId, 'binding_digest' => $binding['record_digest'],
            'manifestation_id' => $binding['manifestation_id'], 'occupancy_generation' => $binding['occupancy_generation'],
        ];
        foreach (glob($this->dispositionDirectory.'/examination-assembly-authorization-disposition-*.json') ?: [] as $path) {
            $prior = $this->read($path, 'S185_EXAMINATION_ASSEMBLY_DISPOSITION_CONFLICT');
            if (!$this->digestMatches($prior)) throw new \RuntimeException('S185_EXAMINATION_ASSEMBLY_DISPOSITION_CONFLICT');
            if (($prior['source_request']['id'] ?? null) !== $requestId) continue;
            if (($prior['disposition'] ?? null) !== $disposition || ($prior['rationale'] ?? null) !== $rationale || CanonicalJson::encode($prior['actor'] ?? null) !== CanonicalJson::encode($actor)) throw new \RuntimeException('S185_EXAMINATION_ASSEMBLY_DISPOSITION_CONFLICT');
            return $prior;
        }
        $id = 'examination-assembly-authorization-disposition-'.substr(hash('sha256', CanonicalJson::encode([$requestId, $request['record_digest'], $actor, $disposition, $rationale])), 0, 20);
        $accepted = 'ACCEPTED' === $disposition;
        return $this->persist($id, [
            'schema' => 'imperium.senate-conscription-examination-assembly-authorization-disposition/v1',
            'disposition_id' => $id,
            'instance_id' => $request['instance_id'],
            'proceeding_id' => $request['proceeding_id'],
            'actor' => $actor,
            'recipient' => $request['requester'],
            'source_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
            'source_profile_candidate_acceptance' => $request['source_profile_candidate_acceptance'],
            'source_profile_candidate' => $request['source_profile_candidate'],
            'source_return' => $request['source_return'],
            'source_authorization_act' => $request['source_authorization_act'],
            'source_reservation_disposition' => $request['source_reservation_disposition'],
            'source_plan' => $request['source_plan'],
            'profile' => $request['profile'],
            'persona' => $request['persona'],
            'profile_scope' => $request['profile_scope'],
            'custody_lease' => $request['custody_lease'],
            'assembly_contract' => $request['assembly_contract'],
            'disposition' => $disposition,
            'rationale' => $rationale,
            'status' => $accepted ? 'EXAMINATION_ASSEMBLY_AUTHORIZED_PENDING_CONSCRIPTION_ASSEMBLY' : 'EXAMINATION_ASSEMBLY_REFUSED_NO_AUTHORITY',
            'recipient_acceptance' => $accepted,
            'examination_profile_installation_authority' => $accepted,
            'examination_assembly_authority' => $accepted,
            'examination_assembly_authority_exercisable' => $accepted,
            'profile_approval_authority' => false,
            'profile_installation_authority' => false,
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

    private function validate(string $requestId, array $request, array $acceptance, array $candidate, array $custody, string $bindingId, array $binding): void
    {
        $contract = $request['assembly_contract'] ?? null;
        if (!$this->digestMatches($request) || !$this->digestMatches($acceptance) || !$this->digestMatches($candidate) || !$this->digestMatches($custody) || !$this->digestMatches($binding)
            || 'imperium.conscription-senate-examination-assembly-authorization-request/v1' !== ($request['schema'] ?? null) || $requestId !== ($request['request_id'] ?? null)
            || 'senate.lord-speaker' !== ($request['recipient']['seat'] ?? null) || 'ASSEMBLE_ONE_EXAMINATION_ONLY_MANIFESTATION' !== ($request['requested_authority'] ?? null)
            || 'EXAMINATION_ASSEMBLY_AUTHORIZATION_REQUESTED_PENDING_SENATE_INTAKE' !== ($request['status'] ?? null) || null !== ($request['recipient_acceptance'] ?? null)
            || true !== ($request['examination_assembly_request_authority_consumed'] ?? null) || true !== ($request['sealed'] ?? null) || $this->hasDownstreamAuthority($request)
            || !is_array($contract) || 'conscription.recruiter' !== ($contract['assembler'] ?? null) || 'generic-officer' !== ($contract['substrate']['kind'] ?? null) || 0 !== ($contract['substrate']['version'] ?? null)
            || 'SENATE_EXAMINATION_ONLY' !== ($contract['purpose'] ?? null) || 'senate.stand' !== ($contract['target'] ?? null) || 'conscription.recruiter' !== ($contract['return_destination'] ?? null)
            || false !== ($contract['operational_use_permitted'] ?? null)
            || 'imperium.conscription-profile-candidate-return-acceptance/v1' !== ($acceptance['schema'] ?? null)
            || ($request['source_profile_candidate_acceptance']['digest'] ?? null) !== ($acceptance['record_digest'] ?? null)
            || 'PROFILE_CANDIDATE_ACCEPTED_PENDING_EXAMINATION_ASSEMBLY_AUTHORIZATION' !== ($acceptance['status'] ?? null) || true !== ($acceptance['recipient_acceptance'] ?? null)
            || CanonicalJson::encode($request['persona'] ?? null) !== CanonicalJson::encode($acceptance['persona'] ?? null)
            || CanonicalJson::encode($request['profile_scope'] ?? null) !== CanonicalJson::encode($acceptance['profile_scope'] ?? null)
            || CanonicalJson::encode($request['custody_lease'] ?? null) !== CanonicalJson::encode($acceptance['custody_lease'] ?? null)
            || 'imperium.laboratorium-profile-candidate/v1' !== ($candidate['schema'] ?? null)
            || ($request['source_profile_candidate']['digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || ($request['profile']['candidate_digest'] ?? null) !== ($candidate['record_digest'] ?? null)
            || true !== ($candidate['profile_elaboration_complete'] ?? null) || true !== ($candidate['sealed'] ?? null)
            || 'imperium.garrison-persona-custody/v1' !== ($custody['schema'] ?? null)
            || ($request['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || ($request['persona']['persona_id'] ?? null) !== ($custody['persona_id'] ?? null)
            || ($request['instance_id'] ?? null) !== ($custody['instance_id'] ?? null) || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || !in_array($binding['schema'] ?? null, ['imperium.senate-lord-speaker-occupancy/v1', 'imperium.operator-root-seat-occupancy/v1'], true) || $bindingId !== ($binding['binding_id'] ?? null)
            || 'senate.lord-speaker' !== ($binding['seat'] ?? null) || 'ACTIVE' !== ($binding['status'] ?? null) || true !== ($binding['binding_atomic'] ?? null)
            || true !== ($binding['examination_assembly_authorization_disposition_authority'] ?? null) || true === ($binding['execution_authority'] ?? null)
            || ($request['instance_id'] ?? null) !== ($binding['instance_id'] ?? null)
        ) throw new \RuntimeException('S183_EXAMINATION_ASSEMBLY_INTAKE_CHAIN_INVALID');
    }

    private function hasDownstreamAuthority(array $record): bool
    {
        foreach (['profile_approval_authority', 'profile_installation_authority', 'examination_profile_installation_authority', 'examination_assembly_authority', 'senate_examination_authority', 'custody_release_authority', 'persona_substitution_authority', 'spawning_authority', 'seat_binding_authority', 'deployment_authority', 'execution_authority'] as $field) if (true === ($record[$field] ?? null)) return true;
        return false;
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
        if (!is_dir($this->dispositionDirectory) && !mkdir($this->dispositionDirectory, 0770, true) && !is_dir($this->dispositionDirectory)) throw new \RuntimeException('S184_EXAMINATION_ASSEMBLY_DISPOSITION_FAILED');
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $path = $this->dispositionDirectory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'S185_EXAMINATION_ASSEMBLY_DISPOSITION_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) throw new \RuntimeException('S185_EXAMINATION_ASSEMBLY_DISPOSITION_CONFLICT');
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('S184_EXAMINATION_ASSEMBLY_DISPOSITION_FAILED'); }
        return $record;
    }
}
