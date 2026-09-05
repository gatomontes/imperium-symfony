<?php
declare(strict_types=1);
namespace App\ProtectedMission;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\{ProceedingStore,PlanningDossierAssemblyService,ImperatorPlanningDossierReviewService,MissionAuthorizationDerivationService};

#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class Ceremony
{
    public function __construct(private readonly string $root) {}

    /** Called only inside the owner's common authority transaction. */
    public function prepare(array &$state, array $mission, array $disclosures, int $now): array
    {
        self::validateMission($mission,$now);
        if (($state['trust']['revoked'] ?? true) || $now >= $state['trust']['expires_at'] || $now < $state['trust']['not_before']) throw new \RuntimeException('PMA_TRUST_INACTIVE');
        $mid=$mission['mission_id'];
        if (self::terminal($state,$mid)) throw new \RuntimeException('PMA_TERMINAL');
        if (count($state['pending'] ?? []) >= 64) throw new \RuntimeException('PMA_PROPOSAL_CAPACITY');
        $predecessor=self::predecessor($state,$mid);
        $challenge='challenge-'.bin2hex(random_bytes(24));
        $payload=$this->scratch([],function(string $root) use($mission,$disclosures,$now,$state,$challenge,$predecessor):array {
            $store=new ProceedingStore($root);
            $proceeding='proceeding-'.substr($challenge,10);
            $store->persist(['proceeding_id'=>$proceeding,'instance_id'=>'protected-runtime']);
            $store->appendTurn($proceeding,'supplied-plan',1,['seneschal'=>['disposition'=>'MISSION_PLAN_DRAFTED','mission_plan'=>['objective'=>'Exact bounded Git object inspection.','protected_mission'=>$mission]]]);
            $d=(new PlanningDossierAssemblyService($store,$root))->assemble($proceeding,1,[],$disclosures,(new \DateTimeImmutable())->setTimestamp($now));
            $r=(new ImperatorPlanningDossierReviewService($root))->review($d['dossier_id'],$d['imperator_review_authority']['authority_id'],'APPROVE_DOSSIER',[],'I approve every numbered line and every bound value of this exact disposable or separately authorized dossier.',true,(new \DateTimeImmutable())->setTimestamp($now),$state['trust']['identity'],false);
            return ['schema'=>'imperium.protected-approval/v1','challenge_id'=>$challenge,'challenge_version'=>1,
                'activation'=>['operation'=>$predecessor===null?'INITIAL_AUTHORIZATION':'REPLACE_AUTHORIZATION','expected_predecessor'=>$predecessor],
                'operator_identity'=>$state['trust']['identity'],'competence'=>PublicTrust::COMPETENCE,'trust_fingerprint'=>$state['trust']['fingerprint'],
                'mission_id'=>$mission['mission_id'],'dossier'=>$d,'review_preview'=>$r,'target'=>$mission['target'],
                'permissions'=>$mission['permissions'],'prohibitions'=>$mission['prohibitions'],'paths'=>$mission['paths'],'budget'=>$mission['budget'],
                'transitions'=>$mission['transitions'],'expires_at'=>$mission['expires_at'],'nonce'=>bin2hex(random_bytes(24))];
        });
        $state['pending'][$challenge]=['status'=>'PENDING_NON_AUTHORIZING','payload'=>$payload];
        return ['challenge_id'=>$challenge,'status'=>'PENDING_NON_AUTHORIZING','payload_digest'=>hash('sha256',CanonicalJson::encode($payload)),'execution_authority'=>false];
    }

    public function export(array $state,string $id,int $now): array
    {
        $pending=$this->pending($state,$id,$now);
        return $pending['payload'];
    }

    public function submit(array &$state,string $id,string $signature,int $now): array
    {
        $pending=$this->pending($state,$id,$now); $payload=$pending['payload'];
        PublicTrust::verify($state['trust'],$payload,$signature,$now);
        $d=$payload['dossier'];
        $r=$this->scratch(['var/imperium/offices/curia/planning-dossiers/'.$d['dossier_id'].'.json'=>$d],function(string $root)use($payload,$signature,$state,$now):array {
            return (new ImperatorPlanningDossierReviewService($root))->reviewAuthenticated($payload,$signature,$state['trust'],$now);
        });
        $state['pending'][$id]['status']='APPROVED_PENDING_DERIVATION';
        $state['pending'][$id]['signature']=$signature;
        $state['pending'][$id]['review']=$r;
        return ['challenge_id'=>$id,'review_id'=>$r['review_id'],'status'=>'APPROVED_PENDING_DERIVATION','execution_authority'=>false];
    }

    public function derive(array &$state,string $id,int $now): array
    {
        $pending=$state['pending'][$id] ?? [];
        if (($pending['status'] ?? '')!=='APPROVED_PENDING_DERIVATION') throw new \RuntimeException('PMA_CHALLENGE_NOT_APPROVED');
        $payload=$pending['payload'];
        if ($now >= $payload['expires_at']) throw new \RuntimeException('PMA_CHALLENGE_INACTIVE');
        PublicTrust::verify($state['trust'],$payload,$pending['signature'],$now);
        $mid=$payload['mission_id'];
        $predecessor=self::predecessor($state,$mid);
        $activation=['operation'=>$predecessor===null?'INITIAL_AUTHORIZATION':'REPLACE_AUTHORIZATION','expected_predecessor'=>$predecessor];
        if (CanonicalJson::encode($payload['activation'] ?? null)!==CanonicalJson::encode($activation)) throw new \RuntimeException('PMA_STALE_PREDECESSOR');
        if (self::terminal($state,$mid)
            || ($predecessor!==null && ($state['inactive'][$predecessor['authorization_id']] ?? '')==='cancel')) throw new \RuntimeException('PMA_TERMINAL');
        if ($predecessor!==null && isset($state['inactive'][$predecessor['authorization_id']])) throw new \RuntimeException('PMA_AUTHORITY_INACTIVE');
        $d=$payload['dossier']; $r=$pending['review'];
        $a=$this->scratch(['var/imperium/offices/curia/planning-dossiers/'.$d['dossier_id'].'.json'=>$d,
            'var/imperium/offices/curia/planning-dossier-reviews/'.$r['review_id'].'.json'=>$r],function(string $root)use($r,$now):array {
                return (new MissionAuthorizationDerivationService($root))->derive($r['review_id'],$r['mission_authorization_derivation_authority']['authority_id'],(new \DateTimeImmutable())->setTimestamp($now));
            });
        $aid=$a['authorization_id'];
        $state['authorizations'][$aid]=['payload'=>$payload,'signature'=>$pending['signature'],'dossier'=>$d,'review'=>$r,'authorization'=>$a];
        $state['lifecycles'][$aid]=['binding'=>Generation::binding($state['authorizations'][$aid]),'state'=>'AUTHORIZED','history'=>[],'consumed_nonces'=>[]];
        if ($predecessor!==null) $state['inactive'][$predecessor['authorization_id']]='amended';
        $state['current'][$payload['mission_id']]=$aid;
        $state['pending'][$id]['status']='DERIVED'; $state['pending'][$id]['authorization_id']=$aid;
        return ['authorization_id'=>$aid,'status'=>$a['status'],'execution_authority'=>false];
    }

    public function status(array $state,string $id,int $now):array
    {
        $p=$state['pending'][$id] ?? null;
        if (!is_array($p)) throw new \RuntimeException('PMA_CHALLENGE_ABSENT');
        $status=$p['status'];
        if ($now >= $p['payload']['expires_at']) $status='EXPIRED';
        return ['challenge_id'=>$id,'status'=>$status,'authorization_id'=>$p['authorization_id'] ?? null,
            'next_action'=>match($status){'PENDING_NON_AUTHORIZING'=>'Export, inspect all canonical bytes, sign externally, submit.','APPROVED_PENDING_DERIVATION'=>'Derive canonical Mission Authorization.','DERIVED'=>'Verify authorization; approval is not execution.',default=>'Prepare a fresh dossier and obtain new approval.'},'execution_authority'=>false];
    }

    private function pending(array $state,string $id,int $now):array
    {
        $p=$state['pending'][$id] ?? null;
        if (!is_array($p)) throw new \RuntimeException('PMA_CHALLENGE_ABSENT');
        if ($p['status']!=='PENDING_NON_AUTHORIZING' || $now >= $p['payload']['expires_at']) throw new \RuntimeException('PMA_CHALLENGE_INACTIVE');
        return $p;
    }

    private static function predecessor(array $state,string $mission):?array
    {
        $id=$state['current'][$mission] ?? null;
        if ($id===null) return null;
        Generation::requireBinding($state['lifecycles'][$id] ?? [],Generation::binding($state['authorizations'][$id]));
        return ['authorization_id'=>$id,'authorization_digest'=>$state['authorizations'][$id]['authorization']['record_digest']];
    }

    private static function terminal(array $state,string $mission):bool
    {
        $id=$state['current'][$mission] ?? '';
        return isset($state['terminal_missions'][$mission])
            || in_array($state['lifecycles'][$id]['state'] ?? '',['COMPLETED','FAILED','CANCELLED'],true);
    }

    private function scratch(array $files,callable $call):array
    {
        $scratch=$this->root.'/scratch-'.bin2hex(random_bytes(16)); mkdir($scratch,0700,true);
        try {
            foreach ($files as $relative=>$record) {
                $path=$scratch.'/'.$relative; if (!is_dir(dirname($path))) mkdir(dirname($path),0700,true);
                file_put_contents($path,CanonicalJson::encode($record));
            }
            return $call($scratch);
        } finally { $this->removeScratch($scratch); }
    }
    private function removeScratch(string $path):void
    {
        foreach (new \FilesystemIterator($path) as $item) {
            if ($item->isDir() && !$item->isLink()) $this->removeScratch($item->getPathname()); else unlink($item->getPathname());
        }
        rmdir($path);
    }
    private static function validateMission(array $m,int $now):void
    {
        $required=['mission_id','target','paths','budget','expires_at','permissions','prohibitions','transitions'];
        $keys=array_keys($m); sort($keys); sort($required);
        if ($keys!==$required || !preg_match('/^[a-z0-9-]{8,100}$/',$m['mission_id'] ?? '')
            || !is_array($m['target']) || !is_string($m['target']['repository'] ?? null)
            || !preg_match('/^[a-f0-9]{40}$/',$m['target']['commit'] ?? '') || !preg_match('/^[a-f0-9]{40}$/',$m['target']['tree'] ?? '')
            || !is_int($m['expires_at']) || $m['expires_at']<=$now || $m['expires_at']>$now+86400
            || $m['permissions']!==['READ_EXACT_GIT_OBJECTS'] || $m['prohibitions']!==['NETWORK','TARGET_MUTATION','PROVIDERS','CREDENTIALS']
            || !is_array($m['paths']) || !array_is_list($m['paths']) || $m['paths']===[] || count(array_unique($m['paths']))!==count($m['paths'])) throw new \RuntimeException('PMA_MISSION_INVALID');
        foreach ($m['paths'] as $path) if (!is_string($path) || !preg_match('~^[a-zA-Z0-9_.-]+(?:/[a-zA-Z0-9_.-]+)*$~D',$path) || str_contains($path,'..') || str_starts_with($path,'-')) throw new \RuntimeException('PMA_PATH_INVALID');
        foreach (['max_files'=>1000,'max_bytes'=>8000000,'max_findings'=>1000,'max_seconds'=>60] as $key=>$max) if (!is_int($m['budget'][$key] ?? null) || $m['budget'][$key]<1 || $m['budget'][$key]>$max) throw new \RuntimeException('PMA_BUDGET_INVALID');
        $expected=[['action'=>'admit','actor'=>'protected-git-inspector','from'=>'AUTHORIZED','to'=>'ADMITTED'],['action'=>'inspect','actor'=>'protected-git-inspector','from'=>'ADMITTED','to'=>'INSPECTING'],['action'=>'complete','actor'=>'protected-git-inspector','from'=>'INSPECTING','to'=>'COMPLETED']];
        if (CanonicalJson::encode($m['transitions'])!==CanonicalJson::encode($expected)) throw new \RuntimeException('PMA_TRANSITIONS_INVALID');
    }
}
