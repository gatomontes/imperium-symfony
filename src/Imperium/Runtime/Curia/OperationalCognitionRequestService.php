<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalCognitionRequestService
{
    private const string AUTHORIZATIONS = 'var/imperium/offices/curia/bounded-execution-authorizations';
    private const string REQUESTS = 'var/imperium/offices/curia/operational-cognition-requests';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $root,
        ?RecordReferenceValidator $validator = null,
        ?ImmutableRecordStore $records = null,
        ?AtomicTransition $atomic = null,
    ) {
        $this->validator = $validator ?? new RecordReferenceValidator($root);
        $this->atomic = $atomic ?? new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $this->atomic);
    }

    public function request(
        string $authorizationId,
        array $modelRequirements,
        int $iteration,
        array $stopConditions,
        \DateTimeImmutable $expiresAt,
        \DateTimeImmutable $requestedAt,
    ): array {
        if (!preg_match('/^bounded-execution-authorization-[a-f0-9]{20}$/', $authorizationId)) {
            throw new \InvalidArgumentException('OCA100_BOUNDED_EXECUTION_AUTHORIZATION_ID_INVALID');
        }
        $modelRequirements = $this->modelRequirements($modelRequirements);
        $stopConditions = $this->stopConditions($stopConditions);
        if (1 !== $iteration || $expiresAt <= $requestedAt || $expiresAt > $requestedAt->modify('+15 minutes')) {
            throw new \InvalidArgumentException('OCA101_OPERATIONAL_COGNITION_REQUEST_SCOPE_INVALID');
        }

        $authorization = $this->validator->read(
            $this->root.'/'.self::AUTHORIZATIONS.'/'.$authorizationId.'.json',
            'OCA102_BOUNDED_EXECUTION_AUTHORIZATION_ABSENT',
        );
        $this->validateAuthorization($authorizationId, $authorization);

        $fingerprint = [
            $authorizationId,
            $authorization['record_digest'],
            $authorization['input_digest'],
            $modelRequirements,
            $iteration,
            $stopConditions,
            $requestedAt->format(DATE_ATOM),
            $expiresAt->format(DATE_ATOM),
        ];
        $requestId = 'operational-cognition-request-'.substr(hash('sha256', CanonicalJson::encode($fingerprint)), 0, 20);
        $cognitionAuthorityId = 'operational-cognition-authority-'.substr(hash('sha256', CanonicalJson::encode([$requestId, $authorizationId, $authorization['record_digest']])), 0, 20);
        $profileModelRequirementsDigest = hash('sha256', CanonicalJson::encode([
            'profile_candidate' => $authorization['profile_candidate'],
            'model_requirements' => $modelRequirements,
        ]));

        $record = [
            'schema' => 'imperium.curia-operational-cognition-request/v1',
            'request_id' => $requestId,
            'instance_id' => $authorization['instance_id'],
            'case_id' => $authorization['case_id'],
            'case_digest' => $authorization['case_digest'],
            'source_bounded_execution_authorization' => ['id' => $authorizationId, 'digest' => $authorization['record_digest']],
            'source_custody_transition' => $authorization['source_custody_transition'],
            'source_binding' => $authorization['source_binding'],
            'authorizer' => $authorization['authorizer'],
            'target' => [
                'seat' => $authorization['seat'],
                'manifestation_id' => $authorization['manifestation_id'],
                'binding_id' => $authorization['source_binding']['id'],
                'binding_digest' => $authorization['source_binding']['digest'],
                'custody_id' => $authorization['operational_custody']['id'],
                'custody_digest' => $authorization['operational_custody']['digest'],
            ],
            'bounded_execution_authorization_identity' => $authorizationId,
            'bounded_execution_authorization_digest' => $authorization['record_digest'],
            'input_digest' => $authorization['input_digest'],
            'profile_model_requirements_digest' => $profileModelRequirementsDigest,
            'model_requirements' => $modelRequirements,
            'iteration' => $iteration,
            'stop_conditions' => $stopConditions,
            'requested_at' => $requestedAt->format(DATE_ATOM),
            'expires_at' => $expiresAt->format(DATE_ATOM),
            'status' => 'OPERATIONAL_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION',
            'cognition_authority' => true,
            'cognition_authority_id' => $cognitionAuthorityId,
            'cognition_authority_single_use' => true,
            'cognition_authority_consumed' => false,
            'credential_use_authority' => false,
            'network_access_authority' => false,
            'provider_invocation_authority' => false,
            'execution_continuation_authority' => false,
            'sealed' => true,
        ];

        return $this->atomic->run('oca-request-source:'.$authorizationId, function () use ($authorizationId, $requestId, $record): array {
            foreach (glob($this->root.'/'.self::REQUESTS.'/*.json') ?: [] as $path) {
                $prior = $this->validator->read($path, 'OCA104_OPERATIONAL_COGNITION_REQUEST_CONFLICT');
                if (($prior['source_bounded_execution_authorization']['id'] ?? null) !== $authorizationId) {
                    continue;
                }
                if (($prior['request_id'] ?? null) !== $requestId) {
                    throw new \RuntimeException('OCA104_OPERATIONAL_COGNITION_REQUEST_CONFLICT');
                }

                return $prior;
            }

            return $this->records->put(self::REQUESTS, $requestId, $record);
        });
    }

    private function modelRequirements(array $requirements): array
    {
        if (array_keys($requirements) !== ['provider', 'model', 'capabilities']
            || !is_string($requirements['provider']) || !preg_match('/^[a-z0-9][a-z0-9._-]{1,63}$/', $requirements['provider'])
            || !is_string($requirements['model']) || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._\/-]{1,127}$/', $requirements['model'])
            || !is_array($requirements['capabilities']) || !array_is_list($requirements['capabilities']) || [] === $requirements['capabilities']) {
            throw new \InvalidArgumentException('OCA103_MODEL_REQUIREMENTS_INVALID');
        }
        foreach ($requirements['capabilities'] as $capability) {
            if (!is_string($capability) || !preg_match('/^[a-z0-9][a-z0-9._-]{1,63}$/', $capability)) {
                throw new \InvalidArgumentException('OCA103_MODEL_REQUIREMENTS_INVALID');
            }
        }
        $requirements['capabilities'] = array_values(array_unique($requirements['capabilities']));
        sort($requirements['capabilities'], SORT_STRING);

        return $requirements;
    }

    private function stopConditions(array $conditions): array
    {
        if (!array_is_list($conditions) || [] === $conditions) {
            throw new \InvalidArgumentException('OCA101_OPERATIONAL_COGNITION_REQUEST_SCOPE_INVALID');
        }
        foreach ($conditions as $condition) {
            if (!is_string($condition) || '' === trim($condition) || strlen($condition) > 240) {
                throw new \InvalidArgumentException('OCA101_OPERATIONAL_COGNITION_REQUEST_SCOPE_INVALID');
            }
        }

        return array_map('trim', $conditions);
    }

    private function validateAuthorization(string $authorizationId, array $authorization): void
    {
        if (!$this->validator->isIntact($authorization)
            || 'imperium.curia-bounded-execution-authorization/v1' !== ($authorization['schema'] ?? null)
            || $authorizationId !== ($authorization['authorization_id'] ?? null)
            || 'BOUNDED_EXECUTION_AUTHORIZED_PENDING_ONE_ITERATION' !== ($authorization['status'] ?? null)
            || true !== ($authorization['bounded_execution_authority'] ?? null)
            || true !== ($authorization['bounded_execution_authority_exercisable'] ?? null)
            || 1 !== ($authorization['maximum_iterations'] ?? null)
            || true === ($authorization['credentials_authority'] ?? null)
            || true === ($authorization['network_access_authority'] ?? null)
            || true === ($authorization['external_action_authority'] ?? null)
            || !isset($authorization['input_digest'], $authorization['profile_candidate'], $authorization['source_binding']['id'], $authorization['source_binding']['digest'])) {
            throw new \RuntimeException('OCA105_BOUNDED_EXECUTION_AUTHORIZATION_INVALID');
        }
    }
}
