<?php
declare(strict_types=1);

/** Preparation/public evidence only. No authority store or private key access. */
final class LocalIsolation
{
    public const COMMIT = 'a1fc4f27634319f2a22df2e6a1b370f70cdb98bf';
    public const PATHS = [
        'docs/delegate-mission-flow.md','docs/protected-mission-operator-runbook.md',
        'docs/next-campaign-mission-amendment-correction.md',
        'docs/handoffs/mission-amendment-correction-local-audit-complete.md',
        'bin/protected-mission.php','src/ProtectedMission/Cli.php',
        'src/ProtectedMission/InstalledRuntime.php','src/ProtectedMission/AuthorityOwner.php',
        'src/ProtectedMission/Ceremony.php','src/ProtectedMission/Generation.php',
        'src/ProtectedMission/OfflineGitInspector.php','src/ProtectedMission/InspectionProcess.php',
        'tools/ProtectedMission.ps1','tools/Install-ProtectedMission.ps1',
        'tools/Assert-ProtectedMissionInstallation.ps1',
    ];
    public static function canonical(mixed $v): string
    {
        if (is_array($v)) {
            if (!array_is_list($v)) ksort($v, SORT_STRING);
            foreach ($v as &$x) $x=json_decode(self::canonical($x),true,512,JSON_THROW_ON_ERROR);
            unset($x);
        }
        return json_encode($v,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    }
    public static function same(mixed $a,mixed $b,string $why): void
    {
        if (self::canonical($a)!==self::canonical($b)) throw new RuntimeException($why);
    }
    public static function manifest(string $root): array
    {
        if (!is_dir($root) || is_link($root)) throw new RuntimeException('MANIFEST_ROOT_INVALID');
        $out=[];
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST) as $f) {
            if ($f->isLink()) throw new RuntimeException('MANIFEST_LINK_REFUSED');
            if ($f->isFile()) $out[str_replace('\\','/',substr($f->getPathname(),strlen(rtrim($root,'/\\'))+1))]=hash_file('sha256',$f->getPathname());
        }
        ksort($out,SORT_STRING);return $out;
    }
    public static function writeNew(string $path,array $value): void
    {
        $h=fopen($path,'xb');if (!$h) throw new RuntimeException('OUTPUT_EXISTS_OR_UNWRITABLE');
        try { $b=json_encode($value,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
            if (fwrite($h,$b)!==strlen($b)) throw new RuntimeException('OUTPUT_SHORT_WRITE');
        } finally {fclose($h);}
    }
    /** Original object bytes from local Git, never checkout/filter/fetch/repack. */
    public static function materialize(string $source,string $destination,string $commit=self::COMMIT,array $paths=self::PATHS): array
    {
        if (file_exists($destination)) throw new RuntimeException('FRESH_TARGET_REQUIRED');
        mkdir($destination.'/.git/objects',0700,true);
        $objects=[];
        $read=static function(string $id,string $type)use($source,$destination,&$objects):string {
            if (!preg_match('/^[a-f0-9]{40}$/D',$id) || !in_array($type,['commit','tree','blob'],true)) throw new RuntimeException('OBJECT_REQUEST_INVALID');
            $env=getenv();$env['GIT_NO_LAZY_FETCH']='1';$env['GIT_NO_REPLACE_OBJECTS']='1';$env['GIT_TERMINAL_PROMPT']='0';
            $p=proc_open(['git','--no-replace-objects','-C',$source,'-c','core.fsmonitor=false','cat-file',$type,$id],
                [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,$env,['bypass_shell'=>true]);
            fclose($pipes[0]);$bytes=stream_get_contents($pipes[1]);fclose($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[2]);
            if(proc_close($p)!==0)throw new RuntimeException('LOCAL_OBJECT_UNAVAILABLE: '.$error);
            $raw=$type.' '.strlen($bytes)."\0".$bytes;
            if(sha1($raw)!==$id)throw new RuntimeException('SOURCE_OBJECT_HASH_INVALID');
            $dir=$destination.'/.git/objects/'.substr($id,0,2);if(!is_dir($dir))mkdir($dir);
            $path=$dir.'/'.substr($id,2);
            if(!isset($objects[$id]))file_put_contents($path,gzcompress($raw));
            $objects[$id]=['type'=>$type,'bytes'=>strlen($bytes),'sha256'=>hash('sha256',$bytes)];return $bytes;
        };
        $inventory=self::walk($read,$commit,$paths);
        $inventory['objects']=$objects;$inventory['manifest']=self::manifest($destination);
        $inventory['source']=realpath($source);$inventory['derivation']='Local cat-file bytes, original SHA-1 verified, fresh compressed files; no shared objects or hardlinks.';
        $inventory['target_repository']=$destination;return $inventory;
    }
    /** Independent byte traversal, including repeated tree cost of the deployed reader. */
    public static function walk(Closure $read,string $commit,array $paths):array
    {
        $count=0;
        $object=static function($id,$type)use($read,&$count){$b=$read($id,$type);$count+=strlen($b);return $b;};
        $c=$object($commit,'commit');if(!preg_match('/^tree ([a-f0-9]{40})\n/',$c,$m))throw new RuntimeException('COMMIT_TREE_INVALID');
        $root=$object($m[1],'tree');$findings=[];
        foreach($paths as $path){
            $tree=$root;$parts=explode('/',$path);
            foreach($parts as $i=>$part){
                $offset=0;$found=null;
                while($offset<strlen($tree)){
                    $space=strpos($tree,' ',$offset);$nul=strpos($tree,"\0",$offset);
                    if($space===false||$nul===false||$space>$nul||$nul+21>strlen($tree))throw new RuntimeException('TREE_INVALID');
                    $name=substr($tree,$space+1,$nul-$space-1);$mode=substr($tree,$offset,$space-$offset);$id=bin2hex(substr($tree,$nul+1,20));
                    if($name===$part){$found=[$mode,$id];break;}$offset=$nul+21;
                }
                if($found===null)throw new RuntimeException('PATH_ABSENT');
                if($i<count($parts)-1){if($found[0]!=='40000')throw new RuntimeException('PATH_NOT_TREE');$tree=$object($found[1],'tree');}
            }
            if(!in_array($found[0],['100644','100755'],true))throw new RuntimeException('PATH_NOT_FILE');
            $b=$object($found[1],'blob');$findings[]=['path'=>$path,'blob_id'=>$found[1],'byte_length'=>strlen($b),'sha256'=>hash('sha256',$b)];
        }
        return ['commit'=>$commit,'tree'=>$m[1],'paths'=>$paths,'findings'=>$findings,'object_bytes_read'=>$count];
    }
    public static function looseReader(string $repository):Closure
    {
        return static function(string $id,string $type)use($repository):string {
            if(!preg_match('/^[a-f0-9]{40}$/D',$id))throw new RuntimeException('OBJECT_ID_INVALID');
            $p=$repository.'/.git/objects/'.substr($id,0,2).'/'.substr($id,2);
            $raw=gzuncompress(file_get_contents($p));$nul=strpos($raw,"\0");$b=substr($raw,$nul+1);
            if(sha1($raw)!==$id||substr($raw,0,$nul)!==$type.' '.strlen($b))throw new RuntimeException('OBJECT_INVALID');return $b;
        };
    }
    public static function draft(array $inventory,string $target,int $expiry=0):array
    {
        $mission=['mission_id'=>'local-isolation-'.bin2hex(random_bytes(12)),
            'target'=>['repository'=>$target,'commit'=>$inventory['commit'],'tree'=>$inventory['tree']],
            'paths'=>$inventory['paths'],'budget'=>['max_files'=>15,'max_bytes'=>4000000,'max_findings'=>15,'max_seconds'=>60],
            'expires_at'=>$expiry,'permissions'=>['READ_EXACT_GIT_OBJECTS'],'prohibitions'=>['NETWORK','TARGET_MUTATION','PROVIDERS','CREDENTIALS'],
            'transitions'=>[['action'=>'admit','actor'=>'protected-git-inspector','from'=>'AUTHORIZED','to'=>'ADMITTED'],['action'=>'inspect','actor'=>'protected-git-inspector','from'=>'ADMITTED','to'=>'INSPECTING'],['action'=>'complete','actor'=>'protected-git-inspector','from'=>'INSPECTING','to'=>'COMPLETED']]];
        if($inventory['object_bytes_read']>4000000||count($inventory['paths'])>15)throw new RuntimeException('DRAFT_BUDGET_EXCEEDED');
        return ['mission'=>$mission,'disclosures'=>[
            'material_facts'=>['One historical merged snapshot; no inspection of future implementation HEAD. Mechanical bytes and metadata only.',
                'Already installed protected-git-inspector/PHP and owner-operated Runtime terminal; separate caller, deployment administrator and held-key Operator. No personnel or tool preparation is commissioned. The personnel and tools_credentials_data demand lists are intentionally empty.',
                'Only allowlisted local Git objects are read. Only separate authority lifecycle, evidence, status and receipt files are written. No network, providers, credentials, target remediation or second mission.',
                'Canonical ceremony and worker staging use the administrator-provisioned ProtectedMissionScratch sibling. Runtime creates nested temporary work and cleans its exact workspace before authority publication. Caller is excluded; owner references remain outside scratch.'],
            'assumptions'=>['Owner has measured actual-account access and protected PHP, PowerShell, environment, dependencies and relevant parents. Administrators and compromised trusted Runtime/signing processes are outside caller exclusion.'],
            'unknowns'=>['Analytical discrepancies are unknown until separately evaluated; zero is a valid result. Hardware power-loss durability is unmeasured.'],
            'dependencies'=>['Actual isolated fresh Windows deployment, independently confirmed public fingerprint and authentic exact canonical approval are required before execution. No migration, replacement enrollment or journal reset.'],
            'personnel'=>[],'tools_credentials_data'=>[],
            'external_operations'=>['Deterministic local loose-SHA-1 inspection only; no external cognition sortie, provider call, outbound contact or credential release.'],
            'cost_time_retention_limits'=>['15 paths, 15 mechanical findings, 4000000 accepted inflated object bytes, 60 seconds per inspection; at most 15 separate analytical discrepancies. Proposed 900 seconds reduced to schema maximum 60.',
                'Retain local owner evidence for this audit; do not publish pending challenges or usable capabilities. Bounded store: 64 proposals, 16 MB state, 64 MiB journal. No automatic eviction/reset.'],
            'risks_contingencies_fallbacks'=>['Any access success where denial is required stops deployment. Refusal, timeout or unknown consume stops; query persisted IDs and preserve incident, never blindly replay or enlarge scope. No automatic fallback or retry mission.'],
            'evidence_provenance_reporting'=>['Capture AUTHORIZED, ADMITTED, INSPECTING, COMPLETED and reconstruct generation/receipt/object bindings. INSPECTING is persisted after synchronous inspection; no percentage progress is claimed.',
                'Hash complete target before/after. Separate analyst report cites exact document and executable blob/line, expected/observed behavior, confidence and bounded implication. Receipt verifies bytes, not semantic truth/completeness. Outside-allowlist gaps remain unverified.'],
            'expiry_revocation_reauthorization'=>[($expiry===0?'Expiry 0 in this draft is deliberately unusable.':'Fresh explicit Unix expiry is '.$expiry.'.').' Set expiry within 24 hours only immediately before preparation; changed scope or retry requires fresh authentic approval. Never borrow historical generations.'],
        ]];
    }
    /** Public chain and receipt verification; independent of producer booleans and Generation. */
    public static function verify(array $chain,array $status,array $trust,array $inventory,string $repository,array $before,array $after):array
    {
        self::same($before,$after,'TARGET_MANIFEST_CHANGED');self::same($before,self::manifest($repository),'TARGET_CURRENT_MANIFEST_CHANGED');
        $p=$chain['payload'];$d=$chain['dossier'];$a=$chain['authorization'];$r=$chain['review'];
        foreach([$d,$a,$r] as $record){$digest=$record['record_digest'];unset($record['record_digest']);self::same($digest,hash('sha256',self::canonical($record)),'CHAIN_DIGEST_INVALID');}
        $public=base64_decode($trust['public_key'],true);self::same(hash('sha256',$public),$p['trust_fingerprint'],'TRUST_FINGERPRINT_INVALID');
        if(!sodium_crypto_sign_verify_detached(base64_decode($chain['signature'],true),self::canonical($p),$public))throw new RuntimeException('APPROVAL_SIGNATURE_INVALID');
        self::same($p['operator_identity'],$trust['identity'],'OPERATOR_INVALID');
        self::same($p['competence'],'APPROVE_CANONICAL_MISSION_PLAN','COMPETENCE_INVALID');
        self::same($trust['competence'],$p['competence'],'TRUST_COMPETENCE_INVALID');
        self::same($p['activation'],['operation'=>'INITIAL_AUTHORIZATION','expected_predecessor'=>null],'FRESH_INITIAL_AUTHORIZATION_REQUIRED');
        self::same($p['dossier'],$d,'SIGNED_DOSSIER_INVALID');
        self::same($d['mission_plan']['protected_mission'],array_intersect_key($p,array_flip(['mission_id','target','paths','budget','expires_at','permissions','prohibitions','transitions'])),'SIGNED_MISSION_INVALID');
        self::same($r['operator_authenticity']['payload_digest'],hash('sha256',self::canonical($p)),'REVIEW_PAYLOAD_INVALID');
        self::same($r['operator_authenticity']['signature'],$chain['signature'],'REVIEW_SIGNATURE_INVALID');
        self::same($r['operator_authenticity']['challenge_id'],$p['challenge_id'],'REVIEW_CHALLENGE_INVALID');
        self::same($r['operator_authenticity']['trust_fingerprint'],$p['trust_fingerprint'],'REVIEW_TRUST_INVALID');
        $preview=$r;unset($preview['operator_authenticity'],$preview['record_digest']);$preview['record_digest']=hash('sha256',self::canonical($preview));
        self::same($preview,$p['review_preview'],'AUTHENTICATED_REVIEW_CHANGED');
        self::same($a['authority_source']['dossier'],['id'=>$d['dossier_id'],'version'=>$d['dossier_version'],'digest'=>$d['record_digest']],'AUTHORIZATION_DOSSIER_INVALID');
        self::same($a['authority_source']['imperator_review'],['id'=>$r['review_id'],'digest'=>$r['record_digest']],'AUTHORIZATION_REVIEW_INVALID');
        self::same($a['authorized_dossier_lines'],$d['lines'],'AUTHORIZED_LINES_INVALID');
        self::same($a['mission_plan'],$d['mission_plan'],'AUTHORIZED_PLAN_INVALID');
        self::same($a['authorized_disclosures'],$d['disclosures'],'AUTHORIZED_DISCLOSURES_INVALID');
        self::same($a['authority_source']['derivation_authority_id'],$r['mission_authorization_derivation_authority']['authority_id'],'DERIVATION_SOURCE_INVALID');
        $expectedAid='mission-authorization-'.substr(hash('sha256',self::canonical([$a['authority_source'],$d['lines'],$d['resource_demands']??[],$d['proposed_model_bindings']??[],$d['disclosures']??[]])),0,20);
        self::same($a['authorization_id'],$expectedAid,'DERIVED_AUTHORIZATION_ID_INVALID');
        $binding=['authorization_id'=>$a['authorization_id'],'authorization_digest'=>$a['record_digest'],'mission_id'=>$p['mission_id'],'dossier_id'=>$d['dossier_id'],'dossier_version'=>$d['dossier_version'],'dossier_digest'=>$d['record_digest'],'target'=>$p['target'],'paths'=>$p['paths'],'budget'=>$p['budget']];
        $binding['generation_id']=hash('sha256',self::canonical($binding));
        $receipt=$status['receipt'];$life=$status['lifecycle'];$snapshot=$receipt['snapshot'];
        foreach([$status,$life,$receipt,$snapshot] as $record)self::same($binding,$record['binding'],'GENERATION_INVALID');
        self::same($status['authorization_id'],$binding['authorization_id'],'STATUS_AUTHORIZATION_INVALID');
        foreach(['authorization_id','mission_id','dossier_digest'] as $field)self::same($receipt[$field],$binding[$field],'RECEIPT_TOP_LEVEL_BINDING_INVALID');
        self::same($life['state'],'COMPLETED','NOT_COMPLETED');self::same(count($life['history']),3,'HISTORY_COUNT_INVALID');
        $commission='protected-git-commission-'.hash('sha256',$a['authorization_id'].'|'.$a['preparation_authorities']['execution_commission_derivation']['authority_id']);
        self::same($receipt['commission_id'],$commission,'RECEIPT_COMMISSION_INVALID');
        self::same($receipt['authorization_id'],$a['authorization_id'],'RECEIPT_AUTHORIZATION_INVALID');
        self::same($receipt['trust_fingerprint'],$p['trust_fingerprint'],'RECEIPT_TRUST_INVALID');
        self::same($receipt['operator_identity'],$p['operator_identity'],'RECEIPT_OPERATOR_INVALID');
        $nonces=[];$last=0;$issuer=null;
        foreach($life['history'] as $i=>$event){
            $cap=$event['capability']['payload'];$signature=base64_decode($event['capability']['signature'],true);
            if(!sodium_crypto_sign_verify_detached($signature,self::canonical($cap),base64_decode($cap['issuer'],true)))throw new RuntimeException('CAPABILITY_SIGNATURE_INVALID');
            if($issuer!==null)self::same($issuer,$cap['issuer'],'ISSUER_CHANGED');$issuer=$cap['issuer'];
            self::same($cap['binding'],$binding,'CAPABILITY_GENERATION_INVALID');self::same($cap['commission_id'],$commission,'CAPABILITY_COMMISSION_INVALID');
            foreach(['authorization_id','authorization_digest','dossier_digest','mission_id','target'] as $field)self::same($cap[$field],$binding[$field],'CAPABILITY_TOP_LEVEL_BINDING_INVALID');
            self::same(array_intersect_key($cap,array_flip(['action','actor','from','to'])),$p['transitions'][$i],'TRANSITION_INVALID');
            self::same($cap['expires_at'],$p['expires_at'],'CAPABILITY_EXPIRY_INVALID');
            if($event['consumed_at']<$last||$event['consumed_at']>=$p['expires_at']||$event['consumed_at']<$trust['not_before']||$event['consumed_at']>=$trust['expires_at'])throw new RuntimeException('EVENT_TIME_INVALID');
            $last=$event['consumed_at'];if(isset($nonces[$cap['nonce']]))throw new RuntimeException('NONCE_REPLAY');$nonces[$cap['nonce']]=true;
        }
        self::same($nonces,$life['consumed_nonces'],'CONSUMED_NONCES_INVALID');self::same($last,$receipt['completed_at'],'COMPLETION_TIME_INVALID');
        self::same($p['target'],['repository'=>$repository,'commit'=>$inventory['commit'],'tree'=>$inventory['tree']],'TARGET_SCOPE_INVALID');
        self::same($p['paths'],$inventory['paths'],'PATH_SCOPE_INVALID');
        $actual=self::walk(self::looseReader($repository),$inventory['commit'],$inventory['paths']);
        self::same($actual['findings'],$inventory['findings'],'INVENTORY_BYTES_INVALID');
        self::same($snapshot['commit_id'],$actual['commit'],'RECEIPT_COMMIT_INVALID');self::same($snapshot['tree_id'],$actual['tree'],'RECEIPT_TREE_INVALID');
        self::same($snapshot['object_bytes_read'],$actual['object_bytes_read'],'BYTE_ACCOUNTING_INVALID');
        $found=[];
        foreach($snapshot['findings'] as $f){$bytes=base64_decode($f['bytes_base64'],true);
            self::same($f['blob_id'],sha1('blob '.strlen($bytes)."\0".$bytes),'FINDING_BLOB_INVALID');self::same($f['sha256'],hash('sha256',$bytes),'FINDING_HASH_INVALID');self::same($f['byte_length'],strlen($bytes),'FINDING_LENGTH_INVALID');unset($f['bytes_base64']);$found[]=$f;}
        self::same($found,$actual['findings'],'RECEIPT_FINDINGS_INVALID');
        if(count($found)>$p['budget']['max_files']||count($found)>$p['budget']['max_findings']||$actual['object_bytes_read']>$p['budget']['max_bytes'])throw new RuntimeException('RECEIPT_BUDGET_EXCEEDED');
        return ['result'=>'PUBLIC_RECEIPT_BYTES_AND_GENERATION_VERIFIED','generation_id'=>$binding['generation_id'],'authorization_id'=>$a['authorization_id'],'target_commit'=>$actual['commit'],'target_tree'=>$actual['tree'],'files'=>count($found),'object_bytes_read'=>$actual['object_bytes_read'],'target_manifest_sha256'=>hash('sha256',self::canonical($after)),
            'isolation_proved'=>false,'semantic_conclusions_certified'=>false,'limit'=>'Receipt is not independently signed; issuer identity and elapsed deadline rely on recorded trusted Runtime provenance. Public reconstruction detects inconsistent bindings/bytes, not a compromised Runtime fabricating a consistent transcript.'];
    }
}
