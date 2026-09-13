<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Bootstrap\OperatorRootOwnership;
use App\Imperium\Runtime\Onboarding\Deployment\{Composition,FileKeySource};
use App\Imperium\Runtime\Onboarding\Console\FixedGateway;
use App\Imperium\Runtime\Onboarding\DeepSeek\{EvidenceVerifier,MissingEvidence};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Policy};
use App\Tests\Imperium\Runtime\Support\DeepSeekFixture;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\{MockHttpClient,Response\MockResponse};

final class ProviderProductionCompositionTest extends TestCase
{
    private function provision(DeepSeekFixture $f): string
    {
        $root = $f->f->root.'/custody'; mkdir($root); mkdir($root.'/keys');
        file_put_contents($root.'/custody.lock', '');
        file_put_contents($root.'/generation', 'synthetic-generation');
        file_put_contents($root.'/keys/synthetic-generation', 'synthetic-'.bin2hex(random_bytes(16)));
        return $root;
    }

    public function testPublicGenerationDoesNotReadPrivateBytesAndFreshProcessSeesRotation(): void
    {
        $f = new DeepSeekFixture();
        try {
            $root = $this->provision($f); $keys = new FileKeySource($root);
            unlink($root.'/keys/synthetic-generation');
            self::assertSame('synthetic-generation', $keys->generation());
            try { $keys->withKey(static function(): void { self::fail('missing key delivered'); }); self::fail('missing key accepted'); }
            catch (\RuntimeException $e) { self::assertSame('O3_KEY_CUSTODY_REFUSED', $e->getMessage()); }
            file_put_contents($root.'/generation', 'rotated');
            file_put_contents($root.'/keys/rotated', 'synthetic-'.bin2hex(random_bytes(16)));
            $process = proc_open([PHP_BINARY, __DIR__.'/Support/production-custody-worker.php', $root], [1=>['pipe','w'],2=>['pipe','w']], $pipes);
            self::assertIsResource($process);
            $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $err);
            self::assertSame('rotated:delivered', $out);
        } finally { $f->close(); }
    }

    public function testCustodyHoldsRotationFenceAndRedactsCallbackFailures(): void
    {
        $f = new DeepSeekFixture();
        try {
            $root = $this->provision($f); $keys = new FileKeySource($root); $calls = 0;
            $keys->withKey(function(#[\SensitiveParameter] string $key) use ($root,$keys,&$calls): void {
                ++$calls; self::assertStringStartsWith('synthetic-', $key);
                self::assertSame('synthetic-generation', $keys->generation());
                $lock = fopen($root.'/custody.lock', 'rb');
                try { self::assertFalse(flock($lock, LOCK_EX | LOCK_NB)); } finally { fclose($lock); }
            });
            self::assertSame(1, $calls);
            try { $keys->withKey(static function(#[\SensitiveParameter] string $key): void { throw new \RuntimeException($key); }); self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('O3_KEY_CUSTODY_REFUSED', $e->getMessage()); self::assertNull($e->getPrevious()); }
            $lock = fopen($root.'/custody.lock', 'rb');
            try {
                self::assertTrue(flock($lock, LOCK_EX | LOCK_NB));
                try { $keys->generation(); self::fail('busy publisher accepted'); }
                catch (\RuntimeException $e) { self::assertSame('O3_KEY_CUSTODY_REFUSED', $e->getMessage()); }
            } finally { fclose($lock); }
        } finally { $f->close(); }
    }

    public function testMalformedGenerationAndKeyRefuseWithoutDelivery(): void
    {
        $f = new DeepSeekFixture();
        try {
            $root = $this->provision($f); $keys = new FileKeySource($root);
            foreach (['../outside', "generation\n", '', str_repeat('x',129)] as $generation) {
                file_put_contents($root.'/generation',$generation);
                try { $keys->generation(); self::fail(); } catch (\RuntimeException $e) { self::assertSame('O3_KEY_CUSTODY_REFUSED',$e->getMessage()); }
            }
            file_put_contents($root.'/generation','synthetic-generation');
            foreach (['', "synthetic-key\r\nInjected: x", str_repeat('x',4097)] as $key) {
                file_put_contents($root.'/keys/synthetic-generation',$key);
                try { $keys->withKey(static function(): void { self::fail(); }); self::fail(); }
                catch (\RuntimeException $e) { self::assertSame('O3_KEY_CUSTODY_REFUSED',$e->getMessage()); }
            }
        } finally { $f->close(); }
    }

    public function testObservationDoesNotConstructCredentialCompositionOrCreateFiles(): void
    {
        $f = new DeepSeekFixture();
        try {
            $f->ready();
            $gateway = new Composition($f->f->store,new OperatorRootOwnership($f->f->root),null,'not-absolute','absent');
            $q = $f->request(); $q['mode']='preview'; $raw=$f->f::json($q);
            $before=$f->f->head();
            self::assertSame((new FixedGateway($f->f->store))->onboard($raw),$gateway->onboard($raw));
            self::assertSame((new FixedGateway($f->f->store))->status('sequence-test'),$gateway->status('sequence-test'));
            self::assertSame($before,$f->f->head());
            self::assertFileDoesNotExist($f->f->root.'/custody');
        } finally { $f->close(); }
    }

    public function testForeignRootRefusesBeforeCustody(): void
    {
        $f = new DeepSeekFixture();
        try {
            $gateway = new Composition($f->f->store,new OperatorRootOwnership($f->f->root.'/foreign'),null,'bad','bad');
            $this->expectExceptionMessage('ROOT_OWNER_MISMATCH');
            $gateway->onboard($f->f::json($f->request()));
        } finally { $f->close(); }
    }

    public function testAdmittedSyntheticAccountIsNotProductionEvidence(): void
    {
        $f = new DeepSeekFixture();
        try {
            $root=$this->provision($f); $f->ready(); $calls=0;
            $gateway=new Composition($f->f->store,new OperatorRootOwnership($f->f->root),$f->grant,$root,$f->f->root.'/responses',
                mock:new MockHttpClient(function() use (&$calls): MockResponse { ++$calls; return new MockResponse(DeepSeekFixture::listing()); }));
            $out=$gateway->onboard($f->f::json($f->request('access')));
            self::assertSame(0,$calls); self::assertSame('REFUSED',$out['status']);
            foreach ($out['operational_flags'] as $flag) { self::assertFalse($flag); }
        } finally { $f->close(); }
    }

    public function testFixedCompositionDeliversOnlySyntheticMockAndRotationRefuses(): void
    {
        foreach ([false,true] as $rotate) {
            $f=new DeepSeekFixture();
            try {
                $root=$this->provision($f); $f->ready(); $calls=0;
                // Test-only factual oracle: explicitly synthetic, never installed in source composition.
                $evidence=new class($root,$rotate) implements EvidenceVerifier {
                    public function __construct(private string $root,private bool $rotate) {}
                    public function access(array $original,array $grant,int $now): void {
                        $v=Policy::content($original,'deepseek-account-observation');
                        R::require($v['schema']==='imperium.synthetic-deepseek-account/v1' && $v['account_scope']==='synthetic-account'
                            && R::same($v['credential_binding_ref'],$grant['executor']['credential_binding_ref'])
                            && $v['not_before']<=$now && $grant['expires_at']<=$v['expires_at'],'SYNTHETIC_ONLY');
                        if ($this->rotate) { file_put_contents($this->root.'/generation','rotated'); }
                    }
                };
                $gateway=new Composition($f->f->store,new OperatorRootOwnership($f->f->root),$f->grant,$root,$f->f->root.'/responses',access:$evidence,
                    mock:new MockHttpClient(function(string $method,string $url,array $options) use (&$calls): MockResponse {
                        ++$calls; self::assertSame('GET',$method); self::assertSame('https://api.deepseek.com/models',$url);
                        self::assertStringContainsString('Bearer synthetic-',implode(' ',$options['headers']));
                        return new MockResponse(DeepSeekFixture::listing());
                    }));
                $out=$gateway->onboard($f->f::json($f->request('access')));
                self::assertSame($rotate?0:1,$calls,json_encode($out));
                if (!$rotate) { self::assertNotSame('REFUSED',$out['status']); }
            } finally { $f->close(); }
        }
    }
}
