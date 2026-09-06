<?php
declare(strict_types=1);
namespace App\SourceReview;

use App\Imperium\Runtime\Curia\BoundedExecutionAuthorizationService;
use App\Imperium\Runtime\Curia\OperationalCognitionRequestService;
use App\Imperium\Runtime\Imperator\OperationalProviderResourceDecisionService;
use App\Imperium\Runtime\Clavium\OperationalCognitionLeaseService;
use App\Imperium\Runtime\Clavium\OperationalCognitionInvocationClaimService;
use App\Imperium\Runtime\Mission\BoundedOperationalExecutionService;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Clock;

final readonly class Workflow
{
    public function __construct(private SnapshotStore $snapshots, private BoundedExecutionAuthorizationService $authorizations, private OperationalCognitionRequestService $requests, private OperationalProviderResourceDecisionService $decisions, private OperationalCognitionLeaseService $leases, private OperationalCognitionInvocationClaimService $claims, private BoundedOperationalExecutionService $execution, private ImmutableRecordStore $records, private Clock $clock, #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%kernel.project_dir%')] private string $root) {}

    /** Authoritative producer is the existing Seneschal service, then existing Operator decision. */
    public function authorize(string $proposalId, string $expectedDigest, string $transitionId, string $seneschalBindingId): array
    {
        $p = $this->snapshots->get($proposalId);
        if (!hash_equals(Proposal::digest($p), $expectedDigest)) { throw new \RuntimeException('SR_APPROVAL_DIGEST_MISMATCH'); }
        $at = new \DateTimeImmutable($this->clock->now()->format(DATE_ATOM)); Proposal::preflight($p, $at);
        $a = $this->authorizations->authorize($transitionId, $seneschalBindingId, ['source_review' => $p]);
        $r = $this->requests->request($a['authorization_id'], ['provider' => 'deepseek', 'model' => Proposal::MODEL, 'capabilities' => ['structured-output', 'text-generation']], 1, ['Return at most one static functional defect; never execute supplied source.'], $at->modify('+10 minutes'), $at);
        $d = $this->decisions->decide($r['request_id'], 'AUTHORIZED', 'deepseek', Proposal::MODEL, ['temperature' => 0.2], Gateway::ceiling(), 'Owner approves exact source-review proposal '.$proposalId.' digest '.$expectedDigest, $at->modify('+10 minutes'), $at);
        return ['authorization_id' => $a['authorization_id'], 'request_id' => $r['request_id'], 'decision_id' => $d['decision_id'], 'proposal_digest' => $expectedDigest, 'provider_invoked' => false];
    }
    public function lease(string $decisionId, string $locksmithBindingId): array
    {
        $d = $this->records->read('var/imperium/imperator/operational-provider-resource-decisions', $decisionId);
        $at = new \DateTimeImmutable($this->clock->now()->format(DATE_ATOM));
        $end = min($at->modify('+5 minutes'), new \DateTimeImmutable($d['expires_at']));
        return $this->leases->issue($decisionId, $d['clavium_lease_activation_authority']['authority_id'], $locksmithBindingId, $end, $at);
    }
    public function execute(string $authorizationId, string $leaseId): array
    {
        $a = $this->records->read('var/imperium/offices/curia/bounded-execution-authorizations', $authorizationId);
        $p = Proposal::validate($a['input']['source_review']);
        if (Proposal::digest($p) !== Proposal::digest($this->snapshots->get($p['proposal_id']))) { throw new \RuntimeException('SR_SNAPSHOT_CHANGED'); }
        Proposal::preflight($p, $this->clock->now());
        $lease = $this->records->read('var/imperium/offices/clavium/operational-cognition-leases', $leaseId);
        $r = $this->records->read('var/imperium/offices/curia/operational-cognition-requests', $lease['source_cognition_request']['id']);
        if ($r['source_bounded_execution_authorization'] !== ['id' => $authorizationId, 'digest' => $a['record_digest']]) { throw new \RuntimeException('SR_AUTHORIZATION_SUBSTITUTION'); }
        $this->claims->claim($leaseId, $r['cognition_authority_id'], $this->clock->now());
        return $this->execution->execute($authorizationId);
    }
    public function status(string $authorizationId): array
    {
        $a = $this->records->read('var/imperium/offices/curia/bounded-execution-authorizations', $authorizationId);
        $progress = [];
        foreach (glob($this->root.'/var/imperium/runtime/operational-cognition-invocation-claims/*.json') ?: [] as $file) {
            $claim = $this->records->read('var/imperium/runtime/operational-cognition-invocation-claims', basename($file, '.json'));
            if (($claim['input_digest'] ?? null) !== $a['input_digest']) { continue; }
            $r = $this->records->read('var/imperium/offices/curia/operational-cognition-requests', $claim['source_cognition_request']['id']);
            if (($r['source_bounded_execution_authorization']['id'] ?? null) !== $authorizationId) { continue; }
            foreach (['journal' => 'var/imperium/runtime/provider-invocation-journal', 'result' => 'var/imperium/mission/source-review/results'] as $key => $dir) {
                if (is_file($this->root.'/'.$dir.'/'.$claim['claim_id'].'.json')) { $progress[$key] = $this->records->read($dir, $claim['claim_id']); }
            }
            $progress['claim_id'] = $claim['claim_id'];
        }
        return ['progress' => $progress, 'authorization_id' => $authorizationId, 'input_digest' => $a['input_digest'], 'proposal_id' => $a['input']['source_review']['proposal_id'], 'read_only' => true, 'automatic_retry_permitted' => false];
    }
}
