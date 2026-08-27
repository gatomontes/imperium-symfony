<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Cognition;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Governance\ContinuousGovernanceContext;
use App\Imperium\Runtime\Governance\GovernanceEventEnvelopeService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GovernanceCognitionRequestService
{
    private const DIRECTORY = 'var/imperium/runtime/governance-cognition-requests';
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;
    private RecordReferenceValidator $validator;
    private GovernanceEventEnvelopeService $events;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $root,
        private GovernanceCognitionAuthorityRegistry $authorities,
        ?ImmutableRecordStore $records = null,
        ?AtomicTransition $atomic = null,
        ?RecordReferenceValidator $validator = null,
        ?GovernanceEventEnvelopeService $events = null,
    ) {
        $this->atomic = $atomic ?? new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $this->atomic);
        $this->validator = $validator ?? new RecordReferenceValidator($root);
        $this->events = $events ?? new GovernanceEventEnvelopeService($root);
    }

    public function request(
        string $cluster,
        string $authorityType,
        string $authorityId,
        string $seat,
        string $purpose,
        string $inputDigest,
        array $modelRequirements,
        \DateTimeImmutable $expiresAt,
        \DateTimeImmutable $requestedAt,
    ): array {
        foreach ([$cluster, $authorityType, $seat, $purpose] as $value) {
            if (!preg_match('/^[a-z0-9][a-z0-9._-]{2,127}$/', $value)) {
                throw new \InvalidArgumentException('GCA101_GOVERNANCE_COGNITION_REQUEST_INPUT_INVALID');
            }
        }
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{2,220}$/', $authorityId)
            || !preg_match('/^[a-f0-9]{64}$/', $inputDigest)
            || $expiresAt <= $requestedAt || $expiresAt > $requestedAt->modify('+15 minutes')) {
            throw new \InvalidArgumentException('GCA101_GOVERNANCE_COGNITION_REQUEST_INPUT_INVALID');
        }
        $modelRequirements = $this->modelRequirements($modelRequirements);
        $authority = $this->authorities->resolve($cluster, $authorityType, $authorityId);
        $this->validateAuthority($authority, $cluster, $authorityType, $authorityId, $seat, $purpose, $inputDigest, $requestedAt);
        $continuousGovernance = ContinuousGovernanceContext::advisoryCognition($authority);

        $fingerprint = [$authority['source'], $cluster, $authorityType, $seat, $purpose, $inputDigest, $modelRequirements, $requestedAt->format(DATE_ATOM), $expiresAt->format(DATE_ATOM)];
        $requestId = 'governance-cognition-request-'.substr(hash('sha256', CanonicalJson::encode($fingerprint)), 0, 20);
        $record = [
            'schema' => 'imperium.governance-cognition-request/v1',
            'request_id' => $requestId,
            'instance_id' => $authority['instance_id'],
            'case_id' => $authority['case_id'],
            'case_digest' => $authority['case_digest'],
            'cluster' => $cluster,
            'authority_type' => $authorityType,
            'source_governance_authority' => $authority['source'],
            'authority_identity' => $authorityId,
            'target' => ['seat' => $seat, 'purpose' => $purpose],
            'input_digest' => $inputDigest,
            'model_requirements' => $modelRequirements,
            'continuous_governance' => $continuousGovernance,
            'requested_at' => $requestedAt->format(DATE_ATOM),
            'expires_at' => $expiresAt->format(DATE_ATOM),
            'status' => 'GOVERNANCE_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION',
            'governance_authority_single_use' => true,
            'governance_authority_consumed' => false,
            'credential_use_authority' => false,
            'network_access_authority' => false,
            'provider_invocation_authority' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ];

        $request = $this->atomic->run('gca-request-authority:'.hash('sha256', $cluster.'|'.$authorityId), function () use ($cluster, $authorityId, $requestId, $record): array {
            foreach (glob($this->root.'/'.self::DIRECTORY.'/*.json') ?: [] as $path) {
                $prior = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                if (($prior['cluster'] ?? null) !== $cluster || ($prior['authority_identity'] ?? null) !== $authorityId) {
                    continue;
                }
                if (!$this->validator->isIntact($prior) || ($prior['request_id'] ?? null) !== $requestId) {
                    throw new \RuntimeException('GCA103_GOVERNANCE_COGNITION_REQUEST_CONFLICT');
                }
                return $prior;
            }
            return $this->records->put(self::DIRECTORY, $requestId, $record);
        });
        // Historical v1 requests remain exact immutable replays. Only requests created with
        // the Batch 1 classification participate in Batch 2 event emission.
        if (isset($request['continuous_governance'])) {
            $this->events->recordGovernanceCognitionRequest($requestId);
        }

        return $request;
    }

    private function validateAuthority(array $authority, string $cluster, string $type, string $id, string $seat, string $purpose, string $inputDigest, \DateTimeImmutable $at): void
    {
        if ($cluster !== ($authority['cluster'] ?? null) || $type !== ($authority['authority_type'] ?? null)
            || $id !== ($authority['authority_id'] ?? null) || $seat !== ($authority['seat'] ?? null)
            || $purpose !== ($authority['purpose'] ?? null) || $inputDigest !== ($authority['input_digest'] ?? null)
            || !is_string($authority['source']['id'] ?? null) || !preg_match('/^[a-f0-9]{64}$/', $authority['source']['digest'] ?? '')
            || true !== ($authority['single_use'] ?? null) || true !== ($authority['exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null) || new \DateTimeImmutable((string) ($authority['expires_at'] ?? '1970-01-01')) <= $at
            || !isset($authority['instance_id'], $authority['case_id'], $authority['case_digest'])) {
            throw new \RuntimeException('GCA102_GOVERNANCE_AUTHORITY_INVALID');
        }
    }

    private function modelRequirements(array $requirements): array
    {
        if (array_keys($requirements) !== ['provider', 'model'] || 'deepseek' !== ($requirements['provider'] ?? null)
            || 'deepseek-v4-flash' !== ($requirements['model'] ?? null)) {
            throw new \InvalidArgumentException('GCA104_GOVERNANCE_MODEL_REQUIREMENTS_INVALID');
        }
        return $requirements;
    }
}
