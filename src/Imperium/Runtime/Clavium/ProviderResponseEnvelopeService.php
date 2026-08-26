<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderResponseEnvelopeService
{
    private const DIRECTORY = 'var/imperium/runtime/provider-response-envelopes';

    private ImmutableRecordStore $records;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $root,
        ?AtomicTransition $atomic = null,
        ?ImmutableRecordStore $records = null,
    ) {
        $atomic ??= new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $atomic);
    }

    public function seal(array $claim, string $response, \DateTimeImmutable $at): array
    {
        $claimId = $claim['claim_id'] ?? null;
        $claimDigest = $claim['record_digest'] ?? null;
        if (!is_string($claimId) || !preg_match('/^(?:provider-invocation|operational-cognition-invocation-claim)-[a-f0-9]{20}$/', $claimId)
            || !is_string($claimDigest) || 64 !== strlen($claimDigest)) {
            throw new \RuntimeException('CLV430_PROVIDER_RESPONSE_ENVELOPE_CLAIM_INVALID');
        }

        return $this->records->put(self::DIRECTORY, $claimId, [
            'schema' => 'imperium.clavium-provider-response-envelope/v1',
            'envelope_id' => $claimId,
            'claim' => ['id' => $claimId, 'digest' => $claimDigest],
            'provider_response_identity' => 'sha256:'.hash('sha256', $response),
            'response' => $response,
            'credential_material_present' => false,
            'sealed_at' => $at->format(DATE_ATOM),
            'status' => 'PROVIDER_RESPONSE_ENVELOPE_SEALED_PENDING_TURN_PERSISTENCE',
            'automatic_provider_replay_permitted' => false,
            'sealed' => true,
        ]);
    }

    public function read(string $claimId): array
    {
        return $this->records->read(self::DIRECTORY, $claimId);
    }
}
