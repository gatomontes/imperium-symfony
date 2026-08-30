<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PrincipalActivationDecisionAuthorityProvenanceBatch5BFixtureStore
{
    public const string SUCCESSOR_PRINCIPALS =
        'var/imperium/evidence/principal-activation-decision-authority-provenance/batch-5b-successor-principals';
    public const string PRODUCTION_ENVELOPES =
        'var/imperium/evidence/principal-activation-decision-authority-provenance/batch-5b-production-envelopes';

    private ImmutableRecordStore $records;
    private PrincipalActivationDecisionAuthorityProvenanceBatch5BValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->validator = new PrincipalActivationDecisionAuthorityProvenanceBatch5BValidator();
    }

    public function putSuccessorPrincipal(array $principal, array $source, array $transition): array
    {
        $this->validator->assertSuccessorPrincipal($principal, $source, $transition);

        return $this->records->put(self::SUCCESSOR_PRINCIPALS, $principal['principal_version_id'], $principal);
    }

    public function putProductionEnvelope(array $envelope, array $authorization, array $principal): array
    {
        $this->validator->assertProductionEnvelope($envelope, $authorization, $principal);

        return $this->records->put(self::PRODUCTION_ENVELOPES, $envelope['production_envelope_id'], $envelope);
    }
}
