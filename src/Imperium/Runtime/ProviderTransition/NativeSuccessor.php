<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciledLifecycleSuccessorContract as Successor;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationContract as Activation;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationContractValidator;
use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceProductionContract as Production;

/** Native creation; no API accepts an offline successor or a flattened/resealed activation. */
final readonly class NativeSuccessor
{
    public function __construct(private NativeState $state, private ?\Closure $clock = null) {}

    public function create(string $principalId, array $activation): array
    {
        return $this->state->locked(function () use ($principalId, $activation): array {
            $at = null === $this->clock ? time() : ($this->clock)();
            $p = (new NativePrincipal($this->state))->load($principalId, $at);
            $sources = $this->sources($p, $activation, $at);
            $record = $this->build($p, $sources, $at);
            return $this->state->put('successors', $record['successor']['successor_id'], $record);
        });
    }

    public function load(string $id, int $at): array
    {
        $r = $this->state->get('successors', $id) ?? throw new \RuntimeException('NIR_SUCCESSOR_ABSENT');
        if (!is_int($r['at'] ?? null) || $r['at'] > $at) { throw new \RuntimeException('NIR_SUCCESSOR_TIME'); }
        $p = (new NativePrincipal($this->state))->load($r['principal']['id'] ?? '', $at);
        (new NativePrincipal($this->state))->load($r['principal']['id'] ?? '', $r['at']);
        $sources = $this->sources($p, $r['successor']['active_principal_activation'] ?? [], $at);
        if ($r !== $this->build($p, $sources, $r['at']) || $id !== $r['successor']['successor_id']) { throw new \RuntimeException('NIR_SUCCESSOR_PROVENANCE'); }
        return $r;
    }

    private function sources(array $p, array $activation, int $at): array
    {
        $a = $this->state->source('activation', $activation);
        if (array_keys($a) !== Activation::REQUIRED_FIELDS || $a['schema'] !== Activation::SCHEMA
            || $a['principal_activation_id'] !== $activation['id'] || 'ACTIVE' !== $a['status']
            || $p['instance_id'] !== $a['instance_id'] || $p['operation'] !== $a['scope']['operation']
            || true !== ($p['preserved_scope']['provider_binding_activation_authority'] ?? null)) { throw new \RuntimeException('NIR_ACTIVATION_SOURCE'); }
        $b = $this->state->source('binding', $p['provider_binding']);
        if (array_keys($b) !== \App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract::REQUIRED_FIELDS
            || $b['schema'] !== \App\Imperium\Runtime\LaCortine\ProviderImplementationBindingContract::SCHEMA
            || $b['binding_id'] !== $p['provider_binding']['id'] || $b['instance_id'] !== $p['instance_id']
            || $b['scope']['operation'] !== $p['operation'] || false !== $b['scope']['provider_substitution_permitted']
            || $at < strtotime($b['validity']['effective_at']) || $at >= strtotime($b['validity']['expires_at'])
            || 'BOUND_INACTIVE' !== $b['status'] || $b['provider_implementation']['provider_id'] !== $a['scope']['provider_id']) { throw new \RuntimeException('NIR_BINDING_SOURCE'); }
        $boundary = $this->state->source('boundary', $a['execution_boundary']);
        $attestation = $this->state->source('attestation', $a['principal_attestation']);
        $assurance = $this->state->source('assurance', $a['provider_assurance_admission']);
        $productions = [];
        foreach (glob($this->state->root.'/'.NativeState::SOURCES['production'].'/*.json') ?: [] as $path) {
            $candidate = $this->state->json(NativeState::SOURCES['production'].'/'.basename($path));
            if (($candidate['activation_decision']['decision_id'] ?? null) !== $a['source_decision']['id']) { continue; }
            $record = $this->state->source('production', NativeState::ref($candidate, 'production_id'));
            if (array_keys($record) !== Production::REQUIRED_FIELDS || $record['schema'] !== Production::SCHEMA
                || true !== $record['combined_winner'] || 'ACTIVE' !== $record['effective_principal_status']
                || $record['instance_id'] !== $p['instance_id']
                || NativeState::ref($record['activation_decision'], 'decision_id') !== $a['source_decision']
                || true !== $record['consumed_issuance_authorization']['consumed']) { throw new \RuntimeException('NIR_ACTIVATION_PRODUCTION'); }
            $productions[] = $record;
        }
        if (1 !== count($productions)) { throw new \RuntimeException('NIR_ACTIVATION_PRODUCTION_ABSENT_OR_AMBIGUOUS'); }
        $production = $productions[0];
        (new ProviderExecutorPrincipalActivationContractValidator())->assertActivation($a, $production['activation_decision'], $attestation, $assurance, $boundary, new \DateTimeImmutable('@'.$at));
        foreach (glob($this->state->root.'/'.NativeState::SOURCES['activation'].'/*.json') ?: [] as $path) {
            $other = $this->state->json(NativeState::SOURCES['activation'].'/'.basename($path));
            if (($other['instance_id'] ?? null) === $a['instance_id'] && ($other['principal']['principal_id'] ?? null) === $a['principal']['principal_id']
                && ($other['principal']['generation'] ?? 0) > $a['principal']['generation']) {
                $activated = strtotime($other['activated_at'] ?? '');
                if (false === $activated || $activated <= $at) { throw new \RuntimeException('NIR_EXECUTOR_GENERATION_CHANGED'); }
            }
        }
        return compact('a', 'b', 'boundary', 'assurance', 'production');
    }

    private function build(array $p, array $s, int $at): array
    {
        $a = $s['a']; $b = $s['b'];
        $root = TransitionContract::digest(['instance' => $p['instance_id'], 'binding' => $b['binding_id'], 'operation' => $p['operation']]);
        $id = 'successor-'.TransitionContract::digest([$root, $a['record_digest'], $p['record_digest']]);
        $scope = ['provider_id' => $a['scope']['provider_id'], 'operation' => $p['operation'],
            'principal_id' => $a['principal']['principal_id'], 'principal_generation' => $a['principal']['generation'],
            'process_boundary_id' => $a['principal']['process_boundary_id'], 'provider_substitution_permitted' => false,
            'operation_substitution_permitted' => false, 'principal_generation_substitution_permitted' => false, 'binding_substitution_permitted' => false];
        $target = NativeState::seal(['schema' => 'imperium.imperator.native-successor-target/v1', 'target_id' => 'target-'.$id,
            'instance_id' => $p['instance_id'], 'binding' => NativeState::ref($b, 'binding_id'), 'activation' => NativeState::ref($a, 'principal_activation_id'),
            'operation_scope' => $scope, 'replay_contention_root' => $root]);
        $decision = NativeState::seal(['schema' => 'imperium.imperator.native-successor-creation-decision/v1', 'decision_id' => 'decision-'.$id,
            'principal' => NativeState::ref($p, 'principal_version_id'), 'target' => NativeState::ref($target, 'target_id'),
            'permitted_transition' => 'CREATE_EXACT_OPERATION_SCOPED_PROVIDER_BINDING_SUCCESSOR',
            'authority_single_use' => true, 'at' => $at, 'continuing_authority' => false]);
        $successor = ['schema' => Successor::SCHEMA, 'successor_id' => $id, 'instance_id' => $p['instance_id'],
            'source_decision' => NativeState::ref($decision, 'decision_id'), 'successor_target' => NativeState::ref($target, 'target_id'),
            'active_principal_activation' => NativeState::ref($a, 'principal_activation_id'), 'provider_binding_descriptor' => NativeState::ref($b, 'binding_id'),
            'provider_assurance_admission' => $a['provider_assurance_admission'], 'execution_boundary' => $a['execution_boundary'],
            'operation_scope' => $scope, 'replay_contention_root' => ['root_id' => $root, 'instance_id' => $p['instance_id'],
                'principal_activation_id' => $a['principal_activation_id'], 'binding_id' => $b['binding_id'], 'provider_id' => $scope['provider_id'], 'operation' => $p['operation']],
            'consumed_activation_authority' => [...NativeState::ref($decision, 'decision_id'), 'consumed_at' => gmdate(DATE_ATOM, $at), 'consumed' => true, 'continuing_authority' => false],
            'status' => 'OPERATION_SCOPED_BINDING_ACTIVE',
            'validity' => ['effective_at' => gmdate(DATE_ATOM, $at), 'expires_at' => gmdate(DATE_ATOM, min($p['expires_at'], strtotime($a['validity']['expires_at']), strtotime($b['validity']['expires_at']), strtotime($s['assurance']['validity']['review_due_at']))), 'revocation_reference' => null],
            'reconstruction' => Successor::RECONSTRUCTION_INVARIANTS, ...Successor::REQUIRED_INVARIANTS,
            'activated_at' => gmdate(DATE_ATOM, $at), 'sealed' => true];
        $successor = NativeState::seal($successor);
        $winner = NativeState::seal(['schema' => 'imperium.la-cortine.native-successor-creation-winner/v1', 'winner_boundary_id' => 'winner-'.$id,
            'instance_id' => $p['instance_id'], 'source_decision' => NativeState::ref($decision, 'decision_id'),
            'successor' => NativeState::ref($successor, 'successor_id'), 'replay_contention_root' => $root,
            'authority_consumed' => true, 'successor_created' => true, 'effect_started' => false, 'continuing_authority' => false]);
        return ['schema' => 'imperium.la-cortine.native-successor-production/v1', 'principal' => NativeState::ref($p, 'principal_version_id'),
            'activation_production' => NativeState::ref($s['production'], 'production_id'), 'target' => $target,
            'decision' => $decision, 'successor' => $successor, 'creation_winner' => $winner, 'at' => $at];
    }
}
