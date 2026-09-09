<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\{CitadelFormationFixture,FormationCustodyFixture};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormationClaimCustodyProcessTest extends TestCase
{
    private CitadelFormationFixture $f;
    private FormationCustodyFixture $c;
    private string $sid;
    private array $workers=[];
    protected function setUp(): void
    {
        $this->f=$f=new CitadelFormationFixture(); $f->appoint(); $id=$f->receive()['intake_id'];
        $this->c=new FormationCustodyFixture($f->root,$f->clock);
        $terms=$f->terms($id,'interview'); $terms['transport']=FormationCustodyFixture::authorization();
        $this->sid=$f->grant($id,'interview',$terms);
        $call=$this->c->pending($f,$this->sid);
        file_put_contents($f->root.'/custody-worker-input.json',json_encode(['clock'=>$f->clock->at,'call'=>$call],JSON_THROW_ON_ERROR));
    }
    protected function tearDown(): void
    {
        foreach($this->workers as $process) { if(is_resource($process)) { if(proc_get_status($process)['running']) { proc_terminate($process); } proc_close($process); } }
        $this->f->close();
    }
    public function testTwoProcessesContendAtRealDurableCustodySeam(): void
    {
        $a=$this->start('race','one'); $b=$this->start('race','two'); $deadline=microtime(true)+30;
        while(!is_file($this->f->root.'/ready-one') || !is_file($this->f->root.'/ready-two')) {
            if(microtime(true)>$deadline) { self::fail('Workers did not reach barrier'); } usleep(10000);
        }
        file_put_contents($this->f->root.'/release-workers','release');
        $codes=[$this->finish($a),$this->finish($b)]; sort($codes); self::assertSame([0,2],$codes);
        self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$this->c->counts());
        self::assertSame('UNDERSTOOD',$this->c->cognition->recover($this->sid,'custody-attempt-0001')['response']['disposition']);
        self::assertSame(2,$this->finish($this->start('normal','replay')));
        self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$this->c->counts());
    }
    #[DataProvider('stages')]
    public function testAbruptProcessExitPreservesOneUseAndFullUnsettledExposure(string $stage,array $counts,?string $status,bool $envelope): void
    {
        self::assertSame(73,$this->finish($this->start($stage,'crash')),$stage);
        $attempt=$this->f->journal->read()['state']['sessions'][$this->sid]['attempts']['custody-attempt-0001'];
        self::assertSame($counts,$this->c->counts()); self::assertNull($attempt['settled']);
        self::assertSame($status,$attempt['custody']['status'] ?? null);
        if ($envelope) {
            $record=$this->c->cognition->recover($this->sid,'custody-attempt-0001');
            self::assertSame('UNDERSTOOD',$record['response']['disposition']);
            self::assertSame($attempt['custody']['provider_response_id'],$record['provider_response_id']);
            self::assertSame('synthetic-formation-wire-v1',$record['provider_provenance']);
        } else {
            try { $this->c->cognition->call($this->sid,'custody-attempt-0001'); self::fail('Automatic replay'); }
            catch(\RuntimeException $e) { self::assertStringContainsString('PST112',$e->getMessage()); }
        }
        if($status !== null) { self::assertSame(2,$this->finish($this->start('normal','replay'))); }
        self::assertSame($counts,$this->c->counts());
        self::assertNull($this->f->journal->read()['state']['sessions'][$this->sid]['attempts']['custody-attempt-0001']['settled']);
    }
    public static function stages(): iterable
    {
        yield 'before durable delivery'=>['before-delivery',['issue'=>0,'consume'=>0,'dispatch'=>0],null,false];
        yield 'after delivery before issue'=>['after-delivery',['issue'=>0,'consume'=>0,'dispatch'=>0],'DELIVERY_COMMITTED_OUTCOME_UNCERTAIN',false];
        yield 'after issue before consume'=>['after-issue',['issue'=>1,'consume'=>0,'dispatch'=>0],'CONSUMPTION_COMMITTED_OUTCOME_UNCERTAIN',false];
        yield 'after dispatch fence before effect'=>['before-dispatch',['issue'=>1,'consume'=>1,'dispatch'=>0],'DISPATCH_COMMITTED_OUTCOME_UNCERTAIN',false];
        yield 'after possible effect'=>['after-dispatch',['issue'=>1,'consume'=>1,'dispatch'=>1],'DISPATCH_COMMITTED_OUTCOME_UNCERTAIN',false];
        yield 'after response metadata before envelope'=>['before-envelope',['issue'=>1,'consume'=>1,'dispatch'=>1],'RESPONSE_VALIDATED_PENDING_ENVELOPE',false];
        yield 'after envelope before custody receipt'=>['after-envelope',['issue'=>1,'consume'=>1,'dispatch'=>1],'RESPONSE_VALIDATED_PENDING_ENVELOPE',true];
        yield 'after custody receipt before caller settlement'=>['after-response',['issue'=>1,'consume'=>1,'dispatch'=>1],'RESPONSE_RETAINED',true];
    }
    private function start(string $stage,string $token): int
    {
        $command=[PHP_BINARY,'-d','allow_url_fopen=0','-d','disable_functions=curl_exec,curl_multi_exec,fsockopen,pfsockopen,stream_socket_client,socket_connect',__DIR__.'/Support/formation-custody-worker.php',$this->f->root,$stage,$token];
        $pipes=[]; $p=proc_open($command,[0=>['pipe','r'],1=>['file',$this->f->root.'/stdout-'.$token,'w'],2=>['file',$this->f->root.'/stderr-'.$token,'w']],$pipes);
        if(!is_resource($p)) { self::fail('Worker launch failed'); } fclose($pipes[0]); $this->workers[]=$p; return array_key_last($this->workers);
    }
    private function finish(int $index): int
    {
        $deadline=microtime(true)+40;
        do { $status=proc_get_status($this->workers[$index]); if(!$status['running']) { return $status['exitcode']; }
            if(microtime(true)>$deadline) { self::fail('Worker timeout'); } usleep(10000);
        } while(true);
    }
}
