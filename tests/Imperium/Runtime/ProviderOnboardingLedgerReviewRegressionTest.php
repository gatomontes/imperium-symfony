<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Tests\Imperium\Runtime\Support\OnboardingLedgerFixture as F;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use PHPUnit\Framework\TestCase;

/** Attributed reviewer cases: original signed fixtures, no new authority producer. */
final class ProviderOnboardingLedgerReviewRegressionTest extends TestCase
{
    private F $f;
    protected function setUp(): void { $this->f = new F(); $this->f->ready(); }
    protected function tearDown(): void { $this->f->close(); }

    public function testRecoveryRejectsRetainedEnvelopeForAnotherOperation(): void
    {
        $f = $this->f;
        $f->ports->fault = 'after-envelope';
        try { $f->advance('access', true); } catch (\RuntimeException) {}
        $state = $f->f->store->journal->read()['state'];
        $id = array_key_first($state['onboarding']['claims']);
        $claim = $state['onboarding']['claims'][$id];
        self::assertCount(4, $claim['custody']);
        $path = $f->f->root.'/b1-envelope-'.substr($claim['record']['record_digest'], 7).'.json';
        $envelope = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $envelope['operation_digest'] = 'sha256:'.str_repeat('0', 64);
        file_put_contents($path, json_encode($envelope, JSON_THROW_ON_ERROR));
        $f->ports->fault = '';
        $head = $f->f->head();
        $refused = false;
        try { $f->custody->reconcile($id); } catch (\RuntimeException|\InvalidArgumentException) { $refused = true; }
        self::assertTrue($refused, 'Recovery accepted an envelope whose operation differs from the reserved operation');
        self::assertSame($head, $f->f->head());
        self::assertNull($f->f->store->journal->read()['state']['onboarding']['claims'][$id]['settled']);
        self::assertSame(1, $f->ports->counts['dispatch']);
    }

    public function testResumePresentationKeepsAdvancingSequenceHead(): void
    {
        $f = $this->f;
        $advancing = $f->last;
        $head = $f->f->head(); $head['digest'] = 'sha256:'.$head['digest'];
        $request = ['schema'=>'imperium.provider-onboarding-resume/v2', 'sequence_id'=>'sequence-test', 'command_id'=>'review-resume-command', 'expected_head'=>$head, 'recognize_command_ref'=>$advancing];
        $result = $f->recovery->resume(json_encode($request, JSON_THROW_ON_ERROR));
        self::assertTrue(R::same($advancing, $result['sequence_head']), 'Evidence-only resume must not present its own command as the advancing predecessor');
        self::assertTrue(R::same($advancing, $f->recovery->status('sequence-test')['sequence_head']));
        self::assertTrue(R::same($advancing, $f->recovery->resume(json_encode($request, JSON_THROW_ON_ERROR))['sequence_head']));
    }

    public function testStrictV2ReaderRejectsUnknownBudgetBindingBodyField(): void
    {
        $state = $this->f->f->store->journal->read()['state'];
        $key = array_key_first($state['onboarding']['budget_bindings']);
        $record = $state['onboarding']['budget_bindings'][$key]['record'];
        unset($record['record_digest']);
        $record['body']['unrecognized_authority'] = true;
        $state['onboarding']['budget_bindings'][$key]['record'] = R::seal($record);
        $refused = false;
        try { $this->f->f->store->state($state); } catch (\RuntimeException|\InvalidArgumentException) { $refused = true; }
        self::assertTrue($refused, 'A valid hash does not make an unknown budget-binding body field part of the closed v2 schema');
    }

    public function testStrictV2ReaderRejectsForeignMigrationIdentity(): void
    {
        $state = $this->f->f->store->journal->read()['state'];
        $record = $state['onboarding']['migration'];
        unset($record['record_digest']);
        $record['instance_id'] = 'instance-foreign';
        $state['onboarding']['migration'] = R::seal($record);
        $refused = false;
        try { $this->f->f->store->state($state); } catch (\RuntimeException|\InvalidArgumentException) { $refused = true; }
        self::assertTrue($refused, 'Migration receipt must belong to the enrolled instance');
    }
}
