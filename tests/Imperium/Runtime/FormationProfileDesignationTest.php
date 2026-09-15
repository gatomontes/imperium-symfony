<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\DesignationFixture as F;
use App\Imperium\Runtime\Citadel\Formation\{FormationProfileDesignation as D,FormationOwnerFrame};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormationProfileDesignationTest extends TestCase
{
    /** Native personnel compatibility only; no O4 application or live provider. */
    public function testV2CourtthaneThroughActualFormationConsumers(): void
    {
        $f = new F('courtyard.courtthane');
        try {
            $native = $f->m->f;
            $f->service->publish($f->envelope(), $f->m->candidate);
            $scope = $f->m->context['citadel_id'];
            $locksmith = $native->candidate($scope, 'clavium.locksmith');
            $locksmithTerms = ['candidate' => $locksmith, 'scope' => $scope, 'seat' => 'clavium.locksmith', 'generation' => 1];
            $native->personnel->appointLocksmith($locksmith, $native->sign('APPOINT_FORMATION_LOCKSMITH', $locksmithTerms));
            $terms = ['candidate' => $f->m->candidate, 'scope' => $scope, 'seat' => 'courtyard.courtthane', 'generation' => 1];
            $holder = $native->personnel->appointCourtthane($f->m->candidate, $native->sign('APPOINT_COURTTHANE', $terms));
            $id = $native->receive()['intake_id'];
            self::assertSame(\App\Imperium\Runtime\Citadel\Formation\FormationJournal::digest($holder), $native->cognition->authorizationSource($id, 'interview')['holder_digest']);
            $native->understand($id);
            $draft = $native->draft($id);
            self::assertSame($holder, $draft['claim']['holder']);
            $review = $native->approve($native->present($id));
            $native->formation->reserve($review['review_id']);
            $handoff = $native->formation->deliver($id);
            self::assertFalse($handoff['execution_authority']);
            self::assertSame($handoff, $native->formation->deliver($id));
            $session = $native->grant($id, 'acceptance');
            $native->transport->response = ['disposition' => 'ACCEPTED', 'rationale' => 'Exact synthetic mandate understood.', 'gaps' => '', 'dissent' => 'No live execution.'];
            $native->cognition->call($session, 'v2-acceptance-0001');
            self::assertSame('STEP_1_SCHEMA_AND_FOREIGN_REFERENCES_VALIDATED_NO_EXECUTION', $native->formation->validateStepOne($id)['status']);
            self::assertCount(3, $native->transport->calls);
            $nonce = $f->m->authorization['body']['decision']['payload']['nonce'];
            $native->signatures->revoke($native->sign('REVOKE_DECISION', ['nonce' => $nonce]), $nonce);
            $head = $f->m->head();
            foreach ([fn() => $native->cognition->authorizationSource($id, 'drafting'), fn() => $native->formation->validateStepOne($id)] as $current) {
                try { $current(); self::fail('Revoked native model preparation must refuse'); }
                catch (\RuntimeException $e) { self::assertStringContainsString('CMF022', $e->getMessage()); }
            }
            self::assertSame($head, $f->m->head());
            self::assertCount(3, $native->transport->calls);
        } finally { $f->close(); }
    }

    public static function histories():iterable { foreach(D::TARGETS as $seat) { foreach(['live','revoked','expired'] as $terminal) { yield [$seat,$terminal]; } } }
    #[DataProvider('histories')]
    public function testNativeHistorySuccessorAndIndependentAppointment(string $seat,string $terminal):void
    {
        $f=new F($seat);
        try {
            $e=$f->envelope(); $first=$f->service->publish($e,$f->m->candidate);
            self::assertSame($first,$f->current()['event']); $head=$f->m->head();
            self::assertSame($first,$f->service->publish($e,$f->m->candidate)); self::assertSame($head,$f->m->head());
            $last=$first;
            if($terminal==='revoked') { $last=$f->service->publish($f->envelope($first,D::REVOKE),$f->m->candidate); $this->refuses(fn()=>$f->current()); }
            if($terminal==='expired') { $f->successor(); $f->m->f->clock->at+=201; $this->refuses(fn()=>$f->current()); }
            if($terminal!=='expired') { $f->successor(); } $next=$f->service->publish($f->envelope($last),$f->m->candidate);
            self::assertSame($next,$f->current()['event']);
            self::assertSame($terminal==='revoked'?3:2,$next['envelope']['payload']['designation_generation']);
            self::assertSame(1,$next['envelope']['payload']['binding_generation']);
            self::assertSame($terminal==='live'?['superseded','current_active']:($terminal==='expired'?['expired','current_active']:['current_active']),array_column(array_column($next['attestations'],'transition'),'to'));
            $native=$f->m->f; $state=$native->journal->read()['state']; $role=$seat===D::TARGETS[0]?'courtthane':'locksmith';
            self::assertArrayNotHasKey($role,$state);
            $terms=['candidate'=>$f->m->candidate,'scope'=>$state['citadel_id'],'seat'=>$seat,'generation'=>1];
            $holder=$role==='courtthane'?$native->personnel->appointCourtthane($f->m->candidate,$native->sign('APPOINT_COURTTHANE',$terms))
                :$native->personnel->appointLocksmith($f->m->candidate,$native->sign('APPOINT_FORMATION_LOCKSMITH',$terms));
            self::assertSame(1,$holder['generation']);
            self::assertSame($holder,$native->journal->inspect(fn(array $frame,FormationOwnerFrame $owner):array=>$role==='courtthane'?$native->personnel->currentCourtthaneInOwner($owner):$native->personnel->currentLocksmithInOwner($owner)));
            if($dest=getenv('PPC6_PUBLIC_EVIDENCE')) {
                $dir=$dest.'/originals/'.$seat.'-'.$terminal; if(!is_dir($dir)) { mkdir($dir,0770,true); }
                foreach(['citadel/formation','operator-root/installations','operator-root/packages','offices/garrison/occupancy','offices/guildhall/occupancy','offices/laboratorium/occupancy','offices/senate/occupancy','offices/conscription/occupancy'] as $path) {
                    $out=$dir.'/'.$path; if(!is_dir($out)) { mkdir($out,0770,true); }
                    foreach(glob($native->root.'/var/imperium/'.$path.'/*.json')?:[] as $file) { copy($file,$out.'/'.basename($file)); }
                }
            }
        } finally { $f->close(); }
    }
    public static function refusals():iterable { foreach(D::TARGETS as $seat) { foreach(['nonce','generation','binding','original','signature','purpose','index','expired'] as $kind) { yield [$seat,$kind]; } } }
    #[DataProvider('refusals')]
    public function testRefusesWithoutPublication(string $seat,string $kind):void
    {
        $f=new F($seat);
        try {
            $e=$f->envelope();
            if($kind==='nonce') { $f->service->publish($e,$f->m->candidate); $e['payload']['reason']='Changed replay'; }
            if($kind==='generation') { $e['payload']['designation_generation']=2; }
            if($kind==='binding') { $e['payload']['binding_generation']=2; }
            if($kind==='original') { $e['payload']['approval_ref']['schema']='foreign'; }
            if($kind==='purpose') { $e['payload']['delegation_ref']=$f->delegations[D::REVOKE]; }
            $e=$f->sign($e['payload']);
            if($kind==='signature') { $e['signature']=base64_encode(str_repeat('x',64)); }
            if($kind==='index') { $f->service->publish($e,$f->m->candidate); $f->m->f->journal->change(function(array &$s):void { $s['profile_designations']['current']=[]; }); }
            if($kind==='expired') { $f->m->f->clock->at+=201; }
            $head=$f->m->head(); $this->refuses(fn()=>$f->service->publish($e,$f->m->candidate)); self::assertSame($head,$f->m->head());
        } finally { $f->close(); }
    }
    public static function historicalCorruptions():iterable { foreach(D::TARGETS as $seat) { foreach(['finding','seal','oversized'] as $kind) { yield [$seat,$kind]; } } }
    #[DataProvider('historicalCorruptions')]
    public function testCompleteHistoricalOriginalsRemainRequired(string $seat,string $kind):void
    {
        $f=new F($seat);
        try {
            $oldCandidate=$f->m->candidate; $oldSeal=$f->m->seal['id'];
            $first=$f->service->publish($f->envelope(),$oldCandidate); $f->successor();
            $f->service->publish($f->envelope($first),$f->m->candidate);
            $f->m->f->journal->change(function(array &$s)use($kind,$oldCandidate,$oldSeal):void {
                if($kind==='seal') { $s['model_preparation']['seals'][$oldSeal]['body']['profile']['content_digest']='sha256:'.str_repeat('0',64); }
                else {
                    $id=$s['personnel_evidence'][$oldCandidate['examination']]['payload']['content']['findings']['security'];
                    $s['personnel_evidence'][$id]['payload']['content']['rationale']=$kind==='oversized'?str_repeat('x',D::MAX_PERSONNEL_BYTES+1):'Mutated historical original';
                }
            });
            $head=$f->m->head(); $this->refuses(fn()=>$f->current()); self::assertSame($head,$f->m->head());
        } finally { $f->close(); }
    }
    public static function targetSeats():iterable { foreach(D::TARGETS as $seat) { yield [$seat]; } }
    #[DataProvider('targetSeats')]
    public function testExpiredRevocationPreservesExpiryAndRecordsRevocation(string $seat):void
    {
        $f=new F($seat);
        try {
            $first=$f->service->publish($f->envelope(),$f->m->candidate); $f->m->f->clock->at+=201;
            $envelope=$f->envelope($first,D::REVOKE); $event=$f->service->publish($envelope,$f->m->candidate);
            self::assertSame(['expired','revoked'],array_column(array_column($event['attestations'],'transition'),'to'));
            self::assertSame($event['attestations'][0]['attestation_id'],$event['attestations'][1]['transition']['prior_attestation_id']);
            $head=$f->m->head();$this->refuses(fn()=>$f->current());self::assertSame($event,$f->service->publish($envelope,$f->m->candidate));self::assertSame($head,$f->m->head());
        } finally { $f->close(); }
    }
    private function refuses(callable $call):void { try { $call(); self::fail('Expected refusal'); } catch(\RuntimeException $e) { self::assertNotSame('',$e->getMessage()); } }
}
