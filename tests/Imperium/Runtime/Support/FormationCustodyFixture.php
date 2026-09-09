<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal as J, FormationCognition, FormationSignatures, FormationPersonnel, FormationSessionAuthority, FormationPreparedOperation as O, FormationWireAdapter, PreparedFormationTransport, CustodiedFormationTransport};
use App\Imperium\Runtime\Clavium\{FormationClaimCustodyBroker, FormationSessionLeaseService, ProviderResponseEnvelopeService};
use App\Imperium\Runtime\LaCortine\{CredentialBroker, CredentialCapability};
use App\Imperium\Runtime\Clock;
use Symfony\Component\DependencyInjection\{ContainerBuilder, Reference};

/** Only generated roots. Real authority is produced by CitadelFormationFixture;
 * this seam substitutes only transport and credential infrastructure, never validators.
 */
final class FormationCustodyFixture
{
    public ContainerBuilder $container;
    public FormationClaimCustodyBroker $broker;
    public FormationCognition $cognition;
    public CustodiedFormationTransport $transport;
    public RecordingFormationCredentials $credentials;
    public RecordingFormationWire $wire;
    public J $journal;

    public function __construct(public string $root, Clock $clock)
    {
        self::assertRoot($root);
        $c = $this->container = new ContainerBuilder();
        foreach ([Clock::class, CredentialBroker::class, FormationWireAdapter::class] as $type) {
            $c->register($type)->setSynthetic(true)->setPublic(true);
        }
        $c->register(J::class)->setArguments([$root])->setPublic(true);
        $c->register(\App\Imperium\Runtime\Citadel\Formation\FormationInstitution::class)->setArguments([$root]);
        $c->register(ProviderResponseEnvelopeService::class)->setArguments([$root])->setPublic(true);
        $c->register(\App\Imperium\Runtime\Curia\ReceivingFormationHandoffService::class)->setArguments([$root,new Reference(J::class)]);
        foreach ([FormationSignatures::class,FormationPersonnel::class,FormationSessionAuthority::class,FormationSessionLeaseService::class,
            FormationClaimCustodyBroker::class,CustodiedFormationTransport::class,FormationCognition::class] as $type) {
            $c->register($type)->setAutowired(true)->setPublic(true);
        }
        $c->setAlias(\App\Imperium\Runtime\Citadel\Formation\BoundedFormationTransport::class,CustodiedFormationTransport::class);
        $c->compile();
        $this->credentials = new RecordingFormationCredentials($root);
        $this->wire = new RecordingFormationWire($root);
        $c->set(Clock::class,$clock); $c->set(CredentialBroker::class,$this->credentials); $c->set(FormationWireAdapter::class,$this->wire);
        $this->broker=$c->get(FormationClaimCustodyBroker::class); $this->cognition=$c->get(FormationCognition::class);
        $this->transport=$c->get(CustodiedFormationTransport::class); $this->journal=$c->get(J::class);
    }
    public static function assertRoot(string $root): void
    {
        $resolved=realpath($root); $temp=realpath(sys_get_temp_dir());
        if ($resolved === false || dirname(str_replace('\\','/',$resolved)) !== str_replace('\\','/',$temp)
            || !preg_match('/^imperium-citadel-proof-[a-f0-9]{24}$/D',basename($resolved))) { throw new \RuntimeException('Generated custody root required'); }
    }
    public static function authorization(): array
    {
        return ['schema'=>'imperium.formation-transport-authorization/v1','adapter'=>'synthetic-formation-wire-v1',
            'credential_reference'=>'synthetic-public-reference','operation'=>'synthetic-exact-formation'];
    }
    /** Persist a real started claim, stopping before custody (no seeded claim). */
    public function pending(CitadelFormationFixture $f, string $sid, string $aid='custody-attempt-0001'): array
    {
        $capture=new CapturingFormationTransport($this->transport);
        $cognition=new FormationCognition($f->journal,$f->signatures,$f->personnel,$capture,
            $this->container->get(ProviderResponseEnvelopeService::class),$this->container->get(FormationSessionLeaseService::class),$f->clock,
            new \App\Imperium\Runtime\Curia\ReceivingFormationHandoffService($f->root,$f->journal));
        try { $cognition->call($sid,$aid); } catch (\RuntimeException $e) {
            if ($e->getMessage() !== 'CMF059_OUTCOME_UNKNOWN_NO_RETRY') { throw $e; }
        }
        if ($capture->call === null) { throw new \RuntimeException('Genuine claim capture failed'); }
        return $capture->call;
    }
    public function counts(): array
    {
        $path=$this->root.'/custody-effects.log';
        $counts=['issue'=>0,'consume'=>0,'dispatch'=>0];
        foreach (is_file($path) ? file($path,FILE_IGNORE_NEW_LINES) : [] as $line) { ++$counts[$line]; }
        return $counts;
    }
}

final class CapturingFormationTransport implements PreparedFormationTransport
{
    public ?array $call=null;
    public function __construct(private CustodiedFormationTransport $inner) {}
    public function inspect(array $request,array $terms): array { return $this->inner->inspect($request,$terms); }
    public function prepareOperation(array $request,array $terms): array { return $this->inner->prepareOperation($request,$terms); }
    public function invoke(array $claim,array $request,array $terms): array {
        $this->call=[$claim,$request,$terms]; throw new \RuntimeException('Synthetic stop before custody');
    }
}

final class RecordingFormationCredentials implements CredentialBroker
{
    public ?\Closure $onIssue=null;
    public ?\Closure $onConsume=null;
    public ?\Closure $afterCallback=null;
    public bool $repeatCallback=false;
    public function __construct(private string $root) { FormationCustodyFixture::assertRoot($root); }
    public function issue(string $credentialRef,string $commissionId,string $operation,\DateTimeImmutable $expiresAt,int $maxUses=1): CredentialCapability
    {
        if ($this->onIssue) { ($this->onIssue)(); }
        file_put_contents($this->root.'/custody-effects.log',"issue\n",FILE_APPEND|LOCK_EX);
        return new CredentialCapability('synthetic-capability',$credentialRef,$commissionId,$operation,$expiresAt,$maxUses);
    }
    public function consume(CredentialCapability $capability,callable $providerOperation): mixed
    {
        if ($this->onConsume) { ($this->onConsume)(); }
        file_put_contents($this->root.'/custody-effects.log',"consume\n",FILE_APPEND|LOCK_EX);
        $result=$providerOperation('generated-only-auth-context');
        if ($this->afterCallback) { ($this->afterCallback)(); }
        if ($this->repeatCallback) { $providerOperation('generated-only-auth-context'); }
        return $result;
    }
}

final class RecordingFormationWire implements FormationWireAdapter
{
    public string $suffix='';
    public ?\Closure $onPrepare=null;
    public ?\Closure $beforeDispatch=null;
    public ?\Closure $afterDispatch=null;
    public ?\Closure $changeResult=null;
    public array $response;
    public function __construct(private string $root) { FormationCustodyFixture::assertRoot($root); $this->response=CitadelFormationFixture::understanding(); }
    public function prepare(array $request,array $terms): array
    {
        if ($this->onPrepare) { ($this->onPrepare)(); }
        if ($terms['provider'] !== 'synthetic-offline' || $terms['model'] !== 'scripted-v1' || $terms['destination'] !== 'in-process:no-network'
            || ($terms['transport'] ?? null) !== FormationCustodyFixture::authorization()) { throw new \RuntimeException('FC001_LIVE_BOUNDS_AND_ADAPTER_UNAPPROVED'); }
        $max=(new SyntheticFormationTransport())->inspect($request,$terms);
        return O::build($request,$terms,"SYNTHETIC-OFFLINE/1\r\n".CanonicalJson::encode($request).$this->suffix,$max);
    }
    public function dispatch(array $operation,mixed $authentication): array
    {
        if ($this->beforeDispatch) { ($this->beforeDispatch)(); }
        if ($authentication !== 'generated-only-auth-context' || $operation['destination'] !== 'in-process:no-network'
            || hash('sha256',base64_decode($operation['wire_bytes_base64'],true)) !== $operation['wire_sha256']) { throw new \RuntimeException('Synthetic wire mismatch'); }
        file_put_contents($this->root.'/custody-effects.log',"dispatch\n",FILE_APPEND|LOCK_EX);
        if ($this->afterDispatch) { ($this->afterDispatch)(); }
        $response=json_encode($this->response,JSON_THROW_ON_ERROR);
        $input=$operation['maximum']['input_tokens'];
        $result=['response'=>$response,'usage'=>['calls'=>1,'input_tokens'=>$input,'output_tokens'=>strlen($response),
            'cost_microusd'=>$input+strlen($response),'milliseconds'=>1], 'provider_response_id'=>'synthetic-response-sha256-'.hash('sha256',$response),
            'operation_digest'=>J::digest($operation),'provenance'=>'synthetic-formation-wire-v1'];
        return $this->changeResult ? ($this->changeResult)($result) : $result;
    }
}
