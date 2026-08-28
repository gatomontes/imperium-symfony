<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\OutboundEmailAuthorizationRequestService;
use App\Imperium\Runtime\Imperator\OutboundEmailAuthorizationIssuanceService;
use App\Imperium\Runtime\Imperator\OutboundEmailDecisionService;
use App\Imperium\Runtime\LaCortine\CredentialCapability;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimContract;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimService;
use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingBatch6Test extends TestCase
{
    use IronGateCallerAuthorityTestTrait;

    private string $root;
    private string $bindingId = 'curia-seneschal-binding-abcdef0123456789abcd';

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-iron-gate-batch-6-'.bin2hex(random_bytes(5));
        $record = ['schema' => 'imperium.curia-seneschal-occupancy/v1', 'binding_id' => $this->bindingId, 'instance_id' => 'imperium-test', 'office' => 'curia', 'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-seneschal-test', 'occupancy_generation' => 1, 'status' => 'ACTIVE', 'outbound_email_request_authority' => true, 'execution_authority' => false, 'sealed' => true];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $directory = $this->root.'/var/imperium/offices/curia/occupancy';
        mkdir($directory, 0770, true);
        file_put_contents($directory.'/'.$this->bindingId.'.json', json_encode($record, JSON_THROW_ON_ERROR));
        $this->writeImperatorPrincipal();
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
    }

    public function testClaimConsumesAuthorizationAndStopsAtDurablePreIoCheckpoint(): void
    {
        $issuance = $this->issuance();
        $credential = $this->credential('credential-capability.test');
        $service = new DeterministicExecutionClaimService($this->root);
        $claim = $service->claim($issuance['issuance_id'], $credential, $this->time('+3 minutes'));

        self::assertSame(DeterministicExecutionClaimContract::REQUIRED_FIELDS, array_keys($claim));
        self::assertSame($issuance['issued_authorization']['authorization_id'], $claim['source_authorization']['id']);
        self::assertSame($issuance['issued_authorization']['record_digest'], $claim['authorization_consumption']['source_digest']);
        self::assertTrue($claim['authorization_consumption']['consumed']);
        self::assertFalse($claim['authorization_consumption']['continuing_authority']);
        self::assertSame('commission-test', $claim['request']['commission_id']);
        self::assertSame('CLAIMED_PRE_IO', $claim['effect']['checkpoint']);
        self::assertSame('NOT_ATTEMPTED', $claim['effect']['outcome']);
        self::assertFalse($claim['effect']['external_io_started']);
        self::assertFalse($claim['provider_safety']['automatic_replay_permitted']);
        self::assertSame($claim, $service->claim($issuance['issuance_id'], $credential, $this->time('+4 minutes')));
        self::assertFalse(is_dir($this->root.'/var/imperium/lazaretto'));
    }

    public function testCompetingCapabilityCannotWinTheConsumedAuthorization(): void
    {
        $issuance = $this->issuance();
        $service = new DeterministicExecutionClaimService($this->root);
        $service->claim($issuance['issuance_id'], $this->credential('credential-capability.first'), $this->time('+3 minutes'));

        $this->expectExceptionMessage('IGC404_EXECUTION_CLAIM_CONFLICT');
        $service->claim($issuance['issuance_id'], $this->credential('credential-capability.second'), $this->time('+3 minutes'));
    }

    public function testCredentialScopeMismatchFailsBeforeClaimPersistence(): void
    {
        $issuance = $this->issuance();
        $wrong = new CredentialCapability('credential-capability.wrong', 'credential-reference-only', 'other-commission', 'email.send', $this->time('+7 minutes'));

        try {
            (new DeterministicExecutionClaimService($this->root))->claim($issuance['issuance_id'], $wrong, $this->time('+3 minutes'));
            self::fail('A mismatched credential capability must not create a claim.');
        } catch (\RuntimeException $exception) {
            self::assertSame('IGC403_CREDENTIAL_CAPABILITY_SCOPE_INVALID', $exception->getMessage());
        }
        self::assertSame([], glob($this->root.'/'.DeterministicExecutionClaimService::CLAIMS.'/*.json') ?: []);
    }

    public function testTamperAndSecretExclusionFailClosed(): void
    {
        $issuance = $this->issuance();
        $path = $this->root.'/'.OutboundEmailAuthorizationIssuanceService::ISSUANCES.'/'.$issuance['issuance_id'].'.json';
        $tampered = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $tampered['issued_authorization']['scope']['payload_digest'] = hash('sha256', 'attacker');
        file_put_contents($path, json_encode($tampered, JSON_THROW_ON_ERROR));

        self::assertStringNotContainsString('test-secret-material', (string) file_get_contents($path));
        $this->expectExceptionMessage('IGC402_OUTBOUND_EMAIL_AUTHORIZATION_INVALID');
        (new DeterministicExecutionClaimService($this->root))->claim($issuance['issuance_id'], $this->credential('credential-capability.test'), $this->time('+3 minutes'));
    }

    private function issuance(): array
    {
        $request = $this->authorizedRequest($this->bindingId, $this->holder(), 'Send the sealed operational notice', $this->scope(), $this->providerSafety(), $this->time('+10 minutes'), $this->time());
        $decision = $this->authorizedDecision($request['request_id'], 'AUTHORIZED', 'Exact act approved.', 'No scope widening.', $this->time('+8 minutes'), $this->time('+1 minute'));
        return $this->authorizedIssuance($decision['decision_id'], $this->time('+2 minutes'));
    }

    private function credential(string $id): CredentialCapability
    {
        return new CredentialCapability($id, 'credential-reference-only', 'commission-test', 'email.send', $this->time('+7 minutes'));
    }

    private function holder(): array
    {
        return ['actor_id' => 'agentmail-transport', 'office' => 'la-cortine', 'seat' => 'la-cortine.deterministic-boundary-executor', 'binding_id' => 'deterministic-email-lane', 'runtime_principal_id' => 'agentmail-email-send-command'];
    }

    private function scope(): array
    {
        return ['operation' => 'email.send', 'commission_id' => 'commission-test', 'inbox_id' => 'inbox-test', 'destination' => 'https://api.agentmail.to/v0/inboxes/inbox-test/messages/send', 'recipient_set_digest' => hash('sha256', 'recipient'), 'subject_digest' => hash('sha256', 'subject'), 'body_digest' => hash('sha256', 'body'), 'attachment_manifest_digest' => hash('sha256', 'attachments'), 'payload_digest' => hash('sha256', 'payload'), 'credential_reference_digest' => hash('sha256', 'credential-reference-only'), 'expected_return_contract' => 'agentmail.message/v1'];
    }

    private function providerSafety(): array
    {
        return ['strategy' => 'PROVIDER_IDEMPOTENCY_KEY', 'provider' => 'agentmail', 'endpoint' => 'https://api.agentmail.to/v0/inboxes/inbox-test/messages/send', 'idempotency_key' => 'iron-gate-test-key', 'idempotency_key_digest' => hash('sha256', 'iron-gate-test-key'), 'request_fingerprint' => hash('sha256', 'request'), 'provider_contract_reference' => 'docs/runtime/agentmail-email-transport.md', 'provider_key_expires_at' => $this->time('+15 minutes')->format(DATE_ATOM)];
    }

    private function time(string $modifier = ''): \DateTimeImmutable
    {
        $time = new \DateTimeImmutable('2035-01-01T00:00:00+00:00');
        return '' === $modifier ? $time : $time->modify($modifier);
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
