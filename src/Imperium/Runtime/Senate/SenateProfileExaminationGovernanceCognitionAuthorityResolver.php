<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityResolver;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SenateProfileExaminationGovernanceCognitionAuthorityResolver implements GovernanceCognitionAuthorityResolver
{
    private const TYPES = ['question-trust', 'question-security', 'question-usability', 'testimony', 'finding-trust', 'finding-security', 'finding-usability', 'reconciliation', 'disposition'];
    private string $senate;
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root, ?RecordReferenceValidator $validator = null)
    { $this->senate = $root.'/var/imperium/offices/senate'; $this->validator = $validator ?? new RecordReferenceValidator($root); }

    public function supports(string $cluster, string $authorityType): bool
    { return 'senate-profile-examination' === $cluster && in_array($authorityType, self::TYPES, true); }

    public function resolve(string $cluster, string $type, string $id): array
    {
        if (!$this->supports($cluster, $type)) throw new \RuntimeException('GCA560_SENATE_PROFILE_EXAMINATION_AUTHORITY_UNSUPPORTED');
        [$source, $inputs, $seat, $purpose, $caseId, $caseDigest, $consumed] = match (true) {
            str_starts_with($type, 'question-') => $this->question(substr($type, 9), $id),
            'testimony' === $type => $this->testimony($id),
            str_starts_with($type, 'finding-') => $this->finding(substr($type, 8), $id),
            'reconciliation' === $type => $this->reconciliation($id),
            default => $this->disposition($id),
        };
        if (!$this->validator->isIntact($source)) throw new \RuntimeException('GCA562_SENATE_PROFILE_EXAMINATION_AUTHORITY_INVALID');
        return ['cluster'=>$cluster,'authority_type'=>$type,'authority_id'=>$id,'instance_id'=>$source['instance_id'],'case_id'=>$caseId,'case_digest'=>$caseDigest,'seat'=>$seat,'purpose'=>$purpose,'input_digest'=>hash('sha256',CanonicalJson::encode($inputs)),'source'=>['id'=>$id,'digest'=>$source['record_digest']],'single_use'=>true,'exercisable'=>true,'consumed'=>$consumed,'expires_at'=>'9999-12-31T23:59:59+00:00'];
    }

    private function question(string $jurisdiction, string $id): array
    {
        $commission = $this->oneOf(['profile-examination-commission-inbox','delegate-mission-profile-examination-question-commissions'], $id);
        $delegate = str_contains((string)($commission['schema'] ?? ''), 'delegate-mission');
        if ($jurisdiction !== ($commission['jurisdiction'] ?? $this->seatJurisdiction($commission['recipient']['seat'] ?? null))) throw new \RuntimeException('GCA562_SENATE_PROFILE_EXAMINATION_AUTHORITY_INVALID');
        $opening = $delegate
            ? $this->referenced('delegate-mission-profile-examination-openings', $commission['source_examination_opening']['id'] ?? null)
            : $this->find('profile-examination-testimony-openings', fn(array $r): bool => ($r['case_id'] ?? null) === ($commission['case_id'] ?? null));
        $authority=$delegate
            ?$this->find('delegate-mission-profile-examination-question-commission-dispositions',fn(array$r):bool=>$id===($r['source_commission']['id']??null)&&'ACCEPTED'===($r['disposition']??null))
            :$this->find('profile-examination-commission-acceptances',fn(array$r):bool=>$id===($r['source_commission']['id']??null)&&true===($r['recipient_acceptance']??null));
        return [$authority, [$commission,$opening], 'senate.committee.'.$jurisdiction, 'author-profile-question', $opening['case_id'] ?? $id, $opening['case_digest'] ?? $opening['record_digest'], $this->existsBy('profile-examination-questions', 'source_commission.id', $id) || $this->existsBy('delegate-mission-profile-examination-questions', 'source_commission.id', $id)];
    }

    private function testimony(string $id): array
    {
        $question = $this->oneOf(['profile-examination-questions','delegate-mission-profile-examination-question-dispatches'], $id);
        return [$question, [$question,$question['manifestation']], 'senate.stand', 'answer-profile-question', $question['case_id'] ?? $id, $question['case_digest'] ?? $question['record_digest'], $this->existsBy('profile-examination-testimony-turns','source_question.id',$id) || $this->existsBy('delegate-mission-profile-examination-testimony-turns','source_dispatch.id',$id)];
    }

    private function finding(string $jurisdiction, string $turnId): array
    {
        $turn = $this->oneOf(['profile-examination-testimony-turns','model-bound-profile-evidence-testimony-turns','delegate-mission-profile-examination-testimony-turns'], $turnId);
        if ($jurisdiction !== ($turn['jurisdiction'] ?? null)) throw new \RuntimeException('GCA562_SENATE_PROFILE_EXAMINATION_AUTHORITY_INVALID');
        $opening = $this->findOneOf(['profile-examination-finding-authority-openings','model-bound-profile-finding-authority-openings','delegate-mission-profile-examination-finding-authority-openings'], fn(array $r): bool => 1 === count(array_filter($r['finding_authorities'] ?? [], fn(mixed $a): bool => is_array($a) && $jurisdiction === ($a['jurisdiction'] ?? null) && $turnId === ($a['source_testimony_turn']['id'] ?? null))));
        $authority = array_values(array_filter($opening['finding_authorities'], fn(array $a): bool => $jurisdiction === ($a['jurisdiction'] ?? null)))[0];
        $prefix = str_contains((string)$opening['schema'], 'model-bound') ? 'evidence-turn' : (str_contains((string)$opening['schema'], 'delegate-mission') ? 'delegate-testimony' : 'testimony');
        $evidence = ['testimony_turn'=>$turn,'available_evidence_references'=>[$prefix.':'.$jurisdiction.':'.$turn['record_digest']]];
        if (str_contains((string)$opening['schema'], 'delegate-mission')) $evidence['peer_findings'] = [];
        return [$opening, [$authority,$evidence], 'senate.committee.'.$jurisdiction, 'issue-profile-finding', $opening['case_id'] ?? $turnId, $opening['case_digest'] ?? $opening['record_digest'], $this->existsByAny(['profile-examination-senator-findings','model-bound-profile-senator-findings','delegate-mission-profile-examination-senator-findings'],'source_testimony_turn.id',$turnId)];
    }

    private function reconciliation(string $id): array
    {
        $opening = $this->findOneOf(['profile-examination-deliberation-openings','model-bound-profile-deliberation-openings','delegate-mission-profile-examination-deliberation-openings'], fn(array $r): bool => $id === ($r['deliberation_id'] ?? null) || $id === ($r['reconciliation_authority']['authority_id'] ?? null));
        $findings = $this->findings($opening);
        $refs = array_map(fn(array $f): string => (str_contains((string)$opening['schema'],'delegate-mission')?'delegate-finding':'finding').':'.$f['jurisdiction'].':'.$f['record_digest'], $findings);
        $authority = str_contains((string)$opening['schema'],'model-bound') ? ['reconciliation_authority_id'=>$id,'available_finding_references'=>$refs,'mandatory_security_blocking_condition'=>$opening['mandatory_security_blocking_condition'],'vote_authority'=>false,'aggregation_authority'=>false,'senate_disposition_authority'=>false] : ['deliberation_id'=>$opening['deliberation_id'],'deliberation_digest'=>$opening['record_digest'],'lord_speaker'=>$opening['lord_speaker'],'available_finding_references'=>$refs]+(str_contains((string)$opening['schema'],'delegate-mission')?['mandatory_security_blocking_condition'=>$opening['mandatory_security_blocking_condition']]:[])+['reconciliation_authority_exercisable'=>true,'vote_authority'=>false,'aggregation_authority'=>false,'senate_disposition_authority'=>false];
        return [$opening,[$authority,$findings],'senate.lord-speaker','reconcile-profile-findings',$opening['case_id'] ?? $id,$opening['case_digest'] ?? $opening['record_digest'],$this->existsByAny(['profile-examination-reconciliations','model-bound-profile-reconciliations','delegate-mission-profile-examination-reconciliations'],'source_deliberation_opening.id',$opening['deliberation_id'] ?? '')];
    }

    private function disposition(string $id): array
    {
        $opening = $this->findOneOf(['profile-examination-disposition-authority-openings','model-bound-profile-disposition-authority-openings','delegate-mission-profile-examination-disposition-authority-openings'], fn(array $r): bool => $id === ($r['opening_id'] ?? null) || $id === ($r['disposition_authority']['authority_id'] ?? null));
        $findings=$this->findings($opening); $delegate=str_contains((string)$opening['schema'],'delegate-mission');$ordinary='imperium.senate-profile-examination-disposition-authority-opening/v1'===($opening['schema']??null);
        $refs=array_map(fn(array$f):string=>($delegate?'delegate-finding':'finding').':'.$f['jurisdiction'].':'.$f['record_digest'],$findings);
        if ($ordinary) { $keyed=[]; foreach($findings as$f)$keyed[$f['jurisdiction']]='finding:'.$f['jurisdiction'].':'.$f['record_digest']; ksort($keyed); $refs=array_values($keyed); }
        $authority=$ordinary?['opening_id'=>$opening['opening_id'],'opening_digest'=>$opening['record_digest'],'available_finding_references'=>$refs,'senate_disposition_authority'=>true,'profile_approval_authority'=>false]:['disposition_authority_id'=>$opening['disposition_authority']['authority_id'],'available_finding_references'=>$refs,'permitted_dispositions'=>$opening['disposition_authority']['permitted_dispositions'],'mandatory_security_blocking_condition'=>$opening['mandatory_security_blocking_condition'],'profile_approval_authority'=>false];
        return [$opening,[$authority,$findings,$opening['reconciliation']],'senate.lord-speaker','decide-profile-disposition',$opening['case_id']??$id,$opening['case_digest']??$opening['record_digest'],$this->existsByAny(['profile-examination-dispositions','model-bound-profile-dispositions','delegate-mission-profile-examination-dispositions'],'source_disposition_authority_opening.id',$opening['opening_id']??'')];
    }

    private function findings(array $opening): array
    {
        $result=[];$seen=[]; foreach($opening['admitted_findings']??[] as $snapshot){$f=$snapshot;if(!$this->validator->isIntact($f)){ $f=$this->oneOf(['profile-examination-senator-findings','model-bound-profile-senator-findings','delegate-mission-profile-examination-senator-findings'],$snapshot['finding_id']??''); } $result[]=$f;$seen[]=$f['jurisdiction']??null;}
        $jurisdictions=$seen;sort($jurisdictions,SORT_STRING);if(['security','trust','usability']!==$jurisdictions)throw new \RuntimeException('GCA562_SENATE_PROFILE_EXAMINATION_AUTHORITY_INVALID');return$result;
    }
    private function oneOf(array $dirs,string $id):array{foreach($dirs as$d)if(is_file($p=$this->senate.'/'.$d.'/'.$id.'.json'))return $this->validator->read($p,'GCA562_SENATE_PROFILE_EXAMINATION_AUTHORITY_INVALID');throw new \RuntimeException('GCA561_SENATE_PROFILE_EXAMINATION_AUTHORITY_ABSENT');}
    private function referenced(string $dir,mixed $id):array{if(!is_string($id))throw new \RuntimeException('GCA562_SENATE_PROFILE_EXAMINATION_AUTHORITY_INVALID');return$this->oneOf([$dir],$id);}
    private function find(string $dir,callable $match):array{return$this->findOneOf([$dir],$match);}
    private function findOneOf(array $dirs,callable $match):array{$found=[];foreach($dirs as$d)foreach(glob($this->senate.'/'.$d.'/*.json')?:[]as$p){try{$r=$this->validator->read($p,'GCA562_SENATE_PROFILE_EXAMINATION_AUTHORITY_INVALID');if($match($r))$found[]=$r;}catch(\Throwable){}}if(1!==count($found))throw new \RuntimeException('GCA562_SENATE_PROFILE_EXAMINATION_AUTHORITY_INVALID');return$found[0];}
    private function existsBy(string $dir,string $path,string $value):bool{return$this->existsByAny([$dir],$path,$value);}
    private function existsByAny(array$dirs,string$path,string$value):bool{foreach($dirs as$d)foreach(glob($this->senate.'/'.$d.'/*.json')?:[]as$p){try{$r=$this->validator->read($p,'x');$v=$r;foreach(explode('.',$path)as$k)$v=$v[$k]??null;if($value===$v)return true;}catch(\Throwable){}}return false;}
    private function seatJurisdiction(mixed$seat):?string{return is_string($seat)&&str_starts_with($seat,'senate.committee.')?substr($seat,17):null;}
}
