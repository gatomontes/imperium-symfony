<?php
declare(strict_types=1);
namespace App\ProtectedMission;

use App\Bootstrap\CanonicalJson;

/** Trusted Runtime implementation. OS access to root/code is the security boundary. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class AuthorityOwner
{
    public function __construct(private readonly string $root) {}

    /** Deployment-owner operation; deliberately not exposed by dispatch(). */
    public function enroll(array $publicTrust, string $confirmedFingerprint): array
    {
        $trust = PublicTrust::validate($publicTrust, $confirmedFingerprint);
        if (!is_dir($this->root) && !mkdir($this->root, 0700, true)) throw new \RuntimeException('PMA_STORAGE_FAILED');
        return $this->transaction(function (array &$state) use ($trust): array {
            if (isset($state['trust'])) throw new \RuntimeException('PMA_ALREADY_ENROLLED_RECOVERY_REQUIRED');
            $state['trust'] = $trust;
            $state['custody'] = ['enrolled_at'=>time(), 'fingerprint'=>$trust['fingerprint'], 'action'=>'explicit-owner-bootstrap'];
            return $state['custody'];
        }, true);
    }

    /** Narrow public protocol. Unknown fields fail closed; no root/verifier/clock adapters. */
    public function dispatch(array $request): array
    {
        if (array_keys($request) !== ['operation', 'arguments'] || !is_string($request['operation']) || !is_array($request['arguments'])) {
            throw new \RuntimeException('PMA_REQUEST_INVALID');
        }
        $operation = $request['operation']; $arguments = $request['arguments'];
        $fields = match ($operation) {
            'trust' => [], 'verify', 'issue', 'status' => ['authorization_id'],
            'consume' => ['capability'], 'control' => ['payload', 'signature'],
            'prepare' => ['mission','disclosures'], 'export','derive','challenge-status' => ['challenge_id'],
            'submit' => ['challenge_id','signature'],
            default => throw new \RuntimeException('PMA_OPERATION_REFUSED'),
        };
        if (array_keys($arguments) !== $fields) throw new \RuntimeException('PMA_ARGUMENTS_INVALID');
        return $this->transaction(function (array &$state) use ($operation, $arguments): array {
            $now = time();
            $ceremony = new Ceremony($this->root);
            return match ($operation) {
                'trust' => $state['trust'],
                'verify' => $this->verify($state, $arguments['authorization_id'], $now),
                'issue' => $this->issue($state, $arguments['authorization_id'], $now),
                'consume' => $this->consume($state, $arguments['capability'], $now),
                'control' => $this->control($state, $arguments['payload'], $arguments['signature'], $now),
                'status' => $this->status($state, $arguments['authorization_id']),
                'prepare' => $ceremony->prepare($state,$arguments['mission'],$arguments['disclosures'],$now),
                'export' => $ceremony->export($state,$arguments['challenge_id'],$now),
                'submit' => $ceremony->submit($state,$arguments['challenge_id'],$arguments['signature'],$now),
                'derive' => $ceremony->derive($state,$arguments['challenge_id'],$now),
                'challenge-status' => $ceremony->status($state,$arguments['challenge_id'],$now),
            };
        });
    }

    private function verify(array $state, string $id, int $now): array
    {
        $chain = $state['authorizations'][$id] ?? null;
        if (!is_array($chain)) throw new \RuntimeException('PMA_AUTHORITY_ABSENT');
        $payload = $chain['payload'];
        PublicTrust::verify($state['trust'], $payload, $chain['signature'], $now);
        if (($state['inactive'][$id] ?? false) !== false || $now >= $payload['expires_at']
            || ($state['current'][$payload['mission_id']] ?? null) !== $id) throw new \RuntimeException('PMA_AUTHORITY_INACTIVE');
        $d = $chain['dossier']; $r = $chain['review']; $a = $chain['authorization'];
        $preview=$r; $authenticity=$preview['operator_authenticity'] ?? null;
        unset($preview['operator_authenticity'],$preview['record_digest']);
        $preview['record_digest']=hash('sha256',CanonicalJson::encode($preview));
        if (!is_array($authenticity) || $authenticity['signature']!==$chain['signature']
            || $authenticity['payload_digest']!==hash('sha256',CanonicalJson::encode($payload))
            || $authenticity['challenge_id']!==$payload['challenge_id']
            || $authenticity['trust_fingerprint']!==$state['trust']['fingerprint']) throw new \RuntimeException('PMA_REVIEW_AUTHENTICITY_ABSENT');
        foreach ([$d, $r, $a] as $record) {
            $digest = $record['record_digest'] ?? ''; unset($record['record_digest']);
            if (!hash_equals($digest, hash('sha256', CanonicalJson::encode($record)))) throw new \RuntimeException('PMA_CHAIN_INVALID');
        }
        if (CanonicalJson::encode($d) !== CanonicalJson::encode($payload['dossier'])
            || CanonicalJson::encode($preview) !== CanonicalJson::encode($payload['review_preview'])
            || $id !== $a['authorization_id']
            || !$this->same($a['authority_source']['dossier'], ['id'=>$d['dossier_id'],'version'=>$d['dossier_version'],'digest'=>$d['record_digest']])
            || !$this->same($a['authority_source']['imperator_review'], ['id'=>$r['review_id'],'digest'=>$r['record_digest']])
            || $a['authority_source']['derivation_authority_id'] !== $r['mission_authorization_derivation_authority']['authority_id']
            || $a['authorized_dossier_lines'] !== $d['lines'] || $a['mission_plan'] !== $d['mission_plan']
            || $a['authorized_resource_demands'] !== ($d['resource_demands'] ?? [])
            || $a['authorized_disclosures'] !== ($d['disclosures'] ?? [])
            || $a['authorized_proposed_model_bindings'] !== ($d['proposed_model_bindings'] ?? [])
            || $r['actor']['id'] !== $payload['operator_identity'] || $r['disposition'] !== 'APPROVE_DOSSIER'
            || $r['all_lines_acknowledged'] !== true || $r['dossier_approval'] !== true) throw new \RuntimeException('PMA_CHAIN_INVALID');
        return $chain;
    }

    private function issue(array &$state, string $id, int $now): array
    {
        $chain = $this->verify($state, $id, $now); $payload = $chain['payload'];
        $mission = $payload['mission_id'];
        if (in_array($state['lifecycles'][$mission]['state'] ?? '', ['COMPLETED','FAILED','CANCELLED'], true)) throw new \RuntimeException('PMA_TERMINAL');
        $preparation=$chain['authorization']['preparation_authorities'];
        foreach (['model_binding_sealing','profile_model_access_attestation','personnel_preparation','tool_credential_data_preparation'] as $kind) {
            if (!in_array($preparation[$kind] ?? null,[null,[]],true)) throw new \RuntimeException('PMA_PREPARATION_REQUIRED');
        }
        $derivation=$preparation['execution_commission_derivation'] ?? [];
        if (($derivation['authority_single_use'] ?? false)!==true || ($derivation['preparation_authority'] ?? false)!==true) throw new \RuntimeException('PMA_COMMISSION_DERIVATION_ABSENT');
        if (!isset($state['commissions'][$id])) {
            $state['commissions'][$id]=['commission_id'=>'protected-git-commission-'.hash('sha256',$id.'|'.$derivation['authority_id']),
                'source_authorization_id'=>$id,'source_authorization_digest'=>$chain['authorization']['record_digest'],
                'source_derivation_authority_id'=>$derivation['authority_id'],'derivation_consumed'=>true,
                'executor'=>'protected-git-inspector','task'=>'READ_EXACT_GIT_OBJECTS','target'=>$payload['target'],
                'paths'=>$payload['paths'],'budget'=>$payload['budget'],'expires_at'=>$payload['expires_at'],'delegation_permitted'=>false];
        }
        if (!isset($state['issuer_secret'])) {
            $pair = sodium_crypto_sign_keypair();
            $state['issuer_secret'] = base64_encode(sodium_crypto_sign_secretkey($pair));
            $state['issuer_public'] = base64_encode(sodium_crypto_sign_publickey($pair));
            sodium_memzero($pair);
        }
        $capabilities = [];
        foreach ($payload['transitions'] as $transition) {
            $capability = ['authorization_id'=>$id,'authorization_digest'=>$chain['authorization']['record_digest'],
                'commission_id'=>$state['commissions'][$id]['commission_id'],
                'dossier_digest'=>$chain['dossier']['record_digest'],'mission_id'=>$mission,
                'actor'=>$transition['actor'],'target'=>$payload['target'],'issuer'=>$state['issuer_public'],
                'action'=>$transition['action'],'from'=>$transition['from'],'to'=>$transition['to'],
                'expires_at'=>$payload['expires_at'],'nonce'=>bin2hex(random_bytes(24))];
            $sig = sodium_crypto_sign_detached(CanonicalJson::encode($capability), base64_decode($state['issuer_secret'], true));
            $envelope = ['payload'=>$capability,'signature'=>base64_encode($sig)];
            $state['issued'][$capability['nonce']] = hash('sha256',CanonicalJson::encode($envelope));
            $capabilities[] = $envelope;
        }
        return ['capabilities'=>$capabilities,'verification_key'=>$state['issuer_public']];
    }

    private function consume(array &$state, array $envelope, int $now): array
    {
        $capability = $envelope['payload'] ?? [];
        $id = $capability['authorization_id'] ?? '';
        $chain = $this->verify($state, $id, $now);
        $key=base64_decode($state['issuer_public'] ?? '',true); $sig=base64_decode($envelope['signature'] ?? '',true);
        $nonce=$capability['nonce'] ?? '';
        if (!is_string($key) || strlen($key)!==32 || !is_string($sig) || strlen($sig)!==64
            || !sodium_crypto_sign_verify_detached($sig,CanonicalJson::encode($capability),$key)
            || ($state['issued'][$nonce] ?? '') !== hash('sha256',CanonicalJson::encode($envelope))) throw new \RuntimeException('PMA_CAPABILITY_INVALID');
        $payload=$chain['payload']; $mission=$payload['mission_id'];
        $transition=['action'=>$capability['action'],'actor'=>$capability['actor'],'from'=>$capability['from'],'to'=>$capability['to']];
        if ($capability['mission_id']!==$mission || $capability['authorization_digest']!==$chain['authorization']['record_digest']
            || ($capability['commission_id'] ?? null)!==($state['commissions'][$id]['commission_id'] ?? null)
            || $capability['dossier_digest']!==$chain['dossier']['record_digest'] || $capability['target']!==$payload['target']
            || $capability['issuer']!==$state['issuer_public'] || $capability['expires_at']!==$payload['expires_at']
            || !in_array(CanonicalJson::encode($transition),array_map(CanonicalJson::encode(...),$payload['transitions']),true)) throw new \RuntimeException('PMA_CAPABILITY_BINDING_INVALID');
        $record=$state['lifecycles'][$mission] ?? ['state'=>'AUTHORIZED','history'=>[],'consumed_nonces'=>[]];
        if (isset($record['consumed_nonces'][$nonce])) throw new \RuntimeException('PMA_REPLAY');
        if (in_array($record['state'],['COMPLETED','FAILED','CANCELLED'],true)) throw new \RuntimeException('PMA_TERMINAL');
        if ($record['state']!==$capability['from']) throw new \RuntimeException('PMA_REQUIRED_STATE');
        if ($capability['action']==='inspect') {
            $state['inspections'][$mission]=InspectionProcess::run($this->root,$payload);
            $now=time();
            PublicTrust::verify($state['trust'],$payload,$chain['signature'],$now);
            if ($now >= $payload['expires_at']) throw new \RuntimeException('PMA_AUTHORITY_INACTIVE');
        }
        if ($capability['to']==='COMPLETED') {
            if (!isset($state['inspections'][$mission])) throw new \RuntimeException('PMA_INSPECTION_EVIDENCE_ABSENT');
            $snapshot=$state['inspections'][$mission];
            if ($snapshot['commit_id']!==$payload['target']['commit'] || $snapshot['tree_id']!==$payload['target']['tree']) throw new \RuntimeException('PMA_INSPECTION_EVIDENCE_INVALID');
            $state['receipts'][$mission]=['authorization_id'=>$id,'mission_id'=>$mission,'dossier_digest'=>$chain['dossier']['record_digest'],
                'commission_id'=>$capability['commission_id'],
                'trust_fingerprint'=>$state['trust']['fingerprint'],'trust_root_id'=>hash('sha256',$this->root),'operator_identity'=>$state['trust']['identity'],
                'deployment_isolation_claimed'=>false,'snapshot'=>$snapshot,'completed_at'=>$now];
        }
        $record['state']=$capability['to']; $record['consumed_nonces'][$nonce]=true;
        $record['history'][]=['capability'=>$envelope,'consumed_at'=>$now];
        $state['lifecycles'][$mission]=$record;
        return $record;
    }

    private function control(array &$state, array $payload, string $signature, int $now): array
    {
        PublicTrust::verify($state['trust'],$payload,$signature,$now);
        if (($payload['schema'] ?? '')!=='imperium.protected-control/v1' || !is_int($payload['expires_at'] ?? null)
            || $now >= $payload['expires_at'] || !preg_match('/^[a-f0-9]{48}$/',$payload['nonce'] ?? '')
            || isset($state['control_nonces'][$payload['nonce']])) throw new \RuntimeException('PMA_CONTROL_INVALID');
        $action=$payload['action'] ?? '';
        if ($action==='revoke-trust') $state['trust']['revoked']=true;
        elseif ($action==='cancel-challenge' && isset($state['pending'][$payload['challenge_id'] ?? ''])) $state['pending'][$payload['challenge_id']]['status']='CANCELLED';
        elseif ($action==='rotate-trust') {
            $state['trust']=PublicTrust::validate($payload['new_trust'] ?? [],$payload['new_fingerprint'] ?? '');
            foreach ($state['authorizations'] ?? [] as $aid=>$unused) $state['inactive'][$aid]='trust-rotated';
            foreach ($state['pending'] ?? [] as $cid=>$unused) $state['pending'][$cid]['status']='SUPERSEDED';
        }
        elseif (in_array($action,['revoke','supersede','cancel'],true) && isset($state['authorizations'][$payload['authorization_id'] ?? ''])) {
            $state['inactive'][$payload['authorization_id']]=$action;
        } else throw new \RuntimeException('PMA_CONTROL_INVALID');
        $state['control_nonces'][$payload['nonce']]=true;
        return ['status'=>$action,'execution_authority'=>false];
    }

    private function status(array $state, string $id): array
    {
        if (!isset($state['authorizations'][$id])) throw new \RuntimeException('PMA_AUTHORITY_ABSENT');
        $mission=$state['authorizations'][$id]['payload']['mission_id'];
        return ['authorization_id'=>$id,'inactive'=>$state['inactive'][$id] ?? false,
            'lifecycle'=>$state['lifecycles'][$mission] ?? ['state'=>'AUTHORIZED','history'=>[],'consumed_nonces'=>[]],
            'receipt'=>$state['receipts'][$mission] ?? null,
            'execution_authority'=>false];
    }

    private function same(mixed $a, mixed $b): bool { return CanonicalJson::encode($a) === CanonicalJson::encode($b); }

    /** One journal frame publishes the complete authority state under a common file lock.
     * Partial final frames are ignored. The next writer removes only that incomplete tail.
     * fflush/fsync precede successful return; volume/controller power-loss behavior is unproved.
     */
    private function transaction(callable $operation, bool $bootstrap = false): array
    {
        if (!$bootstrap && !is_file($this->root.'/authority.journal')) throw new \RuntimeException('PMA_TRUST_ABSENT');
        $handle = fopen($this->root.'/authority.journal', 'c+b');
        if ($handle === false || !flock($handle, LOCK_EX)) throw new \RuntimeException('PMA_LOCK_FAILED');
        try {
            $state = []; $valid = 0;
            while (($header = fgets($handle, 100)) !== false) {
                if (!preg_match('/^([0-9]{1,9}) ([a-f0-9]{64})\n$/D', $header, $match)) break;
                $length = (int)$match[1];
                if ($length > 16000000) throw new \RuntimeException('PMA_JOURNAL_LIMIT');
                $bytes = ''; while (strlen($bytes) < $length && !feof($handle)) $bytes .= fread($handle, $length - strlen($bytes));
                if (strlen($bytes) !== $length) break;
                if (!hash_equals($match[2], hash('sha256', $bytes))) throw new \RuntimeException('PMA_JOURNAL_CORRUPT');
                $state = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
                $valid = ftell($handle);
            }
            if (!$bootstrap && !isset($state['trust'])) throw new \RuntimeException('PMA_TRUST_ABSENT');
            $before = CanonicalJson::encode($state);
            $result = $operation($state);
            $after = CanonicalJson::encode($state);
            if ($after !== $before) {
                if (strlen($after) > 16000000) throw new \RuntimeException('PMA_JOURNAL_LIMIT');
                ftruncate($handle, $valid); fseek($handle, $valid);
                $frame = strlen($after).' '.hash('sha256', $after)."\n".$after;
                $offset = 0;
                while ($offset < strlen($frame)) {
                    $written = fwrite($handle, substr($frame, $offset));
                    if ($written === false || $written === 0) throw new \RuntimeException('PMA_COMMIT_FAILED');
                    $offset += $written;
                }
                if (!fflush($handle) || !fsync($handle)) throw new \RuntimeException('PMA_COMMIT_FAILED');
            }
            return $result;
        } finally { flock($handle, LOCK_UN); fclose($handle); }
    }
}
