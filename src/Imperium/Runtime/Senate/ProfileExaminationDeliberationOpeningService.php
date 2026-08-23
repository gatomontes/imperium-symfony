<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileExaminationDeliberationOpeningService
{
    private string $readiness;
    private string $findings;
    private string $findingOpenings;
    private string $cases;
    private string $occupancy;
    private string $custody;
    private string $deliberations;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $senate = $root.'/var/imperium/offices/senate';
        $this->readiness = $senate.'/profile-examination-finding-readiness';
        $this->findings = $senate.'/profile-examination-senator-findings';
        $this->findingOpenings = $senate.'/profile-examination-finding-authority-openings';
        $this->cases = $senate.'/profile-examination-cases';
        $this->occupancy = $senate.'/occupancy';
        $this->custody = $root.'/var/imperium/offices/garrison/custody';
        $this->deliberations = $senate.'/profile-examination-deliberation-openings';
    }

    public function open(string $readinessId, string $lordSpeakerBindingId): array
    {
        if (!preg_match('/^profile-examination-finding-readiness-[a-f0-9]{20}$/', $readinessId)) throw new \InvalidArgumentException('S252_PROFILE_EXAMINATION_FINDING_READINESS_ID_INVALID');
        $readiness = $this->read($this->readiness.'/'.$readinessId.'.json', 'S253_PROFILE_EXAMINATION_FINDING_READINESS_ABSENT');
        $openingId = $readiness['source_finding_authority_opening']['id'] ?? null;
        $opening = is_string($openingId) ? $this->read($this->findingOpenings.'/'.$openingId.'.json', 'S254_PROFILE_EXAMINATION_FINDING_AUTHORITY_OPENING_ABSENT') : [];
        $caseId = $readiness['case_id'] ?? null;
        $case = is_string($caseId) ? $this->read($this->cases.'/'.$caseId.'.json', 'S255_PROFILE_EXAMINATION_CASE_ABSENT') : [];
        $lordSpeaker = $this->read($this->occupancy.'/'.$lordSpeakerBindingId.'.json', 'S256_LORD_SPEAKER_UNAVAILABLE');
        $custodyId = $opening['custody_lease']['custody_id'] ?? null;
        $custody = is_string($custodyId) ? $this->read($this->custody.'/'.$custodyId.'.json', 'S257_PROFILE_EXAMINATION_CUSTODY_ABSENT') : [];
        if (!$this->valid($readiness) || !$this->valid($opening) || !$this->valid($case) || !$this->valid($lordSpeaker) || !$this->valid($custody)
            || 'imperium.senate-profile-examination-finding-readiness/v1' !== ($readiness['schema'] ?? null)
            || 'PROFILE_EXAMINATION_SENATOR_FINDINGS_SEALED_PENDING_DELIBERATION_OPENING' !== ($readiness['status'] ?? null)
            || true !== ($readiness['all_finding_authorities_consumed'] ?? null) || false !== ($readiness['deliberation_open'] ?? null)
            || false !== ($readiness['senate_disposition_authority'] ?? null)
            || ($readiness['source_finding_authority_opening']['digest'] ?? null) !== ($opening['record_digest'] ?? null)
            || ($readiness['case_digest'] ?? null) !== ($case['record_digest'] ?? null) || ($opening['case_digest'] ?? null) !== ($case['record_digest'] ?? null)
            || ($opening['custody_lease']['custody_digest'] ?? null) !== ($custody['record_digest'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || 'senate.lord-speaker' !== ($lordSpeaker['seat'] ?? null) || 'ACTIVE' !== ($lordSpeaker['status'] ?? null) || true !== ($lordSpeaker['binding_atomic'] ?? null)
            || true !== ($lordSpeaker['profile_examination_deliberation_opening_authority'] ?? null) || true === ($lordSpeaker['execution_authority'] ?? null)
            || ($readiness['instance_id'] ?? null) !== ($lordSpeaker['instance_id'] ?? null)
        ) throw new \RuntimeException('S258_PROFILE_EXAMINATION_DELIBERATION_OPENING_CHAIN_INVALID');

        $references = $readiness['senator_findings'] ?? null;
        if (!is_array($references) || 3 !== count($references)) throw new \RuntimeException('S258_PROFILE_EXAMINATION_DELIBERATION_OPENING_CHAIN_INVALID');
        $findings = [];
        foreach ($references as $reference) {
            $findingId = $reference['finding_id'] ?? null;
            $finding = is_string($findingId) ? $this->read($this->findings.'/'.$findingId.'.json', 'S259_PROFILE_EXAMINATION_SENATOR_FINDING_ABSENT') : [];
            $jurisdiction = $finding['jurisdiction'] ?? null;
            if (!$this->valid($finding) || !in_array($jurisdiction, ['trust', 'security', 'usability'], true) || isset($findings[$jurisdiction])
                || ($reference['jurisdiction'] ?? null) !== $jurisdiction || ($reference['finding_digest'] ?? null) !== ($finding['record_digest'] ?? null)
                || 'PROFILE_EXAMINATION_SENATOR_FINDING_AUTHORED_SEALED_PENDING_PANEL_COMPLETION' !== ($finding['status'] ?? null)
                || true !== ($finding['senator_finding_authority_consumed'] ?? null) || true !== ($finding['attributable'] ?? null)
                || false !== ($finding['deliberation_open'] ?? null) || false !== ($finding['senate_disposition_authority'] ?? null)
                || ($finding['source_finding_authority_opening']['id'] ?? null) !== $openingId
                || ($finding['source_finding_authority_opening']['digest'] ?? null) !== ($opening['record_digest'] ?? null)
                || ($finding['case_id'] ?? null) !== $caseId || ($finding['case_digest'] ?? null) !== ($case['record_digest'] ?? null)
                || ($finding['manifestation'] ?? null) !== ($opening['manifestation'] ?? null) || ($finding['profile_candidate'] ?? null) !== ($opening['profile_candidate'] ?? null)
                || ($finding['persona_identity'] ?? null) !== ($opening['persona_identity'] ?? null) || ($finding['custody_lease'] ?? null) !== ($opening['custody_lease'] ?? null)
                || ($finding['defect_attribution_rubric'] ?? null) !== ($opening['defect_attribution_rubric'] ?? null) || ($finding['return_destination'] ?? null) !== ($opening['return_destination'] ?? null)
            ) throw new \RuntimeException('S258_PROFILE_EXAMINATION_DELIBERATION_OPENING_CHAIN_INVALID');
            $findings[$jurisdiction] = $finding;
        }
        foreach (['trust', 'security', 'usability'] as $jurisdiction) if (!isset($findings[$jurisdiction])) throw new \RuntimeException('S258_PROFILE_EXAMINATION_DELIBERATION_OPENING_CHAIN_INVALID');
        ksort($findings);
        $actor = ['seat'=>'senate.lord-speaker','binding_id'=>$lordSpeakerBindingId,'binding_digest'=>$lordSpeaker['record_digest'],'manifestation_id'=>$lordSpeaker['manifestation_id'],'occupancy_generation'=>$lordSpeaker['occupancy_generation']];
        $deliberationId = 'profile-examination-deliberation-opening-'.substr(hash('sha256', CanonicalJson::encode([$readinessId, $readiness['record_digest'], $actor, array_column($findings, 'record_digest')])), 0, 20);
        return $this->save($deliberationId, [
            'schema'=>'imperium.senate-profile-examination-deliberation-opening/v1','deliberation_id'=>$deliberationId,
            'instance_id'=>$readiness['instance_id'],'case_id'=>$caseId,'case_digest'=>$case['record_digest'],
            'source_finding_readiness'=>['id'=>$readinessId,'digest'=>$readiness['record_digest']],
            'source_finding_authority_opening'=>['id'=>$openingId,'digest'=>$opening['record_digest']],
            'lord_speaker'=>$actor,'manifestation'=>$opening['manifestation'],'profile_candidate'=>$opening['profile_candidate'],'persona_identity'=>$opening['persona_identity'],
            'custody_lease'=>$opening['custody_lease'],'return_destination'=>$opening['return_destination'],'defect_attribution_rubric'=>$opening['defect_attribution_rubric'],
            'admitted_findings'=>array_values($findings),'status'=>'PROFILE_EXAMINATION_DELIBERATION_OPENED_PENDING_RECONCILIATION',
            'deliberation_opening_authority_consumed'=>true,'deliberation_open'=>true,'reconciliation_authority_exercisable'=>true,
            'reconciliation'=>null,'vote_authority'=>false,'aggregation_authority'=>false,'senate_disposition_authority'=>false,
            'profile_approval_authority'=>false,'profile_installation_authority'=>false,'seat_binding_authority'=>false,'deployment_authority'=>false,'execution_authority'=>false,'sealed'=>true,
        ]);
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function valid(array $record): bool { $digest=$record['record_digest']??null; unset($record['record_digest']); return is_string($digest)&&hash_equals($digest,hash('sha256',CanonicalJson::encode($record))); }
    private function save(string $id, array $record): array { if(!is_dir($this->deliberations)&&!mkdir($this->deliberations,0770,true)&&!is_dir($this->deliberations))throw new \RuntimeException('S260_PROFILE_EXAMINATION_DELIBERATION_OPENING_FAILED');$record['record_digest']=hash('sha256',CanonicalJson::encode($record));$path=$this->deliberations.'/'.$id.'.json';if(is_file($path)){$existing=$this->read($path,'S261_PROFILE_EXAMINATION_DELIBERATION_OPENING_CONFLICT');if($existing!==$record)throw new \RuntimeException('S261_PROFILE_EXAMINATION_DELIBERATION_OPENING_CONFLICT');return $existing;}$temporary=$path.'.tmp.'.bin2hex(random_bytes(6));if(false===file_put_contents($temporary,json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX)||!rename($temporary,$path)){@unlink($temporary);throw new \RuntimeException('S260_PROFILE_EXAMINATION_DELIBERATION_OPENING_FAILED');}return $record; }
}
