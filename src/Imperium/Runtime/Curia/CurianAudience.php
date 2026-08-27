<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;

final readonly class CurianAudience
{
    private const string DEVELOPMENT_IMPERATOR_ID = 'imperator-development-root';

    public function __construct(
        private StateStore $bootstrap,
        private ProceedingStore $proceedings,
        private SeneschalCognitionGateway $seneschal,
        private CurianCognitionAuthorityService $cognitionAuthorities,
    ) {
    }

    public function open(string $request): array
    {
        $request = trim($request);
        if ('' === $request) {
            throw new \InvalidArgumentException('Imperator request cannot be empty.');
        }

        $bootstrap = $this->bootstrap->read();
        if (!is_array($bootstrap) || BootstrapState::CuriaReady->value !== ($bootstrap['state'] ?? null)) {
            throw new \RuntimeException('C01_CURIA_NOT_READY: Imperium must reach CURIA_READY before an audience opens.');
        }
        $ready = $this->lastOutput($bootstrap, 'T10');
        $runtime = $ready['runtime'] ?? null;
        if (!is_array($runtime) || true !== ($runtime['addressable'] ?? null)) {
            throw new \RuntimeException('C02_CURIA_NOT_ADDRESSABLE: readiness receipt does not expose Curia.');
        }

        $occupants = [];
        foreach (['seneschal', 'chamberlain', 'secretary'] as $role) {
            $occupant = $runtime['occupants'][$role] ?? null;
            if (!is_array($occupant) || 'active' !== ($occupant['status'] ?? null)) {
                throw new \RuntimeException('C03_SEAT_UNAVAILABLE: curia.'.$role.' is not actively occupied.');
            }
            $occupants[$role] = [
                'seat' => 'curia.'.$role,
                'manifestation_id' => $occupant['manifestation_id'],
                'occupancy_generation' => $occupant['occupancy_generation'],
            ];
        }

        $binding = $bootstrap['binding'];
        $identity = [
            'instance_id' => $binding['instance_id'],
            'manifest_id' => $binding['manifest_id'],
            'imperator_id' => self::DEVELOPMENT_IMPERATOR_ID,
            'request' => $request,
            'curian_occupancy' => $occupants,
        ];
        $proceedingId = substr(hash('sha256', CanonicalJson::encode($identity)), 0, 32);
        $existing = $this->proceedings->find($proceedingId);
        if (null !== $existing) {
            return $existing;
        }
        $context = [
            'instance_id' => $binding['instance_id'],
            'proceeding_id' => $proceedingId,
        ];
        $authority = $this->cognitionAuthorities->openAudience($request, $context, $occupants['seneschal']);
        $decision = $this->seneschal->decide($authority['authority_id'], $request, $context);
        $proceeding = [
            'schema' => 'imperium.curian-proceeding/v1',
            'proceeding_id' => $proceedingId,
            'instance_id' => $binding['instance_id'],
            'manifest_id' => $binding['manifest_id'],
            'status' => $decision['disposition'],
            'imperator_request' => [
                'actor' => ['kind' => 'imperator', 'id' => self::DEVELOPMENT_IMPERATOR_ID],
                'authority_basis' => 'development-local-cli',
                'content' => $request,
            ],
            'chamberlain' => [
                'disposition' => 'PROCEEDING_OPENED',
                'occupant' => $occupants['chamberlain'],
            ],
            'secretary' => [
                'disposition' => 'REQUEST_RECORDED',
                'occupant' => $occupants['secretary'],
            ],
            'seneschal' => [
                'disposition' => $decision['disposition'],
                'occupant' => $occupants['seneschal'],
                'decision' => $decision['decision'],
                'question' => $decision['question'],
                'mission_plan' => $decision['mission_plan'],
            ],
            'resource_demands' => $decision['resource_demands'],
            'authorization_required' => $decision['authorization_required'],
            'authorization_note' => 'No resources are authorized by opening a proceeding; planning must disclose resource demands separately.',
            'source_cognition_authority' => ['id' => $authority['authority_id'], 'digest' => $authority['record_digest']],
        ];
        $proceeding['record_digest'] = hash('sha256', CanonicalJson::encode($proceeding));

        return $this->proceedings->persist($proceeding);
    }

    private function lastOutput(array $record, string $transition): array
    {
        for ($index = count($record['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $record['events'][$index];
            if ($transition === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null)) {
                return is_array($event['output'] ?? null) ? $event['output'] : [];
            }
        }

        throw new \RuntimeException('C04_READINESS_RECEIPT_MISSING: T10 receipt is absent.');
    }
}
