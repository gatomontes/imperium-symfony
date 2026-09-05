<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\ProtectedMission\AuthorityOwner;
use App\ProtectedMission\PublicTrust;
use PHPUnit\Framework\TestCase;

final class ProtectedMissionAuthorityBatch1Test extends TestCase
{
    public function testExplicitEnrollmentAndNoCallerRootVerifierOrKeyExtraction(): void
    {
        $root=sys_get_temp_dir().'/imperium-protected-b1-'.bin2hex(random_bytes(12));
        $owner=new AuthorityOwner($root);
        $this->refuses('PMA_TRUST_ABSENT', fn()=> $owner->dispatch(['operation'=>'trust','arguments'=>[]]));
        self::assertDirectoryDoesNotExist($root);
        $pair=sodium_crypto_sign_keypair(); $public=sodium_crypto_sign_publickey($pair);
        $trust=['identity'=>'disposable-operator','competence'=>PublicTrust::COMPETENCE,'public_key'=>base64_encode($public),'not_before'=>time()-5,'expires_at'=>time()+600];
        $this->refuses('PMA_TRUST_INVALID', fn()=> $owner->enroll($trust, str_repeat('0',64)));
        self::assertDirectoryDoesNotExist($root);
        $owner->enroll($trust,hash('sha256',$public));
        $before=hash_file('sha256',$root.'/authority.journal');
        foreach (['enroll','existing','initialize','consume','replace-trust'] as $op) {
            $this->refuses('PMA_OPERATION_REFUSED', fn()=> $owner->dispatch(['operation'=>$op,'arguments'=>[]]));
        }
        foreach (['root','verifier','clock','consumer'] as $field) {
            $this->refuses('PMA_REQUEST_INVALID', fn()=> $owner->dispatch(['operation'=>'trust','arguments'=>[],$field=>'attacker']));
        }
        $this->refuses('PMA_ALREADY_ENROLLED_RECOVERY_REQUIRED', fn()=> $owner->enroll($trust,hash('sha256',$public)));
        self::assertSame($before,hash_file('sha256',$root.'/authority.journal'));
        $export=$owner->dispatch(['operation'=>'trust','arguments'=>[]]);
        self::assertSame(base64_encode($public),$export['public_key']);
        self::assertStringNotContainsString(base64_encode(sodium_crypto_sign_secretkey($pair)),file_get_contents($root.'/authority.journal'));
        self::assertFileDoesNotExist($root.'/issuer.key');
        sodium_memzero($pair);
    }

    public function testChangedTrustIdentityCompetenceAndCanonicalBytesRefuse(): void
    {
        $pair=sodium_crypto_sign_keypair(); $key=sodium_crypto_sign_publickey($pair);
        $trust=PublicTrust::validate(['identity'=>'disposable-operator','competence'=>PublicTrust::COMPETENCE,'public_key'=>base64_encode($key),'not_before'=>1,'expires_at'=>PHP_INT_MAX],hash('sha256',$key));
        $payload=['operator_identity'=>$trust['identity'],'competence'=>PublicTrust::COMPETENCE,'trust_fingerprint'=>$trust['fingerprint'],'dossier'=>'new-test-dossier'];
        $signature=base64_encode(sodium_crypto_sign_detached(\App\Bootstrap\CanonicalJson::encode($payload),sodium_crypto_sign_secretkey($pair)));
        PublicTrust::verify($trust,$payload,$signature,time()); self::assertTrue(true);
        foreach (['identity'=>'different-operator','competence'=>'READ','revoked'=>true,'public_key'=>base64_encode(random_bytes(32))] as $field=>$value) {
            $this->refuses('PMA_SIGNATURE_OR_TRUST_INVALID',fn()=>PublicTrust::verify(array_replace($trust,[$field=>$value]),$payload,$signature,time()));
        }
        $this->refuses('PMA_SIGNATURE_OR_TRUST_INVALID',fn()=>PublicTrust::verify($trust,array_replace($payload,['dossier'=>'changed']),$signature,time()));
        sodium_memzero($pair);
    }

    private function refuses(string $expected, callable $call): void
    {
        try {$call(); self::fail('Expected refusal '.$expected);} catch (\RuntimeException $e) {self::assertSame($expected,$e->getMessage());}
    }
}
