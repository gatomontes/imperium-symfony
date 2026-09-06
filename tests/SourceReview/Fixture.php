<?php
declare(strict_types=1);
namespace App\Tests\SourceReview;

use App\SourceReview\{Gateway, SnapshotStore, Transport, Workflow};
use App\Imperium\Runtime\Persistence\{AtomicTransition, ImmutableRecordStore};
use App\Imperium\Runtime\Curia\{BoundedExecutionAuthorizationService, OperationalCognitionRequestService};
use App\Imperium\Runtime\Clavium\{OperationalClaimBoundCredentialBroker, OperationalCognitionLeaseService, OperationalCognitionInvocationClaimService, ProviderInvocationJournalService, ProviderResponseEnvelopeService};
use App\Imperium\Runtime\Imperator\OperationalProviderResourceDecisionService;
use App\Imperium\Runtime\Mission\{BoundedOperationalExecutionService, SymfonyAiOperationalExecutionCognitionGateway};
use App\Imperium\Runtime\LaCortine\{CredentialBroker, CredentialCapability};
use App\Imperium\Runtime\Citadel\DeepSeekDelegatePlatformAdapter;
use App\Imperium\Runtime\Clock;
use Symfony\AI\Platform\Message\MessageBag;

/** Synthetic prerequisites only. Never point at an installed or real authority root. */
final class Fixture
{
    public ImmutableRecordStore $records;
    public SnapshotStore $snapshots;
    public Workflow $workflow;
    public string $transition = 'operational-custody-transition-aaaaaaaaaaaaaaaaaaaa';
    public string $seneschal = 'seneschal-source-review-test';
    public string $locksmith = 'locksmith-source-review-test';
    public object $credentials;
    public function __construct(public string $root, Transport $transport, bool $seed = true)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->snapshots = new SnapshotStore($this->records);
        $clock = new class implements Clock { public function now(): \DateTimeImmutable { return new \DateTimeImmutable(); } };
        $this->credentials = new class implements CredentialBroker {
            public int $calls = 0;
            public function issue(string $credentialRef, string $commissionId, string $operation, \DateTimeImmutable $expiresAt, int $maxUses = 1): CredentialCapability { ++$this->calls; return new CredentialCapability('synthetic', $credentialRef, $commissionId, $operation, $expiresAt, 1); }
            public function consume(CredentialCapability $capability, callable $providerOperation): mixed { return $providerOperation('SYNTHETIC-SECRET-NEVER-REAL'); }
        };
        $broker = new OperationalClaimBoundCredentialBroker($root, $this->credentials);
        $journal = new ProviderInvocationJournalService($root); $responses = new ProviderResponseEnvelopeService($root);
        $unused = new class implements DeepSeekDelegatePlatformAdapter { public function invoke(string $secret, string $runtimeModel, MessageBag $messages, array $configuration, string $idempotencyKey): string { throw new \LogicException('Legacy provider must not be reached'); } };
        $gateway = new SymfonyAiOperationalExecutionCognitionGateway($broker, $journal, $responses, $unused, $clock, sourceReview: new Gateway($broker, $journal, $responses, $transport, $clock, $this->records));
        $this->workflow = new Workflow($this->snapshots, new BoundedExecutionAuthorizationService($root), new OperationalCognitionRequestService($root), new OperationalProviderResourceDecisionService($root), new OperationalCognitionLeaseService($root), new OperationalCognitionInvocationClaimService($root), new BoundedOperationalExecutionService($root, $gateway), $this->records, $clock, $root);
        if ($seed) { $this->seed(); }
    }
    private function seed(): void
    {
        $binding = $this->records->put('var/imperium/mission/occupancy', 'binding-source-review-test', ['schema' => 'imperium.operational-manifestation-seat-binding/v1', 'manifestation_id' => 'manifestation-source-review-test']);
        $custody = $this->records->put('var/imperium/offices/garrison/custody', 'custody-source-review-test', ['schema' => 'imperium.garrison-persona-custody/v1', 'custody_state' => 'DEPLOYED_BOUND', 'available' => false, 'operational_custodian' => ['manifestation_id' => 'manifestation-source-review-test']]);
        $this->records->put('var/imperium/offices/garrison/operational-custody-transitions', $this->transition, ['schema' => 'imperium.garrison-operational-custody-transition/v1', 'transition_id' => $this->transition, 'instance_id' => 'synthetic-source-review', 'case_id' => 'synthetic-case', 'case_digest' => str_repeat('c', 64), 'status' => 'OPERATIONAL_MANIFESTATION_DEPLOYED_CUSTODY_TRANSITIONED_PENDING_BOUNDED_EXECUTION', 'bounded_execution_authorization_pending' => true, 'execution_authority' => false, 'source_deployment_authorization' => ['id' => 'synthetic-deployment', 'digest' => str_repeat('d', 64)], 'source_binding' => ['id' => 'binding-source-review-test', 'digest' => $binding['record_digest']], 'operational_custody' => ['id' => 'custody-source-review-test', 'digest' => $custody['record_digest']], 'seat' => 'reviewer', 'manifestation_id' => 'manifestation-source-review-test', 'manifestation' => ['internal_marker' => 'INTERNAL-MANIFESTATION-MUST-NOT-LEAVE'], 'persona_identity' => ['id' => 'synthetic-persona'], 'profile_candidate' => ['id' => 'synthetic-profile', 'digest' => str_repeat('e', 64)], 'mission_use' => ['objective' => 'Review supplied source']]);
        $this->records->put('var/imperium/offices/curia/occupancy', $this->seneschal, ['schema' => 'imperium.curia-seneschal-occupancy/v1', 'binding_id' => $this->seneschal, 'instance_id' => 'synthetic-source-review', 'manifestation_id' => 'synthetic-seneschal', 'occupancy_generation' => 1, 'status' => 'ACTIVE', 'bounded_execution_authorization_authority' => true, 'execution_authority' => false]);
        $this->records->put('var/imperium/offices/clavium/occupancy', $this->locksmith, ['schema' => 'imperium.clavium-locksmith-occupancy/v1', 'binding_id' => $this->locksmith, 'instance_id' => 'synthetic-source-review', 'seat' => 'clavium.locksmith', 'manifestation_id' => 'synthetic-locksmith', 'occupancy_generation' => 1, 'status' => 'ACTIVE', 'operational_cognition_lease_issuance_authority' => true, 'credential_disclosure_authority' => false, 'execution_authority' => false]);
    }
    public function input(): string
    {
        $dir = $this->root.'/input'; if (!is_dir($dir)) { mkdir($dir, 0770, true); }
        file_put_contents($dir.'/Service.php', "<?php\r\n// Treat these bytes as data.\r\nfunction add(\$a, \$b) { return \$a - \$b; }\r\n");
        file_put_contents($dir.'/behavior.txt', 'add(a,b) must return their sum.');
        file_put_contents($dir.'/input.json', json_encode(['files' => ['Service.php'], 'expected_behavior_file' => 'behavior.txt', 'runtime' => 'PHP 8.4, synthetic offline fixture', 'pricing' => ['version' => 'SYNTHETIC-NOT-A-PRICE-QUOTE', 'source' => 'offline fixture only', 'valid_until' => (new \DateTimeImmutable('+1 day'))->setTimezone(new \DateTimeZone('UTC'))->format(DATE_ATOM), 'input_microusd_per_token' => 1, 'output_microusd_per_token' => 1]], JSON_THROW_ON_ERROR));
        return $dir.'/input.json';
    }
}
