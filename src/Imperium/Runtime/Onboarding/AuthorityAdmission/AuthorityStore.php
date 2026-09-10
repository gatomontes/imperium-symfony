<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\AuthorityAdmission;

use App\Imperium\Runtime\Citadel\Formation\FormationJournal;
use App\Imperium\Runtime\Clock;

/** Deployment-owned construction; no root/identity/clock is read from ingress. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class AuthorityStore
{
    public FormationJournal $journal;
    public function __construct(string $root, public Clock $clock, public string $instance, public string $citadel, public string $operator, public string $sourceCommit) {
        foreach ([$instance,$citadel,$operator] as $id) { Rules::id($id); }
        Rules::require(is_dir($root) && realpath($root)!==false,'FIXED_ROOT');
        Rules::require(preg_match('/\A[0-9a-f]{40}\z/',$sourceCommit)===1,'SOURCE_COMMIT');
        $this->journal=new FormationJournal((string) realpath($root));
    }
    public function now(): int { return Rules::time($this->clock->now()->getTimestamp()); }
    public function identity(array $h): void { Rules::require($h['instance_id']===$this->instance && $h['citadel_id']===$this->citadel,'FOREIGN_RECORD'); }
    public function make(string $schema,string $id,array $body,array $sources=[]): array {
        return Rules::seal(['schema'=>$schema,'id'=>$id,'instance_id'=>$this->instance,'citadel_id'=>$this->citadel,'created_at'=>$this->now(),
            'producer'=>['service'=>'onboarding.authority-admission','source_commit'=>$this->sourceCommit], 'sources'=>Rules::refs($sources),'body'=>$body]);
    }
    public function state(array $state): array {
        Rules::require(isset($state['onboarding']),'TRUST_ABSENT'); $s=Rules::object($state['onboarding'],['schema','trust','acts','policies','evidence','revocations','admissions']);
        Rules::require($s['schema']==='imperium.onboarding-authority-state/v1','STATE_VERSION');
        foreach (['acts','policies','evidence','revocations','admissions'] as $map) { Rules::require(is_array($s[$map]),'STATE_MAP'); }
        $trust=Rules::record($s['trust']); $this->identity($trust); Rules::require($trust['schema']==='imperium.bootstrap-trust/v1','TRUST_SCHEMA');
        return $s;
    }
    public function currentTrust(array $s): array {
        $h=Rules::record($s['trust']); $this->identity($h); $t=Rules::object($h['body'],['public_key','fingerprint','issuer','competence','effects','not_before','expires_at','enrollment_receipt_ref']);
        Rules::require($t['competence']==='OPERATOR_BOOTSTRAP_POLICY' && Rules::same($t['issuer'],['kind'=>'operator','id'=>$this->operator]),'COMPETENCE');
        $key=Rules::bytes($t['public_key'],32); Rules::require($t['fingerprint']==='sha256:'.hash('sha256',$key),'TRUST_FINGERPRINT');
        Rules::time($t['not_before']); Rules::time($t['expires_at']); $now=$this->now();
        Rules::require($t['not_before']<=$now && $now<$t['expires_at'],'TRUST_TIME');
        Rules::require(!isset($s['revocations']['issuer:'.$this->operator]),'ISSUER_REVOKED');
        $receipt=$this->lookup($s,$t['enrollment_receipt_ref']);
        Rules::require($receipt['schema']==='imperium.bootstrap-enrollment/v1','ENROLLMENT_SOURCE');
        foreach (['public_key','fingerprint','issuer','competence','effects','not_before','expires_at'] as $k) { Rules::require(Rules::same($receipt['body'][$k]??null,$t[$k]),'ENROLLMENT_MISMATCH'); }
        foreach ($t['effects'] as $effect) { Rules::effect($effect,'signed_act'); Rules::supported($effect); }
        return $t;
    }
    public function lookup(array $s,mixed $ref): array {
        $key=Rules::key($ref); $item=$s['evidence'][$key]??$s['policies'][$key]??null;
        Rules::require(is_array($item),'SOURCE_MISSING'); $h=Rules::record($item['record']); $this->identity($h);
        Rules::require(Rules::same(Rules::reference($h),$ref),'SOURCE_IDENTITY');
        Rules::require(is_string($item['raw']) && Rules::same(StrictJson::decode($item['raw']),$h),'RETAINED_BYTES');
        return $h;
    }
    public function checkSource(array $s,mixed $ref,array $path=[],int $depth=0,?int &$visits=null): array {
        $visits??=0; Rules::require(++$visits<=8192,'SOURCE_VISIT_LIMIT');
        Rules::require($depth<=16,'SOURCE_DEPTH'); $k=Rules::key($ref); Rules::require(!isset($path[$k]),'SOURCE_CYCLE'); $path[$k]=true;
        $h=$this->lookup($s,$ref); Rules::require($h['created_at']<=$this->now(),'FUTURE_SOURCE');
        Rules::require(!isset($s['revocations']['policy:'.$h['id']]),'POLICY_REVOKED');
        $entry=$s['evidence'][$k]??$s['policies'][$k];
        if ($entry['admission_key']!==null) {
            $a=Admission::retained($s,$entry['admission_key']);
            $p=Act::verify($this,$s,$a['envelope'],$a['object'],false);
            Rules::require(!isset($s['revocations']['act:'.$p['nonce']]),'ACT_REVOKED');
            if ($p['policy_ref']!==null) { $this->checkSource($s,$p['policy_ref'],$path,$depth+1,$visits); }
        } else { Rules::require($h['schema']==='imperium.bootstrap-enrollment/v1','UNSIGNED_SOURCE'); }
        foreach ($h['sources'] as $source) { $this->checkSource($s,$source,$path,$depth+1,$visits); }
        return $h;
    }
}
