<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\OutboundEmailAuthorizationRequestService;
use App\Imperium\Runtime\Imperator\OutboundEmailAuthorizationIssuanceContract;
use App\Imperium\Runtime\Imperator\OutboundEmailAuthorizationIssuanceService;
use App\Imperium\Runtime\Imperator\OutboundEmailDecisionService;
use App\Imperium\Runtime\LaCortine\DeterministicOutboundEmailAuthorizationContract;
use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingBatch5Test extends TestCase
{
    use IronGateCallerAuthorityTestTrait;

    private string $root;
    private string $bindingId = 'curia-seneschal-binding-0123456789abcdef0123';

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-iron-gate-batch-5-'.bin2hex(random_bytes(5));
        $this->writeOccupancy();
        $this->writeImperatorPrincipal();
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
    }

    public function testAuthorizedRouteSealsExactLineageWithoutPerformingAnExternalAct(): void
    {
        [$request, $decision, $issuance] = $this->authorizedRoute();
        $authorization = $issuance['issued_authorization'];

        self::assertSame(OutboundEmailAuthorizationIssuanceContract::REQUIRED_REQUEST_FIELDS, array_keys($request));
        self::assertSame(OutboundEmailAuthorizationIssuanceContract::REQUIRED_DECISION_FIELDS, array_keys($decision));
        self::assertSame(OutboundEmailAuthorizationIssuanceContract::REQUIRED_ISSUANCE_FIELDS, array_keys($issuance));
        self::assertSame(DeterministicOutboundEmailAuthorizationContract::SCHEMA, $authorization['schema']);
        self::assertSame($request['record_digest'], $decision['source_request']['digest']);
        self::assertSame($decision['record_digest'], $authorization['source_decision']['digest']);
        self::assertSame($request['scope'], $authorization['scope']);
        self::assertSame($request['provider_safety'], $authorization['provider_safety']);
        self::assertTrue($issuance['consumed_issuance_authority']['consumed']);
        self::assertFalse($issuance['external_action_performed']);
        self::assertFalse($authorization['consumed']);
        self::assertFalse(is_dir($this->root.'/var/imperium/la-cortine/execution-claims'));
        self::assertFalse(is_dir($this->root.'/var/imperium/lazaretto'));
    }

    public function testExactReplaysAreStableAndConflictingDecisionIsRejected(): void
    {
        $request = $this->authorizedRequest($this->bindingId, $this->holder(), 'Send the sealed operational notice', $this->scope(), $this->providerSafety(), $this->time('+10 minutes'), $this->time());
        self::assertSame($request, $this->authorizedRequest($this->bindingId, $this->holder(), 'Send the sealed operational notice', $this->scope(), $this->providerSafety(), $this->time('+10 minutes'), $this->time()));

        $decision = $this->authorizedDecision($request['request_id'], 'AUTHORIZED', 'Exact act approved.', 'No scope widening.', $this->time('+8 minutes'), $this->time('+1 minute'));
        self::assertSame($decision, $this->authorizedDecision($request['request_id'], 'AUTHORIZED', 'Exact act approved.', 'No scope widening.', $this->time('+8 minutes'), $this->time('+1 minute')));
        $this->expectExceptionMessage('IGD203_OUTBOUND_EMAIL_DECISION_CONFLICT');
        $this->authorizedDecision($request['request_id'], 'REFUSED', 'Act refused.', 'No issuance.', $this->time('+8 minutes'), $this->time('+1 minute'));
    }

    public function testRefusalCannotBeConvertedIntoAuthorizationIssuance(): void
    {
        $request = $this->authorizedRequest($this->bindingId, $this->holder(), 'Send the sealed operational notice', $this->scope(), $this->providerSafety(), $this->time('+10 minutes'), $this->time());
        $decision = $this->authorizedDecision($request['request_id'], 'REFUSED', 'External act refused.', 'No issuance authority.', $this->time('+8 minutes'), $this->time('+1 minute'));
        self::assertNull($decision['issuance_authority']);

        $this->expectExceptionMessage('IGI302_OUTBOUND_EMAIL_DECISION_NOT_ISSUABLE');
        $this->authorizedIssuance($decision['decision_id'], $this->time('+2 minutes'));
    }

    public function testTamperAndSecretExclusionProofsFailClosed(): void
    {
        $request = $this->authorizedRequest($this->bindingId, $this->holder(), 'Send the sealed operational notice', $this->scope(), $this->providerSafety(), $this->time('+10 minutes'), $this->time());
        $path = $this->root.'/'.OutboundEmailAuthorizationRequestService::REQUESTS.'/'.$request['request_id'].'.json';
        $tampered = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $tampered['scope']['destination'] = 'attacker@example.test';
        file_put_contents($path, json_encode($tampered, JSON_THROW_ON_ERROR));

        self::assertStringNotContainsString('test-secret-api-key', (string) file_get_contents($path));
        $this->expectExceptionMessage('IGD202_OUTBOUND_EMAIL_REQUEST_INVALID');
        $this->authorizedDecision($request['request_id'], 'AUTHORIZED', 'Exact act approved.', 'No scope widening.', $this->time('+8 minutes'), $this->time('+1 minute'));
    }

    private function authorizedRoute(): array
    {
        $request = $this->authorizedRequest($this->bindingId, $this->holder(), 'Send the sealed operational notice', $this->scope(), $this->providerSafety(), $this->time('+10 minutes'), $this->time());
        $decision = $this->authorizedDecision($request['request_id'], 'AUTHORIZED', 'Exact act approved.', 'No scope widening.', $this->time('+8 minutes'), $this->time('+1 minute'));
        $issuance = $this->authorizedIssuance($decision['decision_id'], $this->time('+2 minutes'));

        return [$request, $decision, $issuance];
    }

    private function writeOccupancy(): void
    {
        $record = ['schema' => 'imperium.curia-seneschal-occupancy/v1', 'binding_id' => $this->bindingId, 'instance_id' => 'imperium-test', 'office' => 'curia', 'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-seneschal-test', 'occupancy_generation' => 1, 'status' => 'ACTIVE', 'outbound_email_request_authority' => true, 'execution_authority' => false, 'sealed' => true];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $directory = $this->root.'/var/imperium/offices/curia/occupancy';
        mkdir($directory, 0770, true);
        file_put_contents($directory.'/'.$this->bindingId.'.json', json_encode($record, JSON_THROW_ON_ERROR));
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
