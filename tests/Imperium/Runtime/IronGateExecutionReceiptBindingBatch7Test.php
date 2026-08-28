<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\DeterministicEffectStartJournalContract;
use App\Imperium\Runtime\LaCortine\DeterministicEffectStartJournalService;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimContract;
use App\Imperium\Runtime\LaCortine\DeterministicExecutionClaimService;
use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingBatch7Test extends TestCase
{
    private string $root;
    private string $claimId = 'deterministic-execution-claim-0123456789abcdef0123';

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-iron-gate-batch-7-'.bin2hex(random_bytes(5));
        $this->writeClaim();
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
    }

    public function testEffectStartJournalBindsOneWinnerAndDefaultsToUnknownReplayProhibited(): void
    {
        $service = new DeterministicEffectStartJournalService($this->root);
        $journal = $service->start($this->claimId, $this->time('+1 minute'));

        self::assertSame(DeterministicEffectStartJournalContract::REQUIRED_FIELDS, array_keys($journal));
        self::assertSame($this->claimId, $journal['execution_claim']['id']);
        self::assertSame('PROVIDER_IDEMPOTENCY_KEY', $journal['provider_safety']['strategy']);
        self::assertFalse($journal['provider_safety']['automatic_replay_permitted']);
        self::assertSame('EFFECT_STARTED', $journal['effect']['checkpoint']);
        self::assertTrue($journal['effect']['external_io_may_have_started']);
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $journal['effect']['outcome']);
        self::assertFalse($journal['effect']['provider_invoked_by_transition']);
        self::assertTrue($journal['credential_use']['consumption_required']);
        self::assertFalse($journal['credential_use']['consumed_by_journal']);
        self::assertFalse($journal['credential_use']['credential_resolved']);
        self::assertSame($journal, $service->start($this->claimId, $this->time('+2 minutes')));
        self::assertFalse(is_dir($this->root.'/var/imperium/lazaretto'));
    }

    public function testExpiredClaimCannotOpenEffectStart(): void
    {
        $this->expectExceptionMessage('IGJ502_EXECUTION_CLAIM_NOT_STARTABLE');
        (new DeterministicEffectStartJournalService($this->root))->start($this->claimId, $this->time('+6 minutes'));
    }

    public function testTamperedClaimAndCredentialSecretsFailClosed(): void
    {
        $path = $this->root.'/'.DeterministicExecutionClaimService::CLAIMS.'/'.$this->claimId.'.json';
        $tampered = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $tampered['request']['destination'] = '/attacker';
        file_put_contents($path, json_encode($tampered, JSON_THROW_ON_ERROR));

        self::assertStringNotContainsString('test-secret-material', (string) file_get_contents($path));
        $this->expectExceptionMessage('IGJ502_EXECUTION_CLAIM_NOT_STARTABLE');
        (new DeterministicEffectStartJournalService($this->root))->start($this->claimId, $this->time('+1 minute'));
    }

    private function writeClaim(): void
    {
        $record = [
            'schema' => DeterministicExecutionClaimContract::SCHEMA,
            'claim_id' => $this->claimId,
            'instance_id' => 'imperium-test',
            'source_authorization' => ['id' => 'outbound-email-authorization-0123456789abcdef0123', 'digest' => hash('sha256', 'authorization'), 'schema' => 'imperium.la-cortine.deterministic-outbound-email-authorization/v1', 'issuer' => ['actor_id' => 'imperator', 'office' => 'imperator', 'seat' => 'imperator', 'binding_id' => 'imperator', 'runtime_principal_id' => 'imperator'], 'decision_owner' => ['kind' => 'imperator', 'id' => 'imperator-development-root']],
            'authorization_consumption' => ['authority_id' => 'outbound-email-authorization-0123456789abcdef0123', 'source_digest' => hash('sha256', 'authorization'), 'consumed_at' => $this->time('-1 minute')->format(DATE_ATOM), 'consumed' => true, 'continuing_authority' => false],
            'request' => ['id' => 'outbound-email-request-0123456789abcdef0123', 'commission_id' => 'commission-test', 'authorization_id' => 'outbound-email-authorization-0123456789abcdef0123', 'authorization_digest' => hash('sha256', 'authorization'), 'mode' => 'DETERMINISTIC', 'operation' => 'email.send', 'destination' => '/v0/inboxes/test/messages', 'payload_digest' => hash('sha256', 'payload'), 'expected_return_contract' => 'agentmail.message/v1'],
            'holder' => ['actor_id' => 'agentmail-transport', 'office' => 'la-cortine', 'seat' => 'la-cortine.deterministic-boundary-executor', 'runtime_principal_id' => 'agentmail-email-send-command', 'competent_service' => 'la-cortine.deterministic-boundary-executor'],
            'replay_fingerprint' => hash('sha256', 'replay'),
            'execution_identity' => ['execution_id' => 'deterministic-execution-0123456789abcdef0123', 'single_use' => true, 'winner_scope' => 'authorization:outbound-email-authorization-0123456789abcdef0123', 'lock_order' => ['authorization', 'execution-claim']],
            'credential_capability' => ['capability_id' => 'credential-capability.test', 'credential_reference_digest' => hash('sha256', 'credential-reference-only'), 'commission_id' => 'commission-test', 'operation' => 'email.send', 'expires_at' => $this->time('+5 minutes')->format(DATE_ATOM), 'max_uses' => 1],
            'provider_safety' => ['strategy' => 'PROVIDER_IDEMPOTENCY_KEY', 'provider_idempotency_key' => 'iron-gate-test-key', 'provider_contract_reference' => 'docs/runtime/agentmail-email-transport.md', 'automatic_replay_permitted' => false, 'unknown_outcome_status' => 'NOT_STARTED'],
            'effect' => ['checkpoint' => 'CLAIMED_PRE_IO', 'external_io_started' => false, 'outcome' => 'NOT_ATTEMPTED', 'effect_started_at' => null, 'resolved_at' => null],
            'claimed_at' => $this->time('-1 minute')->format(DATE_ATOM),
            'expires_at' => $this->time('+5 minutes')->format(DATE_ATOM),
            'sealed' => true,
        ];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $directory = $this->root.'/'.DeterministicExecutionClaimService::CLAIMS;
        mkdir($directory, 0770, true);
        file_put_contents($directory.'/'.$this->claimId.'.json', json_encode($record, JSON_THROW_ON_ERROR));
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
