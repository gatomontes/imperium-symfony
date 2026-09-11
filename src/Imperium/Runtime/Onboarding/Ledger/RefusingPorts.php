<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Ledger;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore;
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class RefusingPorts implements PreparedOperation,SourceAuthority,CredentialCustody,ResponseCustody
{
    private function refuse(): never { throw new \RuntimeException('O2_COMPATIBLE_PRODUCER_MISSING'); }
    public function prepare(array $terms):array{$this->refuse();}
    public function verify(AuthorityStore $store,array $state,array $policy,string $effect,array $terms,array $operation):void{$this->refuse();}
    public function validateResponse(AuthorityStore $store,array $state,array $policy,string $effect,array $operation,array $envelope):string{$this->refuse();}
    public function select(AuthorityStore $store,array $state,array $policy,array $step):array{$this->refuse();}
    public function issue(array $claim,array $operation):object{$this->refuse();}
    public function consume(object $capability,array $claim,array $operation,callable $delivery):void{$this->refuse();}
    public function dispatch(array $operation,#[\SensitiveParameter] mixed $authentication):array{$this->refuse();}
    public function retain(array $envelope):void{$this->refuse();}
    public function read(array $claimRef):array{$this->refuse();}
}
