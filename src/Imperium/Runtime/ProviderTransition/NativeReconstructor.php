<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3Contract as V3;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3ContractValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciledLifecycleSuccessorContract as Successor;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationContractValidator;

/** Independent stored-edge verification. No issuance, admission builder, publication or lock. */
final readonly class NativeReconstructor
{
    public function __construct(private NativeState $state, private ?\Closure $inspectionCheckpoint = null) {}

    public function reconstruct(string $instance, string $binding, string $operation, int $at): array
    {
        try {
            return (new NativeInspectionSnapshot($this->state, $this->inspectionCheckpoint))
                ->observe(fn (): array => $this->reconstructObserved($instance, $binding, $operation, $at));
        } catch (\Throwable) {
            return $this->result('UNKNOWN_REPLAY_PROHIBITED');
        }
    }

    private function reconstructObserved(string $instance, string $binding, string $operation, int $at): array
    {
        try {
            foreach ([$instance, $binding, $operation] as $id) { NativeState::id($id); }
            $before = $this->snapshot();
            $root = TransitionContract::digest(compact('instance', 'binding', 'operation'));
            $c = $this->state->get('transitions', $root); $j = $this->state->get('journals', $root);
            if (null === $c) {
                if (null !== $j || $this->orphanRetirement($root) || $before !== $this->snapshot()) { return $this->result('UNKNOWN_REPLAY_PROHIBITED'); }
                return $this->result('ABSENT');
            }
            if (null === $j) { throw new \RuntimeException(); }
            $this->sealed($c, ['schema', 'root', 'authority_id', 'journal', 'records', 'committed_at', 'record_digest'], 'imperium.la-cortine.native-transition-commit/v1');
            $this->sealed($j, ['schema', 'journal_id', 'root', 'storage', 'authority', 'manifest_digest', 'legacy_storage_identities', 'prepared_at', 'state', 'record_digest'], 'imperium.la-cortine.native-transition-journal/v1');
            if ($c['root'] !== $root || $j['root'] !== $root || $j['storage'] !== $this->state->identity()
                || $c['journal'] !== NativeState::ref($j, 'journal_id') || $j['journal_id'] !== 'journal-'.$root
                || !is_int($c['committed_at']) || !is_int($j['prepared_at']) || $c['committed_at'] > $at
                || $j['prepared_at'] > $c['committed_at'] || $j['state'] !== 'PREPARED_NO_AUTHORITY_CONSUMED') { throw new \RuntimeException(); }
            $committedAt = $c['committed_at'];
            $chain = $this->state->get('authorities', $c['authority_id']) ?? throw new \RuntimeException();
            TransitionContract::keys($chain, ['schema', 'principal', 'decision', 'custody', 'authority', 'at']);
            $p = (new NativePrincipal($this->state))->load($chain['principal']['id'], $committedAt);
            (new NativePrincipal($this->state))->load($chain['principal']['id'], $chain['at']);
            if ($c['authority_id'] !== $chain['authority']['authority_id'] || $p['instance_id'] !== $instance || $p['provider_binding']['id'] !== $binding || $p['operation'] !== $operation
                || $chain['principal'] !== NativeState::ref($p, 'principal_version_id') || $chain['at'] > $j['prepared_at']) { throw new \RuntimeException(); }
            $s = $this->state->get('successors', $chain['decision']['successor']['id']) ?? throw new \RuntimeException();
            (new NativePrincipal($this->state))->load($p['principal_version_id'], $s['at']);
            $this->successor($s, $p, $s['at'], $root);
            $this->successor($s, $p, $committedAt, $root);
            $this->authority($chain, $p, $s, $root);
            if ($j['authority'] !== NativeState::ref($chain['authority'], 'authority_id')) { throw new \RuntimeException(); }
            $this->migration($j, $instance);
            $receipt = $this->records($c, $chain, $s, $root, $committedAt);
            $current = true;
            try {
                (new NativePrincipal($this->state))->load($p['principal_version_id'], $at);
                $this->successor($s, $p, $at, $root);
            } catch (\Throwable) { $current = false; }
            if ($before !== $this->snapshot()) { throw new \RuntimeException(); }
            return $this->result($current ? 'COMMITTED' : 'COMMITTED_NOT_CURRENT', $receipt);
        } catch (\Throwable) {
            return $this->result('UNKNOWN_REPLAY_PROHIBITED');
        }
    }

    private function authority(array $r, array $p, array $s, string $root): void
    {
        $d = $r['decision']; $a = $r['authority']; $custody = $r['custody'];
        $this->sealed($d, ['schema', 'decision_id', 'principal', 'binding', 'operation', 'issuance_target', 'successor', 'creation_winner', 'at', 'expires_at', 'disposition', 'continuing_authority', 'record_digest'], 'imperium.imperator.native-transition-decision/v1');
        $this->sealed($custody, ['schema', 'custody_id', 'source_decision', 'target', 'delivery', 'serialized_capability_persisted', 'record_digest'], 'imperium.imperator.native-transition-custody/v1');
        $this->sealed($a, ['schema', 'authority_id', 'source_decision', 'custody', 'issuance_target', 'authority_single_use', 'continuing_authority', 'record_digest'], 'imperium.imperator.native-transition-authority/v1');
        $id = 'native-authority-'.TransitionContract::digest([$p['instance_id'], $p['principal_version_id'], $p['operation'], $p['provider_binding'], $s['successor']['record_digest']]);
        $target = ['authority_id' => $id, 'consumer' => TransitionContract::CONSUMER, 'transition' => TransitionContract::SCOPE, 'root' => $root, 'authority_single_use' => true];
        if ($r['schema'] !== 'imperium.imperator.native-transition-issuance/v1' || $d['principal'] !== $r['principal']
            || $d['decision_id'] !== 'decision-'.$root || $d['binding'] !== $p['provider_binding'] || $d['operation'] !== $p['operation']
            || $d['successor'] !== NativeState::ref($s['successor'], 'successor_id') || $d['creation_winner'] !== NativeState::ref($s['creation_winner'], 'winner_boundary_id')
            || $d['issuance_target'] !== $target || $d['at'] !== $r['at'] || $d['at'] < $s['at'] || $d['expires_at'] !== $p['expires_at']
            || $d['disposition'] !== 'AUTHORIZED_EXACT_TRANSITION' || false !== $d['continuing_authority']
            || $custody['custody_id'] !== 'custody-'.$root || $custody['source_decision'] !== NativeState::ref($d, 'decision_id')
            || $custody['target'] !== $target || $custody['delivery'] !== 'EXACT_CONSUMER_LOAD_ONLY' || false !== $custody['serialized_capability_persisted']
            || $a['authority_id'] !== $id || $a['source_decision'] !== NativeState::ref($d, 'decision_id')
            || $a['custody'] !== NativeState::ref($custody, 'custody_id') || $a['issuance_target'] !== $target
            || true !== $a['authority_single_use'] || false !== $a['continuing_authority']) { throw new \RuntimeException(); }
    }

    private function successor(array $r, array $p, int $at, string $root): void
    {
        TransitionContract::keys($r, ['schema', 'principal', 'activation_production', 'target', 'decision', 'successor', 'creation_winner', 'at']);
        $s = $r['successor']; $d = $r['decision']; $t = $r['target']; $w = $r['creation_winner'];
        $this->sealed($s, Successor::REQUIRED_FIELDS, Successor::SCHEMA);
        $this->sealed($t, ['schema', 'target_id', 'instance_id', 'binding', 'activation', 'operation_scope', 'replay_contention_root', 'record_digest'], 'imperium.imperator.native-successor-target/v1');
        $this->sealed($d, ['schema', 'decision_id', 'principal', 'target', 'permitted_transition', 'authority_single_use', 'at', 'continuing_authority', 'record_digest'], 'imperium.imperator.native-successor-creation-decision/v1');
        $this->sealed($w, ['schema', 'winner_boundary_id', 'instance_id', 'source_decision', 'successor', 'replay_contention_root', 'authority_consumed', 'successor_created', 'effect_started', 'continuing_authority', 'record_digest'], 'imperium.la-cortine.native-successor-creation-winner/v1');
        $basis = $p['root_act']['act']['execution_basis'];
        if (null === $basis || $basis['activation'] !== $s['active_principal_activation'] || $basis['production'] !== $r['activation_production']) { throw new \RuntimeException(); }
        $a = $this->state->source('activation', $basis['activation']);
        $production = $this->state->source('production', $basis['production']);
        $b = $this->state->source('binding', $p['provider_binding']);
        $assurance = $this->state->source('assurance', $a['provider_assurance_admission']);
        $boundary = $this->state->source('boundary', $a['execution_boundary']);
        $attestation = $this->state->source('attestation', $a['principal_attestation']);
        (new ProviderExecutorPrincipalActivationContractValidator())->assertActivation($a, $production['activation_decision'], $attestation, $assurance, $boundary, new \DateTimeImmutable('@'.$at));
        if (NativeState::ref($production['activation_decision'], 'decision_id') !== $a['source_decision'] || true !== $production['combined_winner']
            || true !== $production['consumed_issuance_authorization']['consumed'] || 'ACTIVE' !== $production['effective_principal_status']) { throw new \RuntimeException(); }
        $scope = ['provider_id' => $a['scope']['provider_id'], 'operation' => $p['operation'], 'principal_id' => $a['principal']['principal_id'],
            'principal_generation' => $a['principal']['generation'], 'process_boundary_id' => $a['principal']['process_boundary_id'],
            'provider_substitution_permitted' => false, 'operation_substitution_permitted' => false, 'principal_generation_substitution_permitted' => false, 'binding_substitution_permitted' => false];
        $id = 'successor-'.TransitionContract::digest([$root, $a['record_digest'], $p['record_digest']]);
        $rootFields = ['root_id' => $root, 'instance_id' => $p['instance_id'], 'principal_activation_id' => $a['principal_activation_id'],
            'binding_id' => $b['binding_id'], 'provider_id' => $scope['provider_id'], 'operation' => $p['operation']];
        $consumed = [...NativeState::ref($d, 'decision_id'), 'consumed_at' => gmdate(DATE_ATOM, $r['at']), 'consumed' => true, 'continuing_authority' => false];
        $expires = min($p['expires_at'], strtotime($a['validity']['expires_at']), strtotime($b['validity']['expires_at']), strtotime($assurance['validity']['review_due_at']));
        if ($r['schema'] !== 'imperium.la-cortine.native-successor-production/v1' || $r['principal'] !== NativeState::ref($p, 'principal_version_id')
            || true !== ($p['preserved_scope']['provider_binding_activation_authority'] ?? null)
            || !is_int($r['at']) || $r['at'] < $p['constituted_at'] || $at < $r['at'] || $at >= $expires
            || $s['successor_id'] !== $id || $s['instance_id'] !== $p['instance_id'] || $a['instance_id'] !== $p['instance_id']
            || $b['instance_id'] !== $p['instance_id'] || $b['status'] !== 'BOUND_INACTIVE' || $b['scope']['operation'] !== $p['operation']
            || $at < strtotime($b['validity']['effective_at']) || $b['provider_implementation']['provider_id'] !== $scope['provider_id']
            || $s['provider_binding_descriptor'] !== $p['provider_binding'] || $s['source_decision'] !== NativeState::ref($d, 'decision_id')
            || $s['successor_target'] !== NativeState::ref($t, 'target_id') || $s['provider_assurance_admission'] !== $a['provider_assurance_admission']
            || $s['execution_boundary'] !== $a['execution_boundary'] || $s['operation_scope'] !== $scope || $s['replay_contention_root'] !== $rootFields
            || $s['consumed_activation_authority'] !== $consumed || $s['status'] !== 'OPERATION_SCOPED_BINDING_ACTIVE'
            || $s['validity'] !== ['effective_at' => gmdate(DATE_ATOM, $r['at']), 'expires_at' => gmdate(DATE_ATOM, $expires), 'revocation_reference' => null]
            || $s['reconstruction'] !== Successor::RECONSTRUCTION_INVARIANTS || $s['activated_at'] !== gmdate(DATE_ATOM, $r['at']) || true !== $s['sealed']
            || $t['target_id'] !== 'target-'.$id || $t['instance_id'] !== $p['instance_id'] || $t['binding'] !== $p['provider_binding']
            || $t['activation'] !== $basis['activation'] || $t['operation_scope'] !== $scope || $t['replay_contention_root'] !== $root
            || $d['decision_id'] !== 'decision-'.$id || $d['principal'] !== $r['principal'] || $d['target'] !== NativeState::ref($t, 'target_id')
            || $d['permitted_transition'] !== 'CREATE_EXACT_OPERATION_SCOPED_PROVIDER_BINDING_SUCCESSOR' || true !== $d['authority_single_use']
            || $d['at'] !== $r['at'] || false !== $d['continuing_authority'] || $w['winner_boundary_id'] !== 'winner-'.$id
            || $w['instance_id'] !== $p['instance_id'] || $w['source_decision'] !== NativeState::ref($d, 'decision_id')
            || $w['successor'] !== NativeState::ref($s, 'successor_id') || $w['replay_contention_root'] !== $root
            || true !== $w['authority_consumed'] || true !== $w['successor_created'] || false !== $w['effect_started'] || false !== $w['continuing_authority']) { throw new \RuntimeException(); }
        foreach (Successor::REQUIRED_INVARIANTS as $key => $value) { if ($s[$key] !== $value) { throw new \RuntimeException(); } }
        foreach (glob($this->state->root.'/'.NativeState::SOURCES['activation'].'/*.json') ?: [] as $path) {
            $other = $this->state->json(NativeState::SOURCES['activation'].'/'.basename($path));
            if (($other['instance_id'] ?? null) === $p['instance_id'] && ($other['principal']['principal_id'] ?? null) === $a['principal']['principal_id']
                && ($other['principal']['generation'] ?? 0) > $a['principal']['generation']
                && (false === strtotime($other['activated_at'] ?? '') || strtotime($other['activated_at']) <= $at)) { throw new \RuntimeException(); }
        }
    }

    private function records(array $c, array $chain, array $s, string $root, int $at): array
    {
        $r = $c['records']; TransitionContract::keys($r, TransitionContract::WRITE_SET);
        if (array_keys($r) !== TransitionContract::WRITE_SET) { throw new \RuntimeException(); }
        foreach ($r as $record) {
            $plain = $record; unset($plain['record_digest']);
            if (($record['record_digest'] ?? null) !== TransitionContract::digest($plain)) { throw new \RuntimeException(); }
        }
        $a = $r['v3_admission']; (new GovernedProviderExecutionSuccessorAdmissionV3ContractValidator())->assertResult($a);
        $consume = $r['authority_consumption']; $adopt = $r['adoption_join']; $t = $adopt['target'];
        $this->sealed($consume, ['schema', 'consumption_id', 'authority', 'root', 'consumer', 'consumed', 'at', 'continuing_authority', 'record_digest'], 'imperium.la-cortine.native-transition-consumption/v1');
        $this->sealed($adopt, ['schema', 'adoption_id', 'target', 'consumption', 'admission', 'status', 'record_digest'], 'imperium.la-cortine.native-successor-adoption/v1');
        $this->sealed($t, ['schema', 'adoption_target_id', 'successor', 'authority_id', 'replay_contention_root', 'operation_scope', 'record_digest'], 'imperium.la-cortine.native-successor-adoption-target/v1');
        $source = $r['source_binding_transition']; $activation = $r['successor_binding_activation']; $w = $r['winner_target']; $receipt = $r['receipt_target'];
        $this->sealed($source, ['schema', 'binding', 'root', 'operation', 'descriptor_status', 'original_binding_mutated', 'record_digest'], 'imperium.la-cortine.native-source-binding-transition/v1');
        $this->sealed($activation, ['schema', 'successor', 'root', 'operation', 'status', 'provider_effect_started', 'record_digest'], 'imperium.la-cortine.native-successor-binding-activation/v1');
        $this->sealed($w, ['schema', 'winner_id', 'root', 'records_digest', 'at', 'record_digest'], 'imperium.la-cortine.native-transition-winner/v1');
        $this->sealed($receipt, ['schema', 'receipt_id', 'root', 'winner', 'authority_id', 'successor', 'operation', 'at', 'outcome', 'provider_invoked', 'external_io_started', 'retry_authorized', 'record_digest'], 'imperium.la-cortine.native-transition-receipt/v1');
        $successorRef = NativeState::ref($s['successor'], 'successor_id'); $operation = $s['successor']['operation_scope']['operation'];
        if ($consume['authority'] !== NativeState::ref($chain['authority'], 'authority_id') || $consume['root'] !== $root
            || $consume['consumption_id'] !== 'consumption-'.$root || $consume['consumer'] !== TransitionContract::CONSUMER
            || true !== $consume['consumed'] || $consume['at'] !== $at || false !== $consume['continuing_authority']
            || $a['schema'] !== V3::SCHEMA || $a['admission_boundary_id'] !== 'admission-'.$root || $a['instance_id'] !== $s['successor']['instance_id']
            || $a['completed_successor'] !== $successorRef || $a['atomic_creation_winner'] !== NativeState::ref($s['creation_winner'], 'winner_boundary_id')
            || $a['adoption_target'] !== NativeState::ref($t, 'adoption_target_id') || $a['executor_principal'] !== $s['successor']['active_principal_activation']
            || $a['execution_boundary'] !== $s['successor']['execution_boundary'] || $a['operation_scope'] !== $s['successor']['operation_scope']
            || $a['replay_contention_root'] !== $root || $t['adoption_target_id'] !== 'adoption-'.$root || $t['successor'] !== $successorRef
            || $t['authority_id'] !== $c['authority_id'] || $t['replay_contention_root'] !== $root || $t['operation_scope'] !== $a['operation_scope']
            || $adopt['adoption_id'] !== 'adopted-'.$root || $adopt['consumption'] !== NativeState::ref($consume, 'consumption_id')
            || $adopt['admission'] !== NativeState::ref($a, 'admission_boundary_id') || $adopt['status'] !== 'ADOPTED_PRE_EFFECT'
            || $source['binding'] !== $s['successor']['provider_binding_descriptor'] || $source['root'] !== $root || $source['operation'] !== $operation
            || $source['descriptor_status'] !== 'BOUND_INACTIVE' || false !== $source['original_binding_mutated']
            || $activation['successor'] !== $successorRef || $activation['root'] !== $root || $activation['operation'] !== $operation
            || $activation['status'] !== 'BOUND_ACTIVE_FOR_EXACT_OPERATION' || false !== $activation['provider_effect_started']
            || $w['root'] !== $root || $w['winner_id'] !== 'winner-'.$root || $w['at'] !== $at
            || $w['records_digest'] !== TransitionContract::digest(array_slice($r, 0, 5, true))
            || $receipt['root'] !== $root || $receipt['receipt_id'] !== 'receipt-'.$root || $receipt['winner'] !== NativeState::ref($w, 'winner_id')
            || $receipt['authority_id'] !== $c['authority_id'] || $receipt['successor'] !== $successorRef || $receipt['operation'] !== $operation
            || $receipt['at'] !== $at || $receipt['outcome'] !== 'COMMITTED_PRE_EFFECT' || false !== $receipt['provider_invoked']
            || false !== $receipt['external_io_started'] || false !== $receipt['retry_authorized']) { throw new \RuntimeException(); }
        return $receipt;
    }

    private function migration(array $journal, string $instance): void
    {
        $m = $this->state->json(NativeState::TRUST.'/migration.json');
        TransitionContract::keys($m, ['schema', 'storage', 'instance', 'inventory_complete', 'legacy_directories']);
        if ($m['schema'] !== 'imperium.operator-root.transition-migration-inventory/v1' || $m['storage'] !== $this->state->identity()
            || $m['instance'] !== $instance || !is_array($m['legacy_directories']) || !array_is_list($m['legacy_directories'])
            || true !== $m['inventory_complete'] || TransitionContract::digest($m) !== $journal['manifest_digest']) { throw new \RuntimeException(); }
        $identities = [];
        foreach ($m['legacy_directories'] as $dir) {
            if (!preg_match('~^var/imperium/runtime/legacy-provider-transitions/[a-z0-9][a-z0-9._-]{2,100}$~D', $dir) || str_contains($dir, '..')) { throw new \RuntimeException(); }
            $store = new TransitionStore($this->state->root.'/'.$dir); $identities[] = $store->identity();
            if ($store->pending('retirement') || $store->read('retirement') !== ['schema' => 'imperium.native-transition-legacy-retirement/v1',
                'legacy_storage' => $store->identity(), 'native_storage' => $this->state->identity(), 'root' => $journal['root'],
                'journal' => NativeState::ref($journal, 'journal_id'), 'retry_authorized' => false]) { throw new \RuntimeException(); }
            foreach (['grant', 'authority', 'journal', 'commit', 'refusal', 'revocation'] as $name) {
                if ($store->pending($name) || null !== $store->read($name)) { throw new \RuntimeException(); }
            }
        }
        sort($identities);
        if (count(array_unique($identities)) !== count($identities) || $identities !== $journal['legacy_storage_identities']) { throw new \RuntimeException(); }
    }

    private function sealed(array $r, array $fields, string $schema): void
    {
        TransitionContract::keys($r, $fields); $plain = $r; unset($plain['record_digest']);
        if ($r['schema'] !== $schema || $r['record_digest'] !== TransitionContract::digest($plain)) { throw new \RuntimeException(); }
    }

    private function snapshot(): array
    {
        $result = [];
        foreach ([NativeState::DIRECTORY, ...array_values(NativeState::SOURCES), NativeState::TRUST, 'var/imperium/runtime/legacy-provider-transitions'] as $dir) {
            $base = $this->state->root.'/'.$dir;
            if (is_link($base)) { throw new \RuntimeException(); }
            if (!is_dir($base)) { continue; }
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST) as $file) {
                if ($file->isLink()) { throw new \RuntimeException(); }
                if ($file->isDir()) { $result[$file->getPathname()] = 'directory'; }
                elseif (in_array($file->getExtension(), ['json', 'pending'], true)) { $result[$file->getPathname()] = hash_file('sha256', $file->getPathname()); }
            }
        }
        ksort($result); return $result;
    }

    private function result(string $classification, ?array $receipt = null): array
    {
        return ['classification' => $classification, 'receipt' => $receipt, 'read_only' => true,
            'execution_authority' => false, 'retry_authorized' => false, 'provider_effect_started' => false];
    }

    private function orphanRetirement(string $root): bool
    {
        foreach (glob($this->state->root.'/var/imperium/runtime/legacy-provider-transitions/*', GLOB_ONLYDIR) ?: [] as $dir) {
            $store = new TransitionStore($dir);
            if ($store->pending('retirement')) { return true; }
            $record = $store->read('retirement');
            if (null !== $record && (!isset($record['root']) || $record['root'] === $root)) { return true; }
        }
        return false;
    }
}
