<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Armory\CanonicalEmailSendToolDefinitionService;
use App\Imperium\Runtime\Clavium\AgentMailCredentialFamilyPolicy;
use App\Imperium\Runtime\Clavium\ProviderBoundCredentialEligibilityService;
use App\Imperium\Runtime\LaCortine\AgentMailProviderEvidenceDecoder;
use App\Imperium\Runtime\LaCortine\AgentMailProviderProfile;
use App\Imperium\Runtime\LaCortine\CredentialCapability;
use App\Imperium\Runtime\LaCortine\GovernedToolResultReconstructionService;
use App\Imperium\Runtime\LaCortine\NormalizedToolResultAdmissionService;
use App\Imperium\Runtime\LaCortine\ProviderBoundEvidenceNormalizationService;
use App\Imperium\Runtime\LaCortine\ProviderImplementationBindingService;
use App\Imperium\Runtime\LaCortine\ProviderNeutralRawEvidenceService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class GovernedToolProviderSeparationBatch7Test extends TestCase
{
    private string $root;

    protected function setUp(): void { $this->root = sys_get_temp_dir().'/imperium-tool-reconstruction-'.bin2hex(random_bytes(5)); mkdir($this->root, 0770, true); }
    protected function tearDown(): void { $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST); foreach ($it as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname()); rmdir($this->root); }

    public function testReconstructsCompleteSeparatedChainReadOnly(): void
    {
        [$admission, $eligibility] = $this->chain();
        $before = $this->files();
        $proof = (new GovernedToolResultReconstructionService($this->root))->reconstruct($admission['admission_id'], $eligibility['eligibility_id']);

        foreach (['tool_definition', 'source_authorization', 'execution_claim', 'provider_binding', 'credential_eligibility', 'raw_provider_evidence', 'decoder', 'normalized_result', 'lazaretto_admission'] as $field) self::assertArrayHasKey($field, $proof);
        self::assertNull($proof['credential_consumption_attempt']);
        self::assertTrue($proof['read_only']);
        self::assertFalse($proof['provider_reinvoked']);
        self::assertFalse($proof['credential_resolved']);
        self::assertFalse($proof['external_io_performed']);
        self::assertFalse($proof['continuing_authority']);
        self::assertSame($before, $this->files());
    }

    public function testDifferentCredentialEligibilityCannotBeAttachedToResult(): void
    {
        [$admission] = $this->chain();
        $binding = $this->binding('provider-implementation-binding-bbbbbbbbbbbbbbbbbbbb', 'authorization-2', '9');
        $this->store()->put(ProviderImplementationBindingService::BINDINGS, $binding['binding_id'], $binding);
        $other = (new ProviderBoundCredentialEligibilityService($this->root, new AgentMailCredentialFamilyPolicy()))->assess($binding, $this->capability('authorization-2'), $this->time());

        $this->expectExceptionMessage('GTP705_CREDENTIAL_ELIGIBILITY_INVALID');
        (new GovernedToolResultReconstructionService($this->root))->reconstruct($admission['admission_id'], $other['eligibility_id']);
    }

    public function testTamperedAdmissionFailsWithoutProviderOrCredentialAction(): void
    {
        [$admission, $eligibility] = $this->chain();
        $path = $this->root.'/'.NormalizedToolResultAdmissionService::ADMISSIONS.'/'.$admission['admission_id'].'.json';
        $admission['provider_reinvoked'] = true;
        file_put_contents($path, json_encode($admission, JSON_THROW_ON_ERROR));

        $this->expectExceptionMessage('GTP701_NORMALIZED_ADMISSION_INVALID');
        (new GovernedToolResultReconstructionService($this->root))->reconstruct($admission['admission_id'], $eligibility['eligibility_id']);
    }

    public function testBatchSevenDocumentsBatchEightOnly(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/governed-tool-provider-separation-batch-7-complete.md');
        foreach (['Only Batch 8 may next be considered', 'Runtime behavior is unchanged', 'No provider was invoked', 'Batch 8 is not authorized'] as $proof) self::assertStringContainsString($proof, $handoff);
    }

    private function chain(): array
    {
        $tool = (new CanonicalEmailSendToolDefinitionService($this->root))->define($this->time('-10 minutes'));
        $binding = $this->binding('provider-implementation-binding-aaaaaaaaaaaaaaaaaaaa', 'authorization-1', '2', $tool);
        $binding = $this->store()->put(ProviderImplementationBindingService::BINDINGS, $binding['binding_id'], $binding);
        $eligibility = (new ProviderBoundCredentialEligibilityService($this->root, new AgentMailCredentialFamilyPolicy()))->assess($binding, $this->capability('authorization-1'), $this->time());
        $toolRef = ['id' => 'email.send.v1', 'digest' => $tool['record_digest'], 'schema' => $tool['schema']];
        $authorization = $this->ref('authorization-1', '2', 'authorization/v1');
        $claim = $this->ref('execution-claim-1', '3', 'claim/v1');
        $raw = (new ProviderNeutralRawEvidenceService($this->root))->preserve($binding, $toolRef, $authorization, $claim, 202, 'application/json', '{"message_id":"msg-1","thread_id":"thread-1"}', $this->time('+1 minute'));
        $result = (new ProviderBoundEvidenceNormalizationService($this->root, new AgentMailProviderEvidenceDecoder()))->normalize($binding, $raw, $this->time('+2 minutes'));
        $admission = (new NormalizedToolResultAdmissionService($this->root))->admit($result, $this->time('+3 minutes'));
        return [$admission, $eligibility];
    }

    private function binding(string $id, string $target, string $digit, ?array $tool = null): array
    {
        $tool ??= (new CanonicalEmailSendToolDefinitionService($this->root))->read();
        return self::seal(['schema' => 'imperium.la-cortine.provider-implementation-binding/v1', 'binding_id' => $id, 'instance_id' => 'imperium-test', 'source_authority' => $this->ref('authority-1', '4', 'authority/v1'), 'tool_operation' => ['id' => 'email.send.v1', 'digest' => $tool['record_digest'], 'schema' => $tool['schema']], 'provider_implementation' => ['provider_id' => 'agentmail', 'adapter_id' => 'agentmail.email-send', 'adapter_version' => '1'], 'assurance_profile' => $this->ref('assurance-1', '5', 'assurance/v1'), 'credential_family' => ['family_id' => AgentMailProviderProfile::CREDENTIAL_FAMILY_ID, 'provider_id' => 'agentmail', 'secret_persistence_permitted' => false], 'request_encoder' => $this->ref(AgentMailProviderProfile::REQUEST_ENCODER_ID, '6', 'encoder/v1'), 'evidence_decoder' => $this->ref(AgentMailProviderProfile::EVIDENCE_DECODER_ID, '7', 'decoder/v1'), 'destination_policy' => ['policy_id' => 'destination-1', 'policy_digest' => str_repeat('8', 64), 'exact_destination_required' => true], 'scope' => ['operation' => 'email.send', 'authorization_target_id' => $target, 'authorization_target_digest' => str_repeat($digit, 64), 'provider_substitution_permitted' => false], 'validity' => ['effective_at' => $this->time('-5 minutes')->format(DATE_ATOM), 'expires_at' => $this->time('+10 minutes')->format(DATE_ATOM)], 'status' => 'BOUND_INACTIVE', 'bound_at' => $this->time('-4 minutes')->format(DATE_ATOM), 'sealed' => true]);
    }
    private function capability(string $target): CredentialCapability { return new CredentialCapability('credential-capability.'.$target, AgentMailProviderProfile::CREDENTIAL_REFERENCE_SYNTAX, $target, 'email.send', $this->time('+5 minutes')); }
    private function ref(string $id, string $digit, string $schema): array { return ['id' => $id, 'digest' => str_repeat($digit, 64), 'schema' => $schema]; }
    private function time(string $modify = ''): \DateTimeImmutable { $at = new \DateTimeImmutable('2026-08-29T03:00:00+00:00'); return '' === $modify ? $at : $at->modify($modify); }
    private function store(): ImmutableRecordStore { return new ImmutableRecordStore($this->root, new AtomicTransition($this->root)); }
    private function files(): array { $files = []; $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS)); foreach ($it as $item) if ($item->isFile()) $files[] = str_replace($this->root, '', $item->getPathname()).':'.hash_file('sha256', $item->getPathname()); sort($files); return $files; }
    private static function seal(array $record): array { unset($record['record_digest']); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); return $record; }
}
