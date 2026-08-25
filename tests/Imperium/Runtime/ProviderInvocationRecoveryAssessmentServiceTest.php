<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\ProviderInvocationJournalService;
use App\Imperium\Runtime\Clavium\ProviderInvocationRecoveryAssessmentService;
use App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService;
use PHPUnit\Framework\TestCase;

final class ProviderInvocationRecoveryAssessmentServiceTest extends TestCase
{
    private string $root;
    private array $claim;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-provider-recovery-'.bin2hex(random_bytes(5));
        $this->claim = $this->seedClaim();
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testCrashAfterClaimRequiresGovernedResolutionWithoutReplay(): void
    {
        $result = (new ProviderInvocationRecoveryAssessmentService($this->root))->assess($this->claim['claim_id']);
        self::assertSame('CLAIMED_WITHOUT_JOURNAL_GOVERNED_RESOLUTION_REQUIRED', $result['status']);
        self::assertFalse($result['automatic_replay_permitted']);
    }

    public function testCrashBeforeClaimLeavesNoRecoverableInvocation(): void
    {
        $this->expectExceptionMessage('CLV420_PROVIDER_RECOVERY_CLAIM_INVALID');
        (new ProviderInvocationRecoveryAssessmentService($this->root))->assess('provider-invocation-'.str_repeat('f', 20));
    }

    public function testCrashDuringProviderIoClassifiesOutcomeAsUnknown(): void
    {
        (new ProviderInvocationJournalService($this->root))->start($this->claim, new \DateTimeImmutable());
        $result = (new ProviderInvocationRecoveryAssessmentService($this->root))->assess($this->claim['claim_id']);
        self::assertSame('PROVIDER_OUTCOME_UNKNOWN_GOVERNED_RESOLUTION_REQUIRED', $result['status']);
        self::assertTrue($result['provider_outcome_may_be_unknown']);
        self::assertFalse($result['automatic_replay_permitted']);
    }

    public function testCrashAfterResponseReceiptRequiresTurnRecoveryWithoutReplay(): void
    {
        $journal = new ProviderInvocationJournalService($this->root);
        $journal->start($this->claim, new \DateTimeImmutable());
        (new ProviderResponseEnvelopeService($this->root))->seal($this->claim, 'response', new \DateTimeImmutable());
        $journal->sealResponse($this->claim, 'response', new \DateTimeImmutable());
        $result = (new ProviderInvocationRecoveryAssessmentService($this->root))->assess($this->claim['claim_id']);
        self::assertSame('RESPONSE_ENVELOPE_AVAILABLE_FOR_TURN_PERSISTENCE_RECOVERY', $result['status']);
        self::assertFalse($result['automatic_replay_permitted']);
    }

    public function testCrashAfterEnvelopeBeforeJournalSealPreservesRecoverableResponse(): void
    {
        (new ProviderInvocationJournalService($this->root))->start($this->claim, new \DateTimeImmutable());
        (new ProviderResponseEnvelopeService($this->root))->seal($this->claim, 'response', new \DateTimeImmutable());

        $result = (new ProviderInvocationRecoveryAssessmentService($this->root))->assess($this->claim['claim_id']);
        self::assertSame('RESPONSE_ENVELOPE_SEALED_PENDING_JOURNAL_AND_TURN_RECOVERY', $result['status']);
        self::assertFalse($result['provider_outcome_may_be_unknown']);
        self::assertFalse($result['automatic_replay_permitted']);
    }

    public function testPersistedTurnClosesRecoveryNeed(): void
    {
        $this->write($this->root.'/var/imperium/operational/delegate-mission-bounded-cognition-turns/turn-1.json', [
            'turn_id' => 'turn-1',
            'source_invocation_claim' => ['id' => $this->claim['claim_id'], 'digest' => $this->claim['record_digest']],
            'status' => 'DELEGATE_MISSION_BOUNDED_COGNITION_TURN_COMPLETE_PENDING_CURIA_DISPOSITION',
        ]);
        $result = (new ProviderInvocationRecoveryAssessmentService($this->root))->assess($this->claim['claim_id']);
        self::assertSame('TURN_PERSISTED_NO_RECOVERY_REQUIRED', $result['status']);
        self::assertFalse($result['governed_resolution_required']);
    }

    private function seedClaim(): array
    {
        $id = 'provider-invocation-'.str_repeat('a', 20);
        $claim = ['schema' => 'imperium.clavium-provider-invocation-claim/v1', 'claim_id' => $id, 'lease_consumption' => ['consumed' => true], 'turn_authority_consumption' => ['consumed' => true], 'provider_request' => ['idempotency_key' => 'imperium-'.$id, 'external_io_started' => false], 'recovery' => ['automatic_replay_permitted' => false], 'status' => 'INVOCATION_CLAIMED_PENDING_EXTERNAL_IO'];
        $this->write($this->root.'/var/imperium/runtime/provider-invocations/'.$id.'.json', $claim);

        return json_decode((string) file_get_contents($this->root.'/var/imperium/runtime/provider-invocations/'.$id.'.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    private function write(string $path, array $record): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
