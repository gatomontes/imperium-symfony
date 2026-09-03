<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAdmissionValidator;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAuthorityContract;
use App\Imperium\Runtime\ProviderTransition\NativeState;

require_once __DIR__.'/NativeTransitionBatch4Test.php';

class CanonicalNativeEffectCorridorActivationBatch2Test extends NativeTransitionBatch4Test
{
    public function testExactCurrentNativeRootProducesOnlyAnInertView(): void
    {
        [$authorityId, $at] = $this->readyTransition();
        $result = (new NativeConsumer($this->state, static fn () => $at))->execute($authorityId);
        $authority = $this->effectAuthority($result['root'], $at);
        $before = $this->files();

        $view = (new NativeEffectAdmissionValidator($this->state))->inspect($authority, $at);

        self::assertTrue($view['eligible_for_future_atomic_admission']);
        self::assertTrue($view['read_only']);
        foreach (['effect_grant_used', 'credential_resolved', 'capability_consumed', 'provider_callback_permitted', 'provider_invoked', 'external_io_started', 'retry_authorized'] as $field) {
            self::assertFalse($view[$field]);
        }
        self::assertSame($before, $this->files());
    }

    public function testChangedNativeReceiptOrPayloadFailsClosed(): void
    {
        [$authorityId, $at] = $this->readyTransition();
        $result = (new NativeConsumer($this->state, static fn () => $at))->execute($authorityId);
        $authority = $this->effectAuthority($result['root'], $at);
        $authority['native_receipt']['digest'] = str_repeat('0', 64);
        $authority = NativeState::seal($authority);
        $this->fails('CNE202_NATIVE_RECEIPT_NOT_CURRENT', fn () => (new NativeEffectAdmissionValidator($this->state))->inspect($authority, $at));

        $authority = $this->effectAuthority($result['root'], $at);
        $authority['payload_digest'] = str_repeat('1', 64);
        $authority = NativeState::seal($authority);
        $view = (new NativeEffectAdmissionValidator($this->state))->inspect($authority, $at);
        self::assertNotSame(NativeEffectAdmissionValidator::replayIdentity($this->effectAuthority($result['root'], $at)), $view['effect_replay_identity']);
    }

    public function testValidatorHasNoCredentialOrProviderCallbackDependency(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/NativeEffectAdmissionValidator.php');
        foreach (['CredentialBroker', 'CredentialCapability', 'AgentMail', 'HttpClient', 'IronGate', 'Lazaretto', 'file_put_contents'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    protected function effectAuthority(string $root, int $at): array
    {
        $commit = $this->state->get('transitions', $root);
        $admission = $commit['records']['v3_admission'];
        $receipt = $commit['records']['receipt_target'];
        $descriptor = $this->state->json(NativeState::SOURCES['binding'].'/provider-binding.json');
        $record = [
            'schema' => NativeEffectAuthorityContract::SCHEMA,
            'authority_id' => 'native-effect-authority-'.$root,
            'instance_id' => 'imperium-test',
            'native_root' => $root,
            'native_transition' => ['id' => $root, 'schema' => $commit['schema'], 'digest' => $commit['record_digest']],
            'native_receipt' => NativeState::ref($receipt, 'receipt_id'),
            'successor' => $admission['completed_successor'],
            'v3_admission' => NativeState::ref($admission, 'admission_boundary_id'),
            'executor_principal' => $admission['executor_principal'],
            'execution_boundary' => $admission['execution_boundary'],
            'provider_binding' => NativeState::ref($descriptor, 'binding_id'),
            'operation' => 'email.send',
            'destination' => 'https://api.agentmail.to/v0/inboxes/disposable/messages/send',
            'payload_digest' => hash('sha256', '{"to":["disposable@example.test"]}'),
            'request_fingerprint' => hash('sha256', 'canonical-native-effect-request'),
            'provider' => [
                'provider_id' => $descriptor['provider_implementation']['provider_id'],
                'adapter_id' => $descriptor['provider_implementation']['adapter_id'],
                'adapter_version' => $descriptor['provider_implementation']['adapter_version'],
                'assurance_admission' => $this->state->get('successors', $admission['completed_successor']['id'])['successor']['provider_assurance_admission'],
            ],
            'credential_scope' => [
                'credential_family' => $descriptor['credential_family']['family_id'],
                'stationary_same_process' => true,
                'cross_process_transfer_permitted' => false,
                'secret_persistence_permitted' => false,
            ],
            'expected_return_contract' => 'agentmail.message/v1',
            'provider_idempotency_key_digest' => hash('sha256', 'disposable-idempotency-key'),
            'holder' => NativeEffectAuthorityContract::CONSUMER,
            'issuer' => 'imperator.canonical-native-effect-authority-issuer/v1',
            'effective_at' => $at,
            'expires_at' => $at + 300,
            'revocation_reference' => null,
            'cancellation_reference' => null,
            'single_use' => true,
            'consumed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ];
        return NativeState::seal($record);
    }

    private function files(): array
    {
        $files = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) { $files[$file->getPathname()] = hash_file('sha256', $file->getPathname()); }
        }
        ksort($files);
        return $files;
    }
}
