<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Bootstrap\CanonicalJson;

/** Public verification only; the runtime cannot install, rotate or sign a Root identity. */
final readonly class NativeRootActs
{
    public const string SCHEMA = 'imperium.operator-root.transition-act/v1';
    public const array FIELDS = ['schema', 'act_id', 'anchor_id', 'purpose', 'action', 'operator', 'instance',
        'storage', 'source_principal', 'preserved_scope', 'source_generation', 'target_generation',
        'scope', 'binding', 'operation', 'target_id', 'operationalization', 'execution_basis', 'effective_at', 'expires_at'];

    public function __construct(private NativeState $state) {}

    public function verify(array $envelope, int $at): array
    {
        TransitionContract::keys($envelope, ['act', 'signature']);
        $a = $envelope['act'];
        if (!is_array($a)) { throw new \RuntimeException('NIR_ROOT_ACT'); }
        TransitionContract::keys($a, self::FIELDS);
        $anchor = $this->state->json(NativeState::TRUST.'/identity.json');
        TransitionContract::keys($anchor, ['schema', 'anchor_id', 'operator', 'instance', 'public_key', 'effective_at', 'expires_at', 'revoked']);
        foreach (['act_id', 'anchor_id', 'operator', 'instance', 'operation', 'target_id'] as $key) { NativeState::id($a[$key]); }
        foreach (['effective_at', 'expires_at'] as $key) {
            if (!is_int($a[$key]) || !is_int($anchor[$key])) { throw new \RuntimeException('NIR_ROOT_TIME'); }
        }
        if ('imperium.operator-root.transition-trust/v1' !== $anchor['schema'] || false !== $anchor['revoked']
            || $a['anchor_id'] !== $anchor['anchor_id'] || $a['operator'] !== $anchor['operator'] || $a['instance'] !== $anchor['instance']
            || self::SCHEMA !== $a['schema'] || 'CONSTITUTE_EXACT_NATIVE_TRANSITION_SCOPE' !== $a['purpose']
            || !in_array($a['action'], ['CONSTITUTE', 'ACTIVATE', 'REVOKE'], true)
            || $a['scope'] !== TransitionContract::SCOPE || $a['storage'] !== $this->state->identity()
            || !is_int($a['source_generation']) || $a['source_generation'] < 1
            || $a['target_generation'] !== $a['source_generation'] + 1
            || $at < $a['effective_at'] || $at >= $a['expires_at'] || $a['effective_at'] < $anchor['effective_at']
            || $a['expires_at'] > $anchor['expires_at'] || $anchor['effective_at'] < 0) { throw new \RuntimeException('NIR_ROOT_INELIGIBLE'); }
        NativeState::reference($a['source_principal']); NativeState::reference($a['binding']);
        if (null !== $a['execution_basis']) {
            if (!is_array($a['execution_basis'])) { throw new \RuntimeException('NIR_ROOT_EXECUTION_BASIS'); }
            TransitionContract::keys($a['execution_basis'], ['activation', 'production']);
            NativeState::reference($a['execution_basis']['activation']); NativeState::reference($a['execution_basis']['production']);
            if ($a['execution_basis']['activation']['schema'] !== \App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationContract::SCHEMA
                || $a['execution_basis']['production']['schema'] !== \App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceProductionContract::SCHEMA) {
                throw new \RuntimeException('NIR_ROOT_EXECUTION_BASIS');
            }
        }
        NativeState::reference($a['operationalization']);
        $seal = $this->state->json('var/imperium/operator-root/operationalization-seal.json');
        $plain = $seal; unset($plain['record_digest']);
        if (($seal['record_digest'] ?? null) !== TransitionContract::digest($plain)
            || ($seal['schema'] ?? null) !== 'imperium.operator-root-operationalization-seal/v1'
            || $a['operationalization'] !== NativeState::ref($seal, 'seal_id')
            || ($seal['instance_id'] ?? null) !== $a['instance'] || ($seal['status'] ?? null) !== 'IMPERIUM_OPERATIONAL') {
            throw new \RuntimeException('NIR_OPERATIONALIZATION');
        }
        if (!is_array($a['preserved_scope'])) { throw new \RuntimeException('NIR_ROOT_SCOPE'); }
        $allowed = \App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionV3Contract::REQUIRED_AUTHORITY_SCOPE_FIELDS;
        foreach ($a['preserved_scope'] as $key => $value) {
            if (!in_array($key, $allowed, true) || !is_bool($value)) { throw new \RuntimeException('NIR_ROOT_SCOPE'); }
        }
        if (!is_string($anchor['public_key']) || !preg_match('/^[a-f0-9]{64}$/D', $anchor['public_key'])
            || !is_string($envelope['signature']) || !preg_match('/^[a-f0-9]{128}$/D', $envelope['signature'])
            || !function_exists('sodium_crypto_sign_verify_detached')
            || !sodium_crypto_sign_verify_detached(hex2bin($envelope['signature']), CanonicalJson::encode($a), hex2bin($anchor['public_key']))) {
            throw new \RuntimeException('NIR_ROOT_SIGNATURE');
        }
        return $a;
    }
}
