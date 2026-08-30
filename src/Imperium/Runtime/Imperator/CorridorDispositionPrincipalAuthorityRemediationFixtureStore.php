<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CorridorDispositionPrincipalAuthorityRemediationFixtureStore
{
    public const string SCOPE_GRANTS = 'var/imperium/evidence/corridor-principal-authority-remediation/scope-grants';
    public const string SCOPE_SUCCESSORS = 'var/imperium/evidence/corridor-principal-authority-remediation/scope-successors';
    public const string ISSUANCE_AUTHORIZATIONS = 'var/imperium/evidence/corridor-principal-authority-remediation/issuance-authorizations';
    private ImmutableRecordStore $records;
    private CorridorDispositionPrincipalAuthorityRemediationContractValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    { $this->records=new ImmutableRecordStore($root,new AtomicTransition($root));$this->validator=new CorridorDispositionPrincipalAuthorityRemediationContractValidator(); }

    public function putScopeGrant(array $grant,array $sourcePrincipal,\DateTimeImmutable $at): array
    { $this->validator->assertScopeGrant($grant,$sourcePrincipal,$at);return $this->records->put(self::SCOPE_GRANTS,$grant['grant_id'],$grant); }

    public function putScopeSuccessor(array $successor,array $grant): array
    { $this->validator->assertScopeSuccessor($successor,$grant);return $this->records->put(self::SCOPE_SUCCESSORS,$successor['successor_transition_id'],$successor); }

    public function putIssuanceAuthorization(array $authorization,array $issuerPrincipal,array $successor,array $activationDisposition,array $target,array $dossier,array $eligibility,\DateTimeImmutable $at): array
    { $this->validator->assertIssuanceAuthorization($authorization,$issuerPrincipal,$successor,$activationDisposition,$target,$dossier,$eligibility,$at);return $this->records->put(self::ISSUANCE_AUTHORIZATIONS,$authorization['issuance_authorization_id'],$authorization); }
}
