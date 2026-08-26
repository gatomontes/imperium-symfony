<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\DeepSeekDelegateModelConfiguration;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class OperationalProviderResourceDecisionService
{
    private const string REQUESTS = 'var/imperium/offices/curia/operational-cognition-requests';
    private const string DECISIONS = 'var/imperium/imperator/operational-provider-resource-decisions';
    private const string IMPERATOR_ID = 'imperator-development-root';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private DeepSeekDelegateModelConfiguration $configuration;
    private AtomicTransition $atomic;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $root,
        ?RecordReferenceValidator $validator = null,
        ?ImmutableRecordStore $records = null,
        ?DeepSeekDelegateModelConfiguration $configuration = null,
        ?AtomicTransition $atomic = null,
    ) {
        $this->validator = $validator ?? new RecordReferenceValidator($root);
        $this->atomic = $atomic ?? new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $this->atomic);
        $this->configuration = $configuration ?? new DeepSeekDelegateModelConfiguration();
    }

    public function decide(
        string $requestId,
        string $disposition,
        string $provider,
        string $model,
        array $modelConfiguration,
        array $resourceCeiling,
        string $rationale,
        \DateTimeImmutable $expiresAt,
        \DateTimeImmutable $decidedAt,
    ): array {
        if (!preg_match('/^operational-cognition-request-[a-f0-9]{20}$/', $requestId)) {
            throw new \InvalidArgumentException('OCA200_OPERATIONAL_COGNITION_REQUEST_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $rationale = trim($rationale);
        if (!in_array($disposition, ['AUTHORIZED', 'REFUSED'], true) || '' === $rationale) {
            throw new \InvalidArgumentException('OCA201_PROVIDER_RESOURCE_DECISION_INVALID');
        }
        if ('deepseek' !== $provider) {
            throw new \InvalidArgumentException('OCA202_PROVIDER_MODEL_INVALID');
        }
        $modelConfiguration = $this->configuration->normalize($model, $modelConfiguration);
        $resourceCeiling = $this->resourceCeiling($resourceCeiling);
        if ($expiresAt <= $decidedAt || $expiresAt > $decidedAt->modify('+10 minutes')) {
            throw new \InvalidArgumentException('OCA203_PROVIDER_RESOURCE_DECISION_EXPIRY_INVALID');
        }

        $request = $this->validator->read(
            $this->root.'/'.self::REQUESTS.'/'.$requestId.'.json',
            'OCA204_OPERATIONAL_COGNITION_REQUEST_ABSENT',
        );
        $this->validateRequest($requestId, $request, $decidedAt, $provider, $model);
        if ($expiresAt > new \DateTimeImmutable($request['expires_at'])) {
            throw new \InvalidArgumentException('OCA203_PROVIDER_RESOURCE_DECISION_EXPIRY_INVALID');
        }
        $decisionFingerprint = [$requestId, $request['record_digest'], $disposition, $provider, $model, $modelConfiguration, $resourceCeiling, $rationale, $decidedAt->format(DATE_ATOM), $expiresAt->format(DATE_ATOM)];
        $decisionId = 'operational-provider-resource-decision-'.substr(hash('sha256', CanonicalJson::encode($decisionFingerprint)), 0, 20);
        $authorized = 'AUTHORIZED' === $disposition;
        $activationAuthorityId = $authorized ? 'operational-clavium-lease-activation-authority-'.substr(hash('sha256', CanonicalJson::encode([$decisionId, $requestId, $request['record_digest']])), 0, 20) : null;

        $record = [
            'schema' => 'imperium.imperator-operational-provider-resource-decision/v1',
            'decision_id' => $decisionId,
            'instance_id' => $request['instance_id'],
            'case_id' => $request['case_id'],
            'case_digest' => $request['case_digest'],
            'actor' => ['kind' => 'imperator', 'id' => self::IMPERATOR_ID],
            'authority_basis' => 'development-local-cli',
            'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
            'target' => $request['target'],
            'input_digest' => $request['input_digest'],
            'profile_model_requirements_digest' => $request['profile_model_requirements_digest'],
            'iteration' => $request['iteration'],
            'provider' => $provider,
            'model' => $model,
            'model_configuration' => $modelConfiguration,
            'resource_ceiling' => $resourceCeiling,
            'disposition' => $disposition,
            'rationale' => $rationale,
            'decided_at' => $decidedAt->format(DATE_ATOM),
            'expires_at' => $expiresAt->format(DATE_ATOM),
            'status' => $authorized ? 'OPERATIONAL_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE' : 'OPERATIONAL_PROVIDER_RESOURCE_REFUSED_NO_AUTHORITY',
            'clavium_lease_activation_authority' => $authorized ? [
                'authority_id' => $activationAuthorityId,
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'destination' => 'clavium.locksmith',
                'purpose' => 'ISSUE_ONE_EXACT_OPERATIONAL_COGNITION_LEASE',
                'expires_at' => $expiresAt->format(DATE_ATOM),
                'consumed' => false,
            ] : null,
            'credential_use_authority' => false,
            'network_access_authority' => false,
            'provider_invocation_authority' => false,
            'execution_continuation_authority' => false,
            'sealed' => true,
        ];

        return $this->atomic->run('oca-decision-source:'.$requestId, function () use ($requestId, $decisionId, $record): array {
            foreach (glob($this->root.'/'.self::DECISIONS.'/*.json') ?: [] as $path) {
                $prior = $this->validator->read($path, 'OCA206_PROVIDER_RESOURCE_DECISION_CONFLICT');
                if (($prior['source_cognition_request']['id'] ?? null) !== $requestId) {
                    continue;
                }
                if (($prior['decision_id'] ?? null) !== $decisionId) {
                    throw new \RuntimeException('OCA206_PROVIDER_RESOURCE_DECISION_CONFLICT');
                }

                return $prior;
            }

            return $this->records->put(self::DECISIONS, $decisionId, $record);
        });
    }

    private function resourceCeiling(array $ceiling): array
    {
        if (array_keys($ceiling) !== ['maximum_input_tokens', 'maximum_output_tokens', 'maximum_cost_microusd']) {
            throw new \InvalidArgumentException('OCA205_RESOURCE_CEILING_INVALID');
        }
        foreach ($ceiling as $value) {
            if (!is_int($value) || $value < 1) {
                throw new \InvalidArgumentException('OCA205_RESOURCE_CEILING_INVALID');
            }
        }

        return $ceiling;
    }

    private function validateRequest(string $requestId, array $request, \DateTimeImmutable $decidedAt, string $provider, string $model): void
    {
        if (!$this->validator->isIntact($request)
            || 'imperium.curia-operational-cognition-request/v1' !== ($request['schema'] ?? null)
            || $requestId !== ($request['request_id'] ?? null)
            || 'OPERATIONAL_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION' !== ($request['status'] ?? null)
            || true !== ($request['cognition_authority'] ?? null)
            || true !== ($request['cognition_authority_single_use'] ?? null)
            || false !== ($request['cognition_authority_consumed'] ?? null)
            || true === ($request['credential_use_authority'] ?? null)
            || true === ($request['network_access_authority'] ?? null)
            || true === ($request['provider_invocation_authority'] ?? null)
            || new \DateTimeImmutable((string) ($request['expires_at'] ?? '1970-01-01')) <= $decidedAt
            || ($request['model_requirements']['provider'] ?? null) !== $provider
            || ($request['model_requirements']['model'] ?? null) !== $model) {
            throw new \RuntimeException('OCA207_OPERATIONAL_COGNITION_REQUEST_INVALID');
        }
    }
}
