<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\DeepSeekDelegateModelConfiguration;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GovernanceProviderResourceDecisionService
{
    private const REQUESTS = 'var/imperium/runtime/governance-cognition-requests';
    private const DECISIONS = 'var/imperium/imperator/governance-provider-resource-decisions';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;
    private AtomicTransition $atomic;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $root,
        ?RecordReferenceValidator $validator = null,
        ?ImmutableRecordStore $records = null,
        ?AtomicTransition $atomic = null,
        private ?DeepSeekDelegateModelConfiguration $configuration = null,
    ) {
        $this->validator = $validator ?? new RecordReferenceValidator($root);
        $this->atomic = $atomic ?? new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $this->atomic);
    }

    public function decide(string $requestId, string $disposition, array $modelConfiguration, array $resourceCeiling, string $rationale, \DateTimeImmutable $expiresAt, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^governance-cognition-request-[a-f0-9]{20}$/', $requestId)) {
            throw new \InvalidArgumentException('GCA200_GOVERNANCE_COGNITION_REQUEST_ID_INVALID');
        }
        $disposition = strtoupper(trim($disposition));
        $rationale = trim($rationale);
        if (!in_array($disposition, ['AUTHORIZED', 'REFUSED'], true) || '' === $rationale || $expiresAt <= $decidedAt || $expiresAt > $decidedAt->modify('+10 minutes')) {
            throw new \InvalidArgumentException('GCA201_GOVERNANCE_PROVIDER_RESOURCE_DECISION_INVALID');
        }
        $modelConfiguration = ($this->configuration ?? new DeepSeekDelegateModelConfiguration())->normalize('deepseek-v4-flash', $modelConfiguration);
        if (array_keys($resourceCeiling) !== ['maximum_input_tokens', 'maximum_output_tokens', 'maximum_cost_microusd']) {
            throw new \InvalidArgumentException('GCA202_GOVERNANCE_RESOURCE_CEILING_INVALID');
        }
        foreach ($resourceCeiling as $value) {
            if (!is_int($value) || $value < 1) {
                throw new \InvalidArgumentException('GCA202_GOVERNANCE_RESOURCE_CEILING_INVALID');
            }
        }

        $request = $this->validator->read($this->root.'/'.self::REQUESTS.'/'.$requestId.'.json', 'GCA203_GOVERNANCE_COGNITION_REQUEST_ABSENT');
        if (!$this->validator->isIntact($request) || 'imperium.governance-cognition-request/v1' !== ($request['schema'] ?? null)
            || $requestId !== ($request['request_id'] ?? null) || 'GOVERNANCE_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION' !== ($request['status'] ?? null)
            || true !== ($request['governance_authority_single_use'] ?? null) || false !== ($request['governance_authority_consumed'] ?? null)
            || true === ($request['credential_use_authority'] ?? null) || true === ($request['network_access_authority'] ?? null)
            || new \DateTimeImmutable((string) ($request['expires_at'] ?? '1970-01-01')) <= $decidedAt
            || $expiresAt > new \DateTimeImmutable($request['expires_at'])
            || 'deepseek' !== ($request['model_requirements']['provider'] ?? null) || 'deepseek-v4-flash' !== ($request['model_requirements']['model'] ?? null)) {
            throw new \RuntimeException('GCA204_GOVERNANCE_COGNITION_REQUEST_INVALID');
        }

        $fingerprint = [$requestId, $request['record_digest'], $disposition, $modelConfiguration, $resourceCeiling, $rationale, $decidedAt->format(DATE_ATOM), $expiresAt->format(DATE_ATOM)];
        $decisionId = 'governance-provider-resource-decision-'.substr(hash('sha256', CanonicalJson::encode($fingerprint)), 0, 20);
        $authorized = 'AUTHORIZED' === $disposition;
        $activationId = $authorized ? 'governance-clavium-lease-activation-authority-'.substr(hash('sha256', $decisionId.'|'.$request['record_digest']), 0, 20) : null;
        $record = [
            'schema' => 'imperium.imperator-governance-provider-resource-decision/v1', 'decision_id' => $decisionId,
            'instance_id' => $request['instance_id'], 'case_id' => $request['case_id'], 'case_digest' => $request['case_digest'],
            'actor' => ['kind' => 'imperator', 'id' => 'imperator-development-root'], 'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']],
            'cluster' => $request['cluster'], 'source_governance_authority' => $request['source_governance_authority'], 'target' => $request['target'], 'input_digest' => $request['input_digest'],
            'provider' => 'deepseek', 'model' => 'deepseek-v4-flash', 'model_configuration' => $modelConfiguration, 'resource_ceiling' => $resourceCeiling,
            'disposition' => $disposition, 'rationale' => $rationale, 'decided_at' => $decidedAt->format(DATE_ATOM), 'expires_at' => $expiresAt->format(DATE_ATOM),
            'status' => $authorized ? 'GOVERNANCE_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE' : 'GOVERNANCE_PROVIDER_RESOURCE_REFUSED_NO_AUTHORITY',
            'clavium_lease_activation_authority' => $authorized ? ['authority_id' => $activationId, 'authority_single_use' => true, 'authority_exercisable' => true, 'consumed' => false, 'expires_at' => $expiresAt->format(DATE_ATOM)] : null,
            'credential_use_authority' => false, 'network_access_authority' => false, 'provider_invocation_authority' => false, 'continuing_authority' => false, 'sealed' => true,
        ];

        return $this->atomic->run('gca-decision-source:'.$requestId, function () use ($requestId, $decisionId, $record): array {
            foreach (glob($this->root.'/'.self::DECISIONS.'/*.json') ?: [] as $path) {
                $prior = $this->validator->read($path, 'GCA205_GOVERNANCE_PROVIDER_RESOURCE_DECISION_CONFLICT');
                if (($prior['source_cognition_request']['id'] ?? null) !== $requestId) { continue; }
                if (!$this->validator->isIntact($prior) || ($prior['decision_id'] ?? null) !== $decisionId) { throw new \RuntimeException('GCA205_GOVERNANCE_PROVIDER_RESOURCE_DECISION_CONFLICT'); }
                return $prior;
            }
            return $this->records->put(self::DECISIONS, $decisionId, $record);
        });
    }
}
