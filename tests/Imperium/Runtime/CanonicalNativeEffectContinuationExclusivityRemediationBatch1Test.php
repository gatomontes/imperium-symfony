<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeEffectAuthorityContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReceiptInputContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectSemanticIdentity;
use App\Imperium\Runtime\ProviderTransition\NativeEffectTupleDispositionContract;
use App\Imperium\Runtime\ProviderTransition\NativeState;
use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectContinuationExclusivityRemediationBatch1Test extends TestCase
{
    public function testDistinctAuthoritiesForTheSameEffectShareOnlyTheSemanticTuple(): void
    {
        $first = $this->authority();
        $second = $first;
        $second['authority_id'] = 'native-effect-authority-second';
        $second['issuer'] = 'imperator.alternate-effect-authority-issuer/v1';
        $second['effective_at'] = 101;
        $second['expires_at'] = 501;
        $second = NativeState::seal($second);

        self::assertNotSame($first['record_digest'], $second['record_digest']);
        self::assertSame(NativeEffectSemanticIdentity::tuple($first), NativeEffectSemanticIdentity::tuple($second));
        self::assertSame(NativeEffectSemanticIdentity::tupleId($first), NativeEffectSemanticIdentity::tupleId($second));
        self::assertNotSame(
            NativeEffectSemanticIdentity::authorityConsumptionId($first),
            NativeEffectSemanticIdentity::authorityConsumptionId($second),
        );
    }

    public function testEverySemanticEffectFieldChangesTheTupleIdentity(): void
    {
        $authority = $this->authority();
        $expected = NativeEffectSemanticIdentity::tupleId($authority);
        $mutations = [
            static fn (array &$a) => $a['native_root'] = str_repeat('1', 64),
            static fn (array &$a) => $a['native_transition']['digest'] = str_repeat('2', 64),
            static fn (array &$a) => $a['native_receipt']['digest'] = str_repeat('3', 64),
            static fn (array &$a) => $a['successor']['digest'] = str_repeat('4', 64),
            static fn (array &$a) => $a['v3_admission']['digest'] = str_repeat('5', 64),
            static fn (array &$a) => $a['executor_principal']['digest'] = str_repeat('6', 64),
            static fn (array &$a) => $a['execution_boundary']['digest'] = str_repeat('7', 64),
            static fn (array &$a) => $a['provider_binding']['digest'] = str_repeat('8', 64),
            static fn (array &$a) => $a['operation'] = 'email.send.changed',
            static fn (array &$a) => $a['destination'] = 'https://provider.invalid/changed',
            static fn (array &$a) => $a['payload_digest'] = str_repeat('9', 64),
            static fn (array &$a) => $a['request_fingerprint'] = str_repeat('a', 64),
            static fn (array &$a) => $a['provider']['provider_id'] = 'provider-changed',
            static fn (array &$a) => $a['provider']['adapter_id'] = 'adapter-changed',
            static fn (array &$a) => $a['provider']['adapter_version'] = 'v2',
            static fn (array &$a) => $a['provider']['assurance_admission']['digest'] = str_repeat('b', 64),
            static fn (array &$a) => $a['credential_scope']['credential_family'] = 'credential-family-changed',
            static fn (array &$a) => $a['expected_return_contract'] = 'provider.message/v2',
            static fn (array &$a) => $a['provider_idempotency_key_digest'] = str_repeat('c', 64),
        ];

        foreach ($mutations as $index => $mutate) {
            $candidate = $authority;
            $mutate($candidate);
            $candidate = NativeState::seal($candidate);
            self::assertNotSame($expected, NativeEffectSemanticIdentity::tupleId($candidate), 'mutation '.$index);
        }
    }

    public function testAdmissionIdentityUsesTheCompleteSemanticTupleDigest(): void
    {
        $tupleId = NativeEffectSemanticIdentity::tupleId($this->authority());
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $tupleId);
        self::assertSame(NativeEffectSemanticIdentity::ADMISSION_PREFIX.$tupleId, NativeEffectSemanticIdentity::admissionId($tupleId));
        self::assertSame(64 + strlen(NativeEffectSemanticIdentity::ADMISSION_PREFIX), strlen(NativeEffectSemanticIdentity::admissionId($tupleId)));
    }

    public function testTamperedOldDigestAndMalformedIdentityInputsRefuse(): void
    {
        $tampered = $this->authority();
        $tampered['expected_return_contract'] = 'attacker.substitution/v1';
        $this->fails('CNE110_SEMANTIC_IDENTITY_AUTHORITY_INVALID', static fn () => NativeEffectSemanticIdentity::tupleId($tampered));
        $this->fails('CNE111_SEMANTIC_IDENTITY_DIGEST_INVALID', static fn () => NativeEffectSemanticIdentity::admissionId('short'));
    }

    public function testContinuationReceiptAndDispositionContractsPinTheFailClosedRules(): void
    {
        self::assertSame(1, NativeEffectContinuationCapabilityContract::MAX_USES);
        foreach (['exact_replay_may_mint', 'cross_process_transfer_permitted', 'durable_persistence_permitted', 'reconstruction_permitted'] as $rule) {
            self::assertFalse(NativeEffectContinuationCapabilityContract::REQUIRED_INVARIANTS[$rule], $rule);
        }
        self::assertTrue(NativeEffectContinuationCapabilityContract::REQUIRED_INVARIANTS['issuer_registry_object_identity_required']);
        self::assertTrue(NativeEffectContinuationCapabilityContract::REQUIRED_INVARIANTS['newly_published_winner_only']);
        self::assertTrue(NativeEffectContinuationCapabilityContract::REQUIRED_INVARIANTS['single_use']);

        foreach (['expected_return_contract', 'provider', 'provider_request', 'effect_authority'] as $field) {
            self::assertContains($field, NativeEffectReceiptInputContract::REQUIRED_FIELDS);
        }
        foreach (['authority', 'expected_return_contract', 'provider_id', 'destination'] as $input) {
            self::assertContains($input, NativeEffectReceiptInputContract::PROHIBITED_CALLER_INPUTS);
        }
        self::assertContains(NativeEffectTupleDispositionContract::WINNER, NativeEffectTupleDispositionContract::OUTCOMES);
        self::assertContains(NativeEffectTupleDispositionContract::LOSER, NativeEffectTupleDispositionContract::OUTCOMES);
        self::assertStringContainsString('AUTHORITY_UNCONSUMED', NativeEffectTupleDispositionContract::LOSER);
    }

    public function testNewDefinitionsHaveOnlyTheirGovernedLaterBatchCallSites(): void
    {
        $root = dirname(__DIR__, 3);
        $classes = [
            'NativeEffectSemanticIdentity' => ['NativeEffectAtomicAdmissionService.php', 'NativeEffectSemanticIdentity.php'],
            'NativeEffectContinuationCapabilityContract' => ['NativeEffectContinuationCapability.php', 'NativeEffectContinuationCapabilityContract.php'],
            'NativeEffectReceiptInputContract' => ['NativeEffectAtomicAdmissionService.php', 'NativeEffectDoubleExecutionService.php', 'NativeEffectReceiptInputContract.php'],
            'NativeEffectTupleDispositionContract' => ['NativeEffectAtomicAdmissionService.php', 'NativeEffectTupleDispositionContract.php'],
        ];
        foreach ($classes as $class => $allowedFiles) {
            $matches = [];
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/src', \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile() && 'php' === $file->getExtension() && str_contains((string) file_get_contents($file->getPathname()), $class)) {
                    $matches[] = $file->getFilename();
                }
            }
            sort($matches);
            sort($allowedFiles);
            self::assertSame($allowedFiles, $matches, $class.' has an ungoverned call site.');
        }

        $newSource = '';
        foreach (array_keys($classes) as $class) {
            $ownFile = $class.'.php';
            $newSource .= file_get_contents($root.'/src/Imperium/Runtime/ProviderTransition/'.$ownFile);
        }
        foreach (['AtomicTransition', 'ImmutableRecordStore', 'file_put_contents', 'CredentialBroker', 'AgentMail', 'HttpClient', 'curl_'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $newSource, $forbidden);
        }
        $currentRuntime = file_get_contents($root.'/src/Imperium/Runtime/ProviderTransition/NativeEffectAdmissionValidator.php');
        self::assertStringContainsString("'effect_authority_id'", $currentRuntime);
        self::assertStringContainsString("'effect_authority_digest'", $currentRuntime);
    }

    public function testBatchDocumentationPreservesAllLaterStops(): void
    {
        $root = dirname(__DIR__, 3);
        $docs = file_get_contents($root.'/docs/canonical-native-effect-continuation-exclusivity-remediation-batch-1-contracts-identities-v1.md')
            .file_get_contents($root.'/docs/handoffs/canonical-native-effect-continuation-exclusivity-remediation-batch-1-complete.md');
        foreach ([
            'BATCH_1_CORRECTED_CONTRACTS_AND_IDENTITIES_COMPLETE_NO_RUNTIME_WIRING',
            'BATCH_2_NOT_AUTHORIZED', 'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED',
            'No capability was issued or consumed', 'No CI result is claimed',
        ] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $docs, $marker);
        }
    }

    private function authority(): array
    {
        $reference = static fn (string $id, string $digit): array => [
            'id' => $id,
            'schema' => 'imperium.fixture/v1',
            'digest' => str_repeat($digit, 64),
        ];
        $record = [
            'schema' => NativeEffectAuthorityContract::SCHEMA,
            'authority_id' => 'native-effect-authority-first',
            'instance_id' => 'imperium-fixture',
            'native_root' => str_repeat('0', 64),
            'native_transition' => $reference('native-transition', '1'),
            'native_receipt' => $reference('native-receipt', '2'),
            'successor' => $reference('native-successor', '3'),
            'v3_admission' => $reference('native-v3-admission', '4'),
            'executor_principal' => $reference('executor-principal', '5'),
            'execution_boundary' => $reference('execution-boundary', '6'),
            'provider_binding' => $reference('provider-binding', '7'),
            'operation' => 'email.send',
            'destination' => 'https://provider.invalid/messages',
            'payload_digest' => str_repeat('8', 64),
            'request_fingerprint' => str_repeat('9', 64),
            'provider' => [
                'provider_id' => 'provider-fixture',
                'adapter_id' => 'adapter-fixture',
                'adapter_version' => 'v1',
                'assurance_admission' => $reference('assurance-admission', 'a'),
            ],
            'credential_scope' => [
                'credential_family' => 'credential-family-fixture',
                'stationary_same_process' => true,
                'cross_process_transfer_permitted' => false,
                'secret_persistence_permitted' => false,
            ],
            'expected_return_contract' => 'provider.message/v1',
            'provider_idempotency_key_digest' => str_repeat('b', 64),
            'holder' => NativeEffectAuthorityContract::CONSUMER,
            'issuer' => 'imperator.effect-authority-issuer/v1',
            'effective_at' => 100,
            'expires_at' => 500,
            'revocation_reference' => null,
            'cancellation_reference' => null,
            'single_use' => true,
            'consumed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ];

        return NativeState::seal($record);
    }

    private function fails(string $message, callable $action): void
    {
        try {
            $action();
            self::fail('Expected '.$message);
        } catch (\RuntimeException $error) {
            self::assertSame($message, $error->getMessage());
        }
    }
}
