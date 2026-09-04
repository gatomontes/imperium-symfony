<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\ProviderTransition\{NativeState, NativeRootActs, NativePrincipal, NativeAuthority, TransitionContract};
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionContract as V2;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3).'/vendor/autoload.php';

class NativeTransitionBatch1Test extends TestCase
{
    protected string $root;
    protected NativeState $state;
    protected string $secret;
    protected array $source;
    protected array $anchor;
    protected array $act;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/native-transition-'.bin2hex(random_bytes(8));
        mkdir($this->root, 0770, true);
        $this->state = new NativeState($this->root);
        // Synthetic test keys only; the application contains no signer or trust provisioner.
        $pair = sodium_crypto_sign_keypair(); $this->secret = sodium_crypto_sign_secretkey($pair);
        $this->anchor = ['schema' => 'imperium.operator-root.transition-trust/v1', 'anchor_id' => 'root-key-1',
            'operator' => 'operator-test', 'instance' => 'imperium-test', 'public_key' => bin2hex(sodium_crypto_sign_publickey($pair)),
            'effective_at' => 1, 'expires_at' => 900, 'revoked' => false];
        $this->write(NativeState::TRUST.'/identity.json', $this->anchor);
        $ref = ['id' => 'source-test', 'digest' => str_repeat('a', 64), 'schema' => 'imperium.source/v1'];
        $scope = ['provider_binding_activation_authority' => true, 'outbound_email_authority' => false,
            'credential_authority' => false, 'provider_execution_authority' => false, 'corridor_disposition_authority' => false];
        $this->source = NativeState::seal(['schema' => V2::SCHEMA, 'principal_version_id' => 'principal-v2',
            'principal_id' => 'imperator-test', 'instance_id' => 'imperium-test', 'binding_id' => 'imperator-binding',
            'principal_generation' => 1, 'constitution_route' => 'EXISTING_INSTANCE_REMEDIATION',
            'source_constitution_authority' => $ref, 'source_operator_root' => $ref,
            'identity' => ['operator_id' => 'operator-test', 'operator_identity_digest' => str_repeat('a', 64),
                'imperator_subject_id' => 'subject-test', 'imperator_subject_digest' => str_repeat('b', 64)],
            'authority_scope' => $scope,
            'lifecycle' => ['constituted_at' => gmdate(DATE_ATOM, 1), 'effective_at' => gmdate(DATE_ATOM, 1),
                'expires_at' => gmdate(DATE_ATOM, 900), 'prior_version' => null, 'superseding_version' => null, 'current_disposition' => null],
            'status' => 'ACTIVE', 'credential_reference_persisted' => false, 'credential_secret_persisted' => false,
            'serialized_capability_persisted' => false, 'sealed' => true]);
        $this->write(NativeState::SOURCES['principal'].'/principal-v2.json', $this->source);
        $seal = NativeState::seal(['schema' => 'imperium.operator-root-operationalization-seal/v1',
            'seal_id' => 'operationalization-test', 'instance_id' => 'imperium-test', 'status' => 'IMPERIUM_OPERATIONAL']);
        $this->write('var/imperium/operator-root/operationalization-seal.json', $seal);
        $this->act = ['schema' => NativeRootActs::SCHEMA, 'act_id' => 'constitute-test', 'anchor_id' => 'root-key-1',
            'purpose' => 'CONSTITUTE_EXACT_NATIVE_TRANSITION_SCOPE', 'action' => 'CONSTITUTE', 'operator' => 'operator-test',
            'instance' => 'imperium-test', 'storage' => $this->state->identity(), 'source_principal' => NativeState::ref($this->source, 'principal_version_id'),
            'preserved_scope' => $scope, 'source_generation' => 1, 'target_generation' => 2, 'scope' => TransitionContract::SCOPE,
            'binding' => ['id' => 'provider-binding', 'digest' => str_repeat('d', 64), 'schema' => 'imperium.provider-binding/v1'],
            'operation' => 'mail.send', 'target_id' => 'native-principal-'.TransitionContract::digest(['imperium-test', 'imperator-test', 2]),
            'operationalization' => NativeState::ref($seal, 'seal_id'), 'execution_basis' => null, 'effective_at' => 50, 'expires_at' => 800];
    }

    protected function tearDown(): void
    {
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
        rmdir($this->root); sodium_memzero($this->secret);
    }

    /** Canonical typed reconciliation-issuance path used by downstream fixtures. */
    protected function issueReconciliationAuthority(array $admission, int $at, int $expiresAt, ?\Closure $checkpoint = null): array
    {
        $authorization = (new \App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorizationService($this->state))
            ->authorize($admission['admission_id'], $at, $expiresAt);
        $resolver = new \App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $capability = $resolver->resolve($authorization['issuance_authority']['issuance_authority_id'], $at);
        return (new \App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService($this->state, $resolver, $checkpoint))
            ->issue($capability, $at);
    }

    public function testRootConstitutionRequiresSeparateActivationAndNativeAuthorityUsesBackwardSeals(): void
    {
        $service = new NativePrincipal($this->state, static fn () => 100);
        $p = $service->constitute($this->sign($this->act));
        self::assertSame('PENDING_ACTIVATION', $p['status']);
        $this->fails('NIR_PRINCIPAL_INACTIVE', fn () => $service->load($p['principal_version_id'], 100));
        $active = $this->act; $active['action'] = 'ACTIVATE'; $active['act_id'] = 'activate-test';
        $service->lifecycle($p['principal_version_id'], $this->sign($active));
        $authority = (new NativeAuthority($this->state, static fn () => 100))->issue($p['principal_version_id']);
        self::assertSame(NativeState::ref($authority['decision'], 'decision_id'), $authority['authority']['source_decision']);
        self::assertSame(NativeState::ref($authority['custody'], 'custody_id'), $authority['authority']['custody']);
        self::assertArrayNotHasKey('digest', $authority['decision']['issuance_target']);
        self::assertSame($authority, (new NativeAuthority($this->state))->load($authority['authority']['authority_id'], 101));
        self::assertSame($this->source, $this->state->source('principal', $this->act['source_principal']));
    }

    public function testWrongKeyPurposeInstanceAndExtraCapabilityAreRejectedBeforeWrites(): void
    {
        foreach (['purpose' => 'VERIFY_OFFLINE_PROOF', 'instance' => 'other-instance', 'scope' => 'ANY_TRANSITION'] as $key => $value) {
            $a = $this->act; $a[$key] = $value;
            $this->fails('NIR_ROOT_INELIGIBLE', fn () => (new NativePrincipal($this->state, static fn () => 100))->constitute($this->sign($a)));
        }
        $e = $this->sign($this->act); $e['signature'] = str_repeat('a', 128);
        $this->fails('NIR_ROOT_SIGNATURE', fn () => (new NativeRootActs($this->state))->verify($e, 100));
        $e = $this->sign($this->act); $e['act']['capability_token'] = 'forbidden';
        $this->fails('EAT_UNEXPECTED_FIELDS', fn () => (new NativeRootActs($this->state))->verify($e, 100));
        self::assertSame([], $this->state->ids('principals'));
    }

    public function testExpiryRevocationAndNativeGenerationChangeRefuse(): void
    {
        $p = $this->activate();
        $this->fails('NIR_ROOT_INELIGIBLE', fn () => (new NativePrincipal($this->state))->load($p['principal_version_id'], 800));
        $a = $this->act; $a['action'] = 'REVOKE'; $a['act_id'] = 'revoke-test';
        (new NativePrincipal($this->state, static fn () => 102))->lifecycle($p['principal_version_id'], $this->sign($a));
        $this->fails('NIR_PRINCIPAL_NOT_CURRENT', fn () => (new NativeAuthority($this->state, static fn () => 103))->issue($p['principal_version_id']));
        $this->source['principal_generation'] = 2;
        $this->write(NativeState::SOURCES['principal'].'/another-principal.json', NativeState::seal($this->source));
        $this->fails('NIR_SOURCE_GENERATION_CHANGED', fn () => (new NativePrincipal($this->state))->load($p['principal_version_id'], 100));
    }

    public function testRevokedOrMissingTrustAnchorDoesNotFallBack(): void
    {
        $this->anchor['revoked'] = true; $this->write(NativeState::TRUST.'/identity.json', $this->anchor);
        $this->fails('NIR_ROOT_INELIGIBLE', fn () => (new NativeRootActs($this->state))->verify($this->sign($this->act), 100));
        unlink($this->root.'/'.NativeState::TRUST.'/identity.json');
        $this->fails('NIR_SOURCE_ABSENT', fn () => (new NativeRootActs($this->state))->verify($this->sign($this->act), 100));
    }

    protected function activate(): array
    {
        $service = new NativePrincipal($this->state, static fn () => 100);
        $p = $service->constitute($this->sign($this->act));
        $a = $this->act; $a['action'] = 'ACTIVATE'; $a['act_id'] = 'activate-test';
        $service->lifecycle($p['principal_version_id'], $this->sign($a));
        return $p;
    }

    protected function sign(array $act): array
    {
        return ['act' => $act, 'signature' => bin2hex(sodium_crypto_sign_detached(CanonicalJson::encode($act), $this->secret))];
    }

    protected function write(string $path, array $r): void
    {
        $file = $this->root.'/'.$path;
        if (!is_dir(dirname($file))) { mkdir(dirname($file), 0770, true); }
        file_put_contents($file, json_encode($r, JSON_THROW_ON_ERROR));
    }

    protected function fails(string $message, callable $action): void
    {
        try { $action(); self::fail('Expected '.$message); }
        catch (\RuntimeException $e) { self::assertSame($message, $e->getMessage()); }
    }
}
