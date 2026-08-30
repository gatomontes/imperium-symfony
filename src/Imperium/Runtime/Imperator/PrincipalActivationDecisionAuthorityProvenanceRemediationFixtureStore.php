<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PrincipalActivationDecisionAuthorityProvenanceRemediationFixtureStore
{
    public const string SCOPE_GRANTS = 'var/imperium/evidence/principal-activation-decision-authority-provenance/scope-grants';
    public const string SCOPE_SUCCESSORS = 'var/imperium/evidence/principal-activation-decision-authority-provenance/scope-successors';
    public const string ISSUANCE_AUTHORIZATIONS = 'var/imperium/evidence/principal-activation-decision-authority-provenance/issuance-authorizations';

    private ImmutableRecordStore $records;
    private PrincipalActivationDecisionAuthorityProvenanceRemediationContractValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->validator = new PrincipalActivationDecisionAuthorityProvenanceRemediationContractValidator();
    }

    public function putScopeGrant(array $grant, array $sourcePrincipal, \DateTimeImmutable $at): array
    {
        $this->validator->assertScopeGrant($grant, $sourcePrincipal, $at);
        return $this->records->put(self::SCOPE_GRANTS, $grant['grant_id'], $grant);
    }

    public function putScopeSuccessor(array $successor, array $grant): array
    {
        $this->validator->assertScopeSuccessor($successor, $grant);
        return $this->records->put(self::SCOPE_SUCCESSORS, $successor['successor_transition_id'], $successor);
    }

    public function putIssuanceAuthorization(
        array $authorization,
        array $successor,
        array $activationDisposition,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        $this->validator->assertIssuanceAuthorization(
            $authorization,
            $successor,
            $activationDisposition,
            $attestation,
            $assurance,
            $boundary,
            $at,
        );
        return $this->records->put(
            self::ISSUANCE_AUTHORIZATIONS,
            $authorization['issuance_authorization_id'],
            $authorization,
        );
    }
}
