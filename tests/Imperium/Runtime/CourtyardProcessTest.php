<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\{CitadelFormationFixture,FormationCustodyFixture};
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class CourtyardProcessTest extends TestCase
{
    private CitadelFormationFixture $f;
    private FormationCustodyFixture $c;
    private array $workers=[];
    protected function setUp(): void { $this->f=new CitadelFormationFixture(); $this->c=new FormationCustodyFixture($this->f->root,$this->f->clock); }
    protected function tearDown(): void
    {
        foreach ($this->workers as $p) { if (is_resource($p)) { if(proc_get_status($p)['running']) {proc_terminate($p);} proc_close($p); } }
        $this->f->close();
    }
    private function session(): string
    {
        $f=$this->f; $f->appoint(); $id=$f->receive()['intake_id']; $terms=$f->terms($id,'interview');
        $terms['transport']=FormationCustodyFixture::authorization(); $terms['total']=$terms['per_call'];
        return $f->grant($id,'interview',$terms);
    }
    private function start(string $stage,string $token,string $name,string $op,array $args): int
    {
        $f=$this->f; file_put_contents($f->root.'/worker-'.$token.'.json',json_encode(['clock'=>$f->clock->at,'operation'=>$op,'arguments'=>$args],JSON_THROW_ON_ERROR));
        $cmd=[PHP_BINARY,'-d','allow_url_fopen=0','-d','disable_functions=curl_exec,curl_multi_exec,fsockopen,pfsockopen,stream_socket_client,socket_connect',__DIR__.'/Support/courtyard-worker.php',$f->root,$stage,$token,$name];
        $p=proc_open($cmd,[0=>['pipe','r'],1=>['file',$f->root.'/stdout-'.$token,'w'],2=>['file',$f->root.'/stderr-'.$token,'w']],$pipes);
        self::assertIsResource($p); fclose($pipes[0]); $this->workers[]=$p; return array_key_last($this->workers);
    }
    private function finish(int $index): int
    {
        $deadline=microtime(true)+50;
        do { $s=proc_get_status($this->workers[$index]); if(!$s['running']) {return $s['exitcode'];}
            if(microtime(true)>$deadline) {self::fail('Worker timed out');} usleep(10000);
        } while(true);
    }
    private function release(): void
    {
        $deadline=microtime(true)+35;
        while(!is_file($this->f->root.'/ready-one') || !is_file($this->f->root.'/ready-two')) {
            if(microtime(true)>$deadline) {self::fail('Workers did not reach barrier');} usleep(10000);
        }
        file_put_contents($this->f->root.'/release-workers','release');
    }
    public function testConcurrentCliAliasesShareIntakeIdentityAndGeneration(): void
    {
        $f=$this->f; $before=$f->journal->read(); file_put_contents($f->root.'/intake.txt',"Original bytes.\r\n");
        $a=$this->start('race','one','imperium:courtyard:intake','',[]); $b=$this->start('race','two','imperium:citadel:intake','',[]); $this->release();
        self::assertSame(0,$this->finish($a)); self::assertSame(0,$this->finish($b));
        $one=json_decode(file_get_contents($f->root.'/stdout-one'),true); $two=json_decode(file_get_contents($f->root.'/stdout-two'),true);
        self::assertSame($one,$two); self::assertSame($before['generation']+1,$f->journal->read()['generation']);
        self::assertCount(1,$f->journal->read()['state']['intakes']); self::assertSame($before['state']['citadel_id'],$one['citadel_id']);
        self::assertEmpty(glob($f->root.'/var/imperium/courtyard/*'));
    }
    public function testConcurrentAliasesCannotDoubleSessionExposureOrCustody(): void
    {
        $sid=$this->session();
        $a=$this->start('race','one','imperium:courtyard:formation','call',['sessionId'=>$sid,'attemptId'=>'courtyard-attempt-one']);
        $b=$this->start('race','two','imperium:citadel:formation','call',['sessionId'=>$sid,'attemptId'=>'courtyard-attempt-two']); $this->release();
        $codes=[$this->finish($a),$this->finish($b)]; sort($codes); self::assertSame([0,1],$codes);
        self::assertStringContainsString('CMF032',file_get_contents($this->f->root.'/stdout-one').file_get_contents($this->f->root.'/stdout-two'));
        $session=$this->f->journal->read()['state']['sessions'][$sid]; self::assertCount(1,$session['attempts']);
        self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$this->c->counts());
        $aid=array_key_first($session['attempts']);
        self::assertSame(0,$this->finish($this->start('normal','replay','imperium:citadel:formation','call',['sessionId'=>$sid,'attemptId'=>$aid])));
        self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$this->c->counts());
    }
    #[DataProvider('interruptions')]
    public function testInterruptedAliasKeepsFenceAndOriginalExposure(string $stage,bool $recoverable): void
    {
        $sid=$this->session(); $args=['sessionId'=>$sid,'attemptId'=>'courtyard-interrupted-01'];
        self::assertSame(73,$this->finish($this->start($stage,'crash','imperium:courtyard:formation','call',$args)));
        $old=$this->f->journal->read()['state']['sessions'][$sid]['attempts'][$args['attemptId']];
        self::assertNull($old['settled']); self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$this->c->counts());
        self::assertSame($recoverable?0:1,$this->finish($this->start('normal','replay','imperium:citadel:formation','recover-response',$args)));
        $after=$this->f->journal->read()['state']['sessions'][$sid]['attempts'][$args['attemptId']];
        self::assertSame($old['claim'],$after['claim']); self::assertNull($after['settled']); self::assertSame($old['maximum'],$after['maximum']);
        self::assertSame(1,$this->finish($this->start('normal','fresh','imperium:citadel:formation','call',['sessionId'=>$sid,'attemptId'=>'fresh-other-alias-01'])));
        self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$this->c->counts());
    }
    public static function interruptions(): iterable { yield ['after-dispatch',false]; yield ['after-envelope',true]; }
    public function testAbruptChildPublicationRecognizesOriginalReceiptAcrossAliasesAfterExpiry(): void
    {
        $f=$this->f; $f->appoint(); $id=$f->receive()['intake_id']; $f->understand($id); $f->draft($id);
        $review=$f->approve($f->present($id)); $f->run('reserve-mission',['reviewId'=>$review['review_id']]);
        self::assertSame(73,$this->finish($this->start('after-child','crash','imperium:courtyard:formation','deliver-handoff',['intakeId'=>$id])));
        $paths=glob($f->root.'/var/imperium/citadel/children/*/var/imperium/curia/handoffs/*.json'); self::assertCount(1,$paths);
        $hash=hash_file('sha256',$paths[0]); $f->clock->at+=7200;
        self::assertSame(0,$this->finish($this->start('normal','replay','imperium:citadel:formation','deliver-handoff',['intakeId'=>$id])));
        $frame=$f->journal->read();
        self::assertSame(0,$this->finish($this->start('normal','again','imperium:courtyard:formation','deliver-handoff',['intakeId'=>$id])));
        self::assertSame($frame,$f->journal->read()); self::assertSame($hash,hash_file('sha256',$paths[0]));
        self::assertCount(1,glob($f->root.'/var/imperium/citadel/children/*'));
        self::assertSame('RECOGNIZED_COMPLETED_EFFECT',$frame['state']['reservations'][$id]['reconciliation']['kind']);
        self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$this->c->counts());
    }
}
