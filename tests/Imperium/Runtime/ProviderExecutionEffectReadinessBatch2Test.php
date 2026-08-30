<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\AgentMailDirectSendAssuranceProfileContract;
use App\Imperium\Runtime\LaCortine\ProviderAssuranceEvidenceAdmissionContract;
use App\Imperium\Runtime\LaCortine\ProviderAssuranceEvidenceContractValidator;
use App\Imperium\Runtime\LaCortine\ProviderAssuranceEvidenceFixtureStore;
use App\Imperium\Runtime\LaCortine\ProviderAssuranceEvidenceSourceContract;
use PHPUnit\Framework\TestCase;

final class ProviderExecutionEffectReadinessBatch2Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-provider-assurance-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0777, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->root)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->root);
    }

    public function testExactOfflineFixturesValidateStoreAndReplay(): void
    {
        $source = $this->source();
        $profile = $this->profile([$source]);
        $admission = $this->admission($profile, [$source]);
        $store = new ProviderAssuranceEvidenceFixtureStore($this->root);

        self::assertSame($source, $store->putSource($source));
        self::assertSame($profile, $store->putProfile($profile, [$source]));
        self::assertSame($admission, $store->putAdmission($admission, $profile, [$source]));
        self::assertSame($admission, $store->putAdmission($admission, $profile, [$source]));

        foreach (glob($this->root.'/var/imperium/evidence/provider-execution-effect-readiness/*/*.json') ?: [] as $path) {
            $contents = (string) file_get_contents($path);
            self::assertStringNotContainsString('AGENTMAIL_API_KEY', $contents);
            self::assertStringNotContainsString('credential_secret', $contents);
            self::assertStringNotContainsString('CredentialCapability', $contents);
        }
    }

    public function testChangedEvidenceUnknownOrRetentionFailsClosed(): void
    {
        $source = $this->source();
        $profile = $this->profile([$source]);
        $admission = $this->admission($profile, [$source]);
        $validator = new ProviderAssuranceEvidenceContractValidator();

        $badSource = $source;
        $badSource['provider_id'] = 'substitute';
        $badSource = self::seal($badSource);

        $badProfile = $profile;
        $badProfile['retention']['anchor'] = 'LOCAL_EFFECT_START';
        $badProfile = self::seal($badProfile);

        $badAdmission = $admission;
        $badAdmission['explicit_unknowns']['query_before_retry'] = 'KNOWN';
        $badAdmission = self::seal($badAdmission);

        foreach ([
            static fn () => $validator->assertSource($badSource),
            static fn () => $validator->assertProfile($badProfile, [$source]),
            static fn () => $validator->assertAdmission($badAdmission, $profile, [$source]),
        ] as $index => $attempt) {
            try {
                $attempt();
                self::fail('Invalid assurance fixture accepted at '.$index);
            } catch (\RuntimeException $exception) {
                self::assertStringStartsWith('PER2', $exception->getMessage());
            }
        }
    }

    public function testFixtureStoreContainsNoProviderOrActivationPath(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/ProviderAssuranceEvidenceFixtureStore.php',
        );

        foreach ([
            'CredentialCapability',
            'EnvironmentCredentialBroker',
            'AgentMailEmailTransport',
            'DeterministicTransport',
            'GovernedProviderExecutionCombinedAdmissionService',
            'IronGate',
            'Lazaretto',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    private function source(): array
    {
        return self::seal([
            'schema' => ProviderAssuranceEvidenceSourceContract::SCHEMA,
            'source_id' => 'agentmail-idempotency-docs-20260830',
            'provider_id' => 'agentmail',
            'source_kind' => 'OFFICIAL_PROVIDER_DOCUMENTATION',
            'canonical_uri' => 'https://docs.agentmail.to/idempotency',
            'observed_at' => '2026-08-30T12:00:00+00:00',
            'content_digest' => str_repeat('a', 64),
            'version_identity' => 'observed-2026-08-30',
            'immutability_posture' => 'MUTABLE_REMOTE_PAGE',
            'status' => 'DEFINED_EVIDENCE_ONLY',
            'sealed' => true,
        ]);
    }

    private function profile(array $sources): array
    {
        return self::seal([
            'schema' => AgentMailDirectSendAssuranceProfileContract::SCHEMA,
            'profile_id' => 'agentmail-direct-send-assurance-v1',
            'provider_id' => 'agentmail',
            'operation' => 'email.send',
            'endpoint' => 'POST /v0/inboxes/{inbox_id}/messages/send',
            'evidence_sources' => array_map(
                static fn (array $source): array => self::reference($source, 'source_id'),
                $sources,
            ),
            'collision_scope' => [
                'organization_scoped' => true,
                'endpoint_bound' => true,
                'inbox_bound' => true,
                'content_bound' => true,
            ],
            'idempotency_key' => [
                'header_name' => 'Idempotency-Key',
                'minimum_length' => 1,
                'maximum_length' => 256,
                'allowed_character_class' => 'A-Za-z0-9-._~',
                'empty_permitted' => false,
            ],
            'request_equivalence' => [
                'organization' => true,
                'endpoint' => true,
                'inbox_id' => true,
                'message_content' => true,
            ],
            'completed_duplicate' => [
                'second_send_expected' => false,
                'original_message_id_expected' => true,
                'original_thread_id_expected' => true,
            ],
            'changed_request' => [
                'same_key_changed_request_expected_status' => 409,
                'local_collision_refusal_required' => true,
            ],
            'retention' => [
                'declared_duration_hours' => 24,
                'anchor' => 'PROVIDER_COMPLETION',
                'local_effect_start_may_establish_anchor' => false,
            ],
            'explicit_unknowns' => array_fill_keys(
                AgentMailDirectSendAssuranceProfileContract::REQUIRED_UNKNOWN_FIELDS,
                'UNKNOWN',
            ),
            'replay_posture' => 'UNKNOWN_REPLAY_PROHIBITED',
            'status' => 'DEFINED_EVIDENCE_ONLY',
            'sealed' => true,
        ]);
    }

    private function admission(array $profile, array $sources): array
    {
        return self::seal([
            'schema' => ProviderAssuranceEvidenceAdmissionContract::SCHEMA,
            'admission_id' => 'provider-assurance-admission-agentmail-direct-send-v1',
            'instance_id' => 'imperium-test',
            'provider_id' => 'agentmail',
            'operation' => 'email.send',
            'assurance_profile' => self::reference($profile, 'profile_id'),
            'evidence_sources' => array_map(
                static fn (array $source): array => self::reference($source, 'source_id'),
                $sources,
            ),
            'admitted_claims' => array_fill_keys(
                ProviderAssuranceEvidenceAdmissionContract::REQUIRED_ADMITTED_CLAIM_FIELDS,
                true,
            ),
            'explicit_unknowns' => array_fill_keys(
                ProviderAssuranceEvidenceAdmissionContract::REQUIRED_UNKNOWN_FIELDS,
                'UNKNOWN',
            ),
            'threat_model' => [
                'integrity_posture' => 'TRUSTED_WRITER_CANONICAL_INTEGRITY',
                'deployment_posture' => 'SINGLE_AUTHORITATIVE_ROOT_ONLY',
                'authenticated_channel_trust_only' => true,
                'hostile_writer_non_forgeability_claimed' => false,
                'distributed_execution_claimed' => false,
            ],
            'validity' => [
                'effective_at' => '2026-08-30T12:05:00+00:00',
                'review_due_at' => '2026-09-30T12:05:00+00:00',
                'supersession_reference' => null,
                'revocation_reference' => null,
            ],
            'status' => 'EVIDENCE_ADMITTED_NO_EXECUTION_AUTHORITY',
            'admitted_at' => '2026-08-30T12:05:00+00:00',
            'sealed' => true,
        ]);
    }

    private static function reference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private static function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }
}
