<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\DeepSeek;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R};
use App\Imperium\Runtime\Onboarding\Ledger\{CommandLedger,CustodyCoordinator,CredentialCustody,ResponseCustody};
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpClient\{NativeHttpClient,MockHttpClient};

/** Fixed infrastructure facade. No public claim, capability, secret or dispatch entry. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class Runtime
{
    public const CUSTODY='imperium.deepseek-fixed-key/v1';
    private CustodyCoordinator $coordinator;
    private \WeakMap $capabilities;
    private array $issued=[];
    private ?array $active=null;
    private HttpClientInterface $http;
    private \Closure $milliseconds;

    public function __construct(private readonly AuthorityStore $store,
        private readonly AccessAdapter $adapter = new AccessAdapter(),
        private readonly ?KeySource $keys = null, private readonly ?EnvelopeStore $envelopes = null,
        ?HttpClientInterface $mock = null, ?\Closure $monotonicMilliseconds = null)
    {
        // Injection is for an exact mock only; production always creates an undecorated client with no inherited defaults.
        R::require($mock === null || get_class($mock) === MockHttpClient::class,'HTTP_DECORATOR_FORBIDDEN');
        $this->http=$mock ?? new NativeHttpClient();
        $this->milliseconds=$monotonicMilliseconds ?? static fn(): int => intdiv(hrtime(true),1000000);
        $this->capabilities=new \WeakMap();
        $credentials=new class($this->issue(...),$this->consume(...)) implements CredentialCustody {
            public function __construct(private \Closure $issue,private \Closure $consume) {}
            public function issue(array $claim,array $operation): object { return ($this->issue)($claim,$operation); }
            public function consume(object $capability,array $claim,array $operation,callable $delivery): void { ($this->consume)($capability,$claim,$operation,$delivery); }
        };
        $responses=new class($this->dispatch(...),$this->retain(...),$this->read(...)) implements ResponseCustody {
            public function __construct(private \Closure $dispatch,private \Closure $retain,private \Closure $read) {}
            public function dispatch(array $operation,#[\SensitiveParameter] mixed $authentication): array { return ($this->dispatch)($operation,$authentication); }
            public function retain(array $envelope): void { ($this->retain)($envelope); }
            public function read(array $claimRef): array { return ($this->read)($claimRef); }
        };
        $ledger=new CommandLedger($store,$adapter,$adapter);
        $this->coordinator=new CustodyCoordinator($ledger,$adapter,$credentials,$responses);
    }
    public function advance(string $request): array { return $this->coordinator->advance($request); }
    public function reconcile(string $claimId): array { return $this->coordinator->reconcile($claimId); }

    private function current(array $claim,array $operation,int $stage): void
    {
        $this->store->journal->inspect(function(array $frame) use ($claim,$operation,$stage): void {
            $s=$this->store->state($frame['state']); $found=false;
            foreach ($s['claims'] as $id=>$c) {
                if (!R::same($c['record'],$claim)) { continue; }
                R::require(!$found && count($c['custody']) === $stage && R::same($c['operation']['prepared'],$operation),'CUSTODY_ORIGINAL');
                $this->coordinator->check($frame['state'],$id); $found=true;
            }
            R::require($found,'CUSTODY_ORIGINAL');
        });
    }
    private function issue(array $claim,array $operation): object
    {
        $this->current($claim,$operation,1); R::require($this->keys !== null && $this->envelopes !== null,'CUSTODY_MISSING');
        $id=R::hash($claim); R::require(!isset($this->issued[$id]),'CAPABILITY_ALREADY_ISSUED'); $this->issued[$id]=true;
        $cap=new ProcessCapability();
        $this->capabilities[$cap]=[$id,R::hash($operation),$this->keys->generation()]; return $cap;
    }
    private function consume(object $capability,array $claim,array $operation,callable $delivery): void
    {
        R::require(isset($this->capabilities[$capability]),'CAPABILITY_FOREIGN_OR_USED');
        $binding=$this->capabilities[$capability]; unset($this->capabilities[$capability]);
        R::require($this->keys !== null && $binding === [R::hash($claim),R::hash($operation),$this->keys->generation()],'CAPABILITY_SCOPE');
        $this->current($claim,$operation,2); $called=false;
        try {
            $this->keys->withKey(function(#[\SensitiveParameter] string $key) use (&$called,$binding,$claim,$operation,$delivery): void {
                R::require(!$called && $this->keys->generation() === $binding[2],'KEY_CALLBACK_OR_ROTATION'); $called=true;
                $this->current($claim,$operation,2); $this->active=[$claim,$operation,$binding[2],false];
                try { $delivery($key); } finally { $this->active=null; }
            });
            R::require($called,'KEY_CALLBACK_MISSING');
        } catch (\Throwable) { throw new \RuntimeException('O3_KEY_CUSTODY_REFUSED'); }
    }
    private function tick(): int
    {
        $tick=($this->milliseconds)(); R::require(is_int($tick) && $tick >= 0,'MONOTONIC_CLOCK'); return $tick;
    }
    private function dispatch(array $operation,#[\SensitiveParameter] mixed $authentication): array
    {
        R::require($this->active !== null && !$this->active[3] && R::same($operation,$this->active[1])
            && $this->keys->generation() === $this->active[2] && is_string($authentication),'DISPATCH_CAPABILITY');
        $this->active[3]=true; $this->current($this->active[0],$operation,3);
        // B0 connects only the access source. No public entry exposes the lower-level exchange.
        R::require($operation['method'] === 'GET','O3_B1_PRODUCER_MISSING');
        return $this->exchange($operation,$authentication);
    }
    private function exchange(array $operation,#[\SensitiveParameter] string $authentication,
        ?TokenEvidence $tokens=null,?Tariff $tariff=null): array
    {
        if ($operation['method'] === 'POST') {
            Wire::preflight($operation['wire'],$operation['model'],$tokens,$tariff,$this->store->now(),$operation['expires_at']);
        }
        $start=$this->tick(); $last=$start;
        $limit=min($operation['maximum']['milliseconds'],($operation['expires_at']-$this->store->now())*1000);
        R::require($limit > 0 && $start <= PHP_INT_MAX-$limit,'DISPATCH_DEADLINE'); $deadline=$start+$limit;
        $response=null;
        $check=function() use (&$last,$deadline,$operation): int {
            $now=$this->tick(); R::require($now >= $last && $now < $deadline && $this->store->now() < $operation['expires_at'],'TOTAL_DEADLINE'); $last=$now; return $now;
        };
        try {
            $options=Wire::options($operation,$authentication,$deadline-$check());
            $response=$this->http->request($operation['method'],$operation['destination'],$options);
            $check(); R::require($response->getStatusCode() === 200,'HTTP_OUTCOME_UNKNOWN'); $check();
            $bytes=''; $finished=false;
            foreach ($this->http->stream($response,max(0.001,($deadline-$check())/1000)) as $chunk) {
                $check(); R::require(!$chunk->isTimeout(),'HTTP_TIMEOUT');
                $content=$chunk->getContent(); R::require(strlen($content) <= 1048576-strlen($bytes),'RESPONSE_BYTE_LIMIT');
                $bytes.=$content; $check(); if ($chunk->isLast()) { $finished=true; }
            }
            $elapsed=$check()-$start; R::require($finished && !str_contains($bytes,$authentication),'RESPONSE_SCOPE');
            // JSON escaping must not hide an exact credential echo in an otherwise allowed string field.
            // This is a known-value guard, not a general detector for transformed secrets.
            $contains=function(mixed $value) use (&$contains,$authentication): bool {
                if (is_string($value)) { return str_contains($value,$authentication); }
                if (is_array($value)) { foreach ($value as $key=>$item) { if ((is_string($key) && str_contains($key,$authentication)) || $contains($item)) { return true; } } }
                return false;
            };
            R::require(!$contains(\App\Imperium\Runtime\Onboarding\AuthorityAdmission\StrictJson::decode($bytes)),'RESPONSE_SCOPE');
            $mapped=$operation['method'] === 'GET' ? ProviderResponse::listing($bytes,$elapsed)
                : ProviderResponse::cognition($bytes,$operation['model'],$tariff,$elapsed);
            foreach ($mapped['usage'] as $meter=>$n) { R::require($n <= $operation['maximum'][$meter],'RESPONSE_EXCEEDS_RESERVATION'); }
            return ['response'=>$bytes,'provider_response_id'=>$mapped['identity'], 'operation_digest'=>R::hash($operation),
                'usage'=>$mapped['usage'],'provenance'=>Wire::ADAPTER];
        } catch (\Throwable) { throw new \RuntimeException('O3_HTTP_REFUSED_OR_OUTCOME_UNKNOWN'); }
        finally { if ($response !== null) { $response->cancel(); } }
    }
    private function retain(array $envelope): void
    {
        R::require($this->envelopes !== null,'ENVELOPE_STORE_MISSING'); $this->envelopes->retain($envelope);
    }
    private function read(array $claim): array
    {
        R::require($this->envelopes !== null,'ENVELOPE_STORE_MISSING'); return $this->envelopes->read($claim);
    }
}
