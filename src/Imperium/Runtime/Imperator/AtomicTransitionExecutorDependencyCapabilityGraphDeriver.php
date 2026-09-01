<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContractValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator;

/** Derives the actual resolved in-memory executor graph for the exact build. */
final readonly class AtomicTransitionExecutorDependencyCapabilityGraphDeriver
{
    private const array ALLOWED_CLASSES = [
        AtomicTransitionTrustedCaseExecutionCorridor::class,
        AtomicTransitionExecutionProvenanceContractValidator::class,
        AtomicTransitionEvidenceDeterministicCaseExecutor::class,
        AtomicTransitionEvidenceDerivationContractValidator::class,
        ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor::class,
        ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContractValidator::class,
        ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier::class,
        ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator::class,
    ];

    private const array EFFECT_PATTERNS = [
        'network_io' => '/(?:curl_|stream_socket_|socket_|HttpClientInterface)/',
        'filesystem_write' => '/(?:file_put_contents|fopen\s*\(|unlink\s*\(|rename\s*\(|mkdir\s*\(|flock\s*\()/',
        'process_execution' => '/(?:proc_open|shell_exec|passthru\s*\(|\bexec\s*\()/',
        'environment_access' => '/(?:getenv\s*\(|\$_ENV|\$_SERVER)/',
        'credential_resolution' => '/(?:CredentialBroker|CredentialResolver|resolveCredential)/',
        'provider_invocation' => '/(?:ProviderInvocation|invokeProvider|SymfonyAiPlatform)/',
        'runtime_state_mutation' => '/(?:ImmutableRecordStore|MutableStateStore|AuthorityConsumptionStore|Persistence\\\\AtomicTransition)/',
    ];

    public function __construct(
        private AtomicTransitionExecutionProvenanceContractValidator $provenanceValidator,
    ) {
    }

    public function derive(
        string $graphId,
        array $origin,
        array $provenance,
        object $executor,
    ): array {
        $this->provenanceValidator->assertExecutionProvenance($provenance, $origin);
        if (!preg_match('/^[a-z0-9][a-z0-9._:\/-]{2,220}$/', $graphId)) {
            throw new \RuntimeException('PBL1000_EXECUTOR_GRAPH_ID_INVALID');
        }

        $nodes = [];
        $visited = [];
        $this->walk($executor, $nodes, $visited);
        ksort($nodes, SORT_STRING);
        $nodes = array_values($nodes);

        $root = new \ReflectionClass($executor);
        $rootDigest = $this->implementationDigest($root);
        if (AtomicTransitionTrustedCaseExecutionCorridor::class !== $root->getName()
            || !hash_equals($origin['executor_implementation_digest'], $rootDigest)) {
            throw new \RuntimeException('PBL1001_EXECUTOR_GRAPH_ROOT_SUBSTITUTED');
        }

        return $this->seal([
            'schema' => AtomicTransitionExecutorDependencyCapabilityGraphContract::SCHEMA,
            'graph_id' => $graphId,
            'execution_provenance_reference' => $this->reference(
                $provenance,
                'execution_provenance_id',
            ),
            'evidence_origin_reference' => $this->reference(
                $origin,
                'evidence_origin_id',
            ),
            'source_commit' => $origin['source_commit'],
            'source_tree_digest' => $origin['source_tree_digest'],
            'build_id' => $origin['build_id'],
            'build_artifact_digest' => $origin['build_artifact_digest'],
            'dependency_lock_digest' => $origin['dependency_lock_digest'],
            'root_executor_class' => $root->getName(),
            'root_implementation_digest' => $rootDigest,
            'node_count' => count($nodes),
            'nodes' => $nodes,
            'graph_digest' => hash('sha256', CanonicalJson::encode($nodes)),
            'complete_recursive_object_traversal' => true,
            'build_bound' => true,
            'unknown_dependencies' => [],
            'substituted_dependencies' => [],
            'mutable_dependencies' => [],
            'effect_capable_dependencies' => [],
            'read_only' => true,
            'runtime_state_written' => false,
            'authority_issued_or_consumed' => false,
            'execution_admitted' => false,
            'provider_effect_started' => false,
            'continuing_authority' => false,
            'status' => AtomicTransitionExecutorDependencyCapabilityGraphContract::STATUS,
            'sealed' => true,
        ]);
    }

    private function walk(object $object, array &$nodes, array &$visited): void
    {
        $objectId = spl_object_id($object);
        if (isset($visited[$objectId])) {
            return;
        }
        $visited[$objectId] = true;

        $reflection = new \ReflectionClass($object);
        $class = $reflection->getName();
        if (!in_array($class, self::ALLOWED_CLASSES, true)) {
            throw new \RuntimeException('PBL1002_EXECUTOR_GRAPH_UNKNOWN_DEPENDENCY:'.$class);
        }
        if (!$this->readonlyOrStateless($reflection)) {
            throw new \RuntimeException('PBL1003_EXECUTOR_GRAPH_MUTABLE_DEPENDENCY:'.$class);
        }

        $capabilities = $this->capabilities($reflection);
        if (AtomicTransitionExecutorDependencyCapabilityGraphContract::CAPABILITIES
            !== $capabilities) {
            throw new \RuntimeException('PBL1004_EXECUTOR_GRAPH_EFFECT_CAPABLE_DEPENDENCY:'.$class);
        }

        $dependencies = [];
        $properties = $reflection->getProperties();
        usort(
            $properties,
            static fn (\ReflectionProperty $left, \ReflectionProperty $right): int =>
                $left->getName() <=> $right->getName(),
        );
        foreach ($properties as $property) {
            if ($property->isStatic() || !$property->isInitialized($object)) {
                continue;
            }
            $value = $property->getValue($object);
            if (is_object($value)) {
                $dependencies[] = $value::class;
                $this->walk($value, $nodes, $visited);
            } elseif (null !== $value) {
                throw new \RuntimeException('PBL1003_EXECUTOR_GRAPH_MUTABLE_DEPENDENCY:'.$class);
            }
        }
        sort($dependencies, SORT_STRING);

        $nodes[$class] = [
            'class' => $class,
            'implementation_digest' => $this->implementationDigest($reflection),
            'final' => $reflection->isFinal(),
            'readonly_or_stateless' => true,
            'dependencies' => array_values(array_unique($dependencies)),
            'capabilities' => $capabilities,
        ];
    }

    private function readonlyOrStateless(\ReflectionClass $reflection): bool
    {
        if ($reflection->isReadOnly()) {
            return true;
        }
        foreach ($reflection->getProperties() as $property) {
            if (!$property->isStatic() && !$property->isReadOnly()) {
                return false;
            }
        }

        return true;
    }

    private function capabilities(\ReflectionClass $reflection): array
    {
        $source = $this->source($reflection);
        $capabilities = AtomicTransitionExecutorDependencyCapabilityGraphContract::CAPABILITIES;
        foreach (self::EFFECT_PATTERNS as $capability => $pattern) {
            if (preg_match($pattern, $source)) {
                $capabilities[$capability] = true;
            }
        }

        return $capabilities;
    }

    private function implementationDigest(\ReflectionClass $reflection): string
    {
        return hash('sha256', $this->source($reflection));
    }

    private function source(\ReflectionClass $reflection): string
    {
        $file = $reflection->getFileName();
        if (!is_string($file) || !is_file($file)) {
            throw new \RuntimeException(
                'PBL1002_EXECUTOR_GRAPH_UNKNOWN_DEPENDENCY:'.$reflection->getName(),
            );
        }
        $source = file_get_contents($file);
        if (!is_string($source)) {
            throw new \RuntimeException(
                'PBL1002_EXECUTOR_GRAPH_UNKNOWN_DEPENDENCY:'.$reflection->getName(),
            );
        }

        return $source;
    }

    private function reference(array $record, string $id): array
    {
        return [
            'id' => $record[$id],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }
}
