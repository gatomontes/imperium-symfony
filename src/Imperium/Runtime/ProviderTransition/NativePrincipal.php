<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Imperator\ImperatorPrincipalLifecycleReconstructionService;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionContract as V2;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionV3Contract as V3;

/** Native exact competence; separate signed lifecycle, no credentials or implicit upgrade. */
final readonly class NativePrincipal
{
    public const string SCHEMA = 'imperium.imperator-native-transition-principal/v1';
    public function __construct(private NativeState $state, private ?\Closure $clock = null) {}

    public function constitute(array $envelope): array
    {
        return $this->state->locked(function () use ($envelope): array {
            $at = $this->now();
            $a = (new NativeRootActs($this->state))->verify($envelope, $at);
            $source = $this->source($a, $at);
            $expected = $this->build($envelope, $source, $at);
            if ('CONSTITUTE' !== $a['action'] || $a['target_id'] !== $expected['principal_version_id']) { throw new \RuntimeException('NIR_CONSTITUTION_TARGET'); }
            return $this->state->put('principals', $expected['principal_version_id'], $expected);
        });
    }

    public function lifecycle(string $id, array $envelope): array
    {
        return $this->state->locked(function () use ($id, $envelope): array {
            $at = $this->now();
            $p = $this->load($id, $at, false);
            $a = (new NativeRootActs($this->state))->verify($envelope, $at);
            $this->lifecycleAct($p, $a);
            $kind = 'ACTIVATE' === $a['action'] ? 'activations' : 'revocations';
            if ('activations' === $kind && null !== $this->state->get('revocations', $id)) { throw new \RuntimeException('NIR_PRINCIPAL_REVOKED'); }
            $r = NativeState::seal(['schema' => 'imperium.operator-root.transition-lifecycle/v1',
                'principal' => NativeState::ref($p, 'principal_version_id'), 'root_act' => $envelope, 'at' => $at,
                'predecessor_transition_competence' => 'SUPERSEDED_FOR_EXACT_OPERATION', 'continuing_authority' => false]);
            return $this->state->put($kind, $id, $r);
        });
    }

    public function load(string $id, int $at, bool $active = true): array
    {
        $p = $this->state->get('principals', $id) ?? throw new \RuntimeException('NIR_PRINCIPAL_ABSENT');
        $a = (new NativeRootActs($this->state))->verify($p['root_act'] ?? [], $at);
        $source = $this->source($a, $at);
        if ('CONSTITUTE' !== $a['action'] || !is_int($p['constituted_at'] ?? null)
            || $p['constituted_at'] > $at || $id !== $a['target_id']
            || $p !== $this->build($p['root_act'], $source, $p['constituted_at'])) { throw new \RuntimeException('NIR_PRINCIPAL_INVALID'); }
        foreach (['activations' => 'ACTIVATE', 'revocations' => 'REVOKE'] as $kind => $action) {
            $event = $this->state->get($kind, $id);
            if (null === $event) {
                if ($active && 'activations' === $kind) { throw new \RuntimeException('NIR_PRINCIPAL_INACTIVE'); }
                continue;
            }
            $plain = $event; unset($plain['record_digest']);
            TransitionContract::keys($plain, ['schema', 'principal', 'root_act', 'at', 'predecessor_transition_competence', 'continuing_authority']);
            $act = (new NativeRootActs($this->state))->verify($event['root_act'], $event['at']);
            $this->lifecycleAct($p, $act);
            if ($event['schema'] !== 'imperium.operator-root.transition-lifecycle/v1'
                || $event['record_digest'] !== TransitionContract::digest($plain)
                || $event['principal'] !== NativeState::ref($p, 'principal_version_id')
                || $event['at'] < $p['constituted_at'] || $act['action'] !== $action
                || $event['predecessor_transition_competence'] !== 'SUPERSEDED_FOR_EXACT_OPERATION'
                || false !== $event['continuing_authority']) { throw new \RuntimeException('NIR_LIFECYCLE_INVALID'); }
            if ($active && ('revocations' === $kind && $event['at'] <= $at || 'activations' === $kind && $event['at'] > $at)) {
                throw new \RuntimeException('NIR_PRINCIPAL_NOT_CURRENT');
            }
        }
        return $p;
    }

    private function source(array $a, int $at): array
    {
        $p = $this->state->source('principal', $a['source_principal']);
        if (!in_array($p['schema'], [V2::SCHEMA, V3::SCHEMA], true) || array_keys($p) !== V2::REQUIRED_FIELDS
            || $p['principal_version_id'] !== $a['source_principal']['id'] || $p['instance_id'] !== $a['instance']
            || $p['identity']['operator_id'] !== $a['operator'] || $p['principal_generation'] !== $a['source_generation']
            || $p['authority_scope'] !== $a['preserved_scope'] || true !== $p['sealed']
            || false !== $p['credential_reference_persisted'] || false !== $p['credential_secret_persisted']
            || false !== $p['serialized_capability_persisted']) { throw new \RuntimeException('NIR_NATIVE_PRINCIPAL'); }
        $fields = V2::SCHEMA === $p['schema'] ? V2::REQUIRED_AUTHORITY_SCOPE_FIELDS : V3::REQUIRED_AUTHORITY_SCOPE_FIELDS;
        if (array_keys($p['authority_scope']) !== $fields
            || false === strtotime($p['lifecycle']['effective_at']) || false === strtotime($p['lifecycle']['expires_at'])
            || $at < strtotime($p['lifecycle']['effective_at']) || $at >= strtotime($p['lifecycle']['expires_at'])
            || null !== $p['lifecycle']['superseding_version']) { throw new \RuntimeException('NIR_SOURCE_PRINCIPAL_NOT_CURRENT'); }
        $status = $p['status'];
        if (V2::SCHEMA === $p['schema']) {
            $status = (new ImperatorPrincipalLifecycleReconstructionService($this->state->root))->reconstruct($p['principal_version_id'], new \DateTimeImmutable('@'.$at))['effective_status'];
        } else {
            // The old v2 loader cannot interpret v3. Do not silently ignore its dispositions.
            foreach (glob($this->state->root.'/'.NativeState::SOURCES['lifecycle'].'/*.json') ?: [] as $path) {
                $event = $this->state->json(NativeState::SOURCES['lifecycle'].'/'.basename($path));
                if (($event['source_principal_version']['id'] ?? null) === $p['principal_version_id']) { throw new \RuntimeException('NIR_V3_LIFECYCLE_REQUIRES_NATIVE_MIGRATION'); }
            }
        }
        if ('ACTIVE' !== $status) { throw new \RuntimeException('NIR_SOURCE_PRINCIPAL_NOT_ACTIVE'); }
        foreach (glob($this->state->root.'/'.NativeState::SOURCES['principal'].'/*.json') ?: [] as $path) {
            $other = $this->state->json(NativeState::SOURCES['principal'].'/'.basename($path));
            if (($other['instance_id'] ?? null) === $p['instance_id'] && ($other['principal_id'] ?? null) === $p['principal_id']
                && ($other['principal_generation'] ?? 0) > $p['principal_generation']) {
                $constituted = strtotime($other['lifecycle']['constituted_at'] ?? '');
                if (false === $constituted || $constituted <= $at) { throw new \RuntimeException('NIR_SOURCE_GENERATION_CHANGED'); }
            }
        }
        return $p;
    }

    private function build(array $envelope, array $source, int $at): array
    {
        $a = $envelope['act'];
        return NativeState::seal(['schema' => self::SCHEMA,
            'principal_version_id' => 'native-principal-'.TransitionContract::digest([$a['instance'], $source['principal_id'], $a['target_generation']]),
            'principal_id' => $source['principal_id'], 'instance_id' => $a['instance'], 'binding_id' => $source['binding_id'],
            'principal_generation' => $a['target_generation'], 'source_principal' => $a['source_principal'],
            'preserved_scope' => $source['authority_scope'], 'scope' => TransitionContract::SCOPE,
            'operation' => $a['operation'], 'provider_binding' => $a['binding'], 'root_act' => $envelope,
            'status' => 'PENDING_ACTIVATION', 'constituted_at' => $at, 'expires_at' => $a['expires_at'],
            'continuing_authority' => false]);
    }

    private function lifecycleAct(array $p, array $a): void
    {
        $expected = $p['root_act']['act']; $actual = $a;
        foreach (['action', 'act_id', 'effective_at', 'expires_at'] as $key) { unset($expected[$key], $actual[$key]); }
        if ($actual !== $expected || !in_array($a['action'], ['ACTIVATE', 'REVOKE'], true)) { throw new \RuntimeException('NIR_LIFECYCLE_TARGET'); }
    }

    private function now(): int { return null === $this->clock ? time() : ($this->clock)(); }
}
