<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeConsumer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapability;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReceiptInputContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectSemanticIdentity;
use App\Imperium\Runtime\ProviderTransition\NativeEffectTupleDispositionContract;
use App\Imperium\Runtime\ProviderTransition\NativeState;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch2Test.php';

final class CanonicalNativeEffectContinuationExclusivityRemediationBatch2Test extends CanonicalNativeEffectCorridorActivationBatch2Test
{
    public function testNewWinnerPublishesCompleteTupleAdmissionAndReturnsEphemeralCustody(): void
    {
        [$authority, $at] = $this->authorityFixture();
        $credentials = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $credential = $credentials->issue($authority, $authority['execution_boundary']['id'], $at);

        $outcome = (new NativeEffectAtomicAdmissionService($this->state, $credentials, $continuations))->admit($authority, $credential, $at);

        self::assertTrue($outcome->newlyPublished);
        self::assertInstanceOf(NativeEffectContinuationCapability::class, $outcome->continuation);
        self::assertTrue($continuations->recognizes($outcome->continuation));
        self::assertFalse($credentials->recognizes($credential));
        self::assertSame(NativeEffectSemanticIdentity::tupleId($authority), $outcome['semantic_effect_tuple_id']);
        self::assertSame($outcome['semantic_effect_tuple_id'], $outcome['effect_replay_identity']);
        self::assertSame(NativeEffectSemanticIdentity::admissionId($outcome['semantic_effect_tuple_id']), $outcome['admission_id']);
        self::assertSame(NativeEffectReceiptInputContract::REQUIRED_FIELDS, array_keys($outcome['receipt_input']));
        self::assertSame($authority['expected_return_contract'], $outcome['receipt_input']['expected_return_contract']);
        self::assertSame($authority['provider'], $outcome['receipt_input']['provider']);
        self::assertSame([], glob($this->root.'/var/imperium/runtime/canonical-native-effect-callback-starts/*.json') ?: []);
    }

    public function testDistinctAuthorityForSameTupleIsUnconsumedLoser(): void
    {
        [$winner, $at] = $this->authorityFixture();
        $loser = $winner;
        $loser['authority_id'] = 'native-effect-authority-loser';
        $loser['issuer'] = 'imperator.alternate-effect-authority-issuer/v1';
        $loser = NativeState::seal($loser);
        self::assertSame(NativeEffectSemanticIdentity::tupleId($winner), NativeEffectSemanticIdentity::tupleId($loser));

        $credentials = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $winnerCredential = $credentials->issue($winner, $winner['execution_boundary']['id'], $at);
        $loserCredential = $credentials->issue($loser, $loser['execution_boundary']['id'], $at);
        $service = new NativeEffectAtomicAdmissionService($this->state, $credentials, $continuations);
        $service->admit($winner, $winnerCredential, $at);
        $this->fails('CNE306_EFFECT_TUPLE_ALREADY_WON', fn () => $service->admit($loser, $loserCredential, $at));

        self::assertTrue($credentials->recognizes($loserCredential), 'The losing authority capability must not be consumed.');
        self::assertCount(1, glob($this->root.'/'.NativeEffectAtomicAdmissionService::ADMISSIONS.'/*.json') ?: []);
        $paths = glob($this->root.'/'.NativeEffectAtomicAdmissionService::DISPOSITIONS.'/*.json') ?: [];
        self::assertCount(1, $paths);
        $disposition = json_decode((string) file_get_contents($paths[0]), true, 32, JSON_THROW_ON_ERROR);
        self::assertSame(NativeEffectTupleDispositionContract::LOSER, $disposition['outcome']);
        self::assertFalse($disposition['candidate_authority_consumed']);
        self::assertFalse($disposition['continuation_capability_minted']);
        self::assertFalse($disposition['callback_permitted']);
    }

    public function testFreshServiceExactReplayHasReconciliationOnlyAndCannotReconstructContinuation(): void
    {
        [$authority, $at] = $this->authorityFixture();
        $firstCredentials = new NativeEffectCredentialCapabilityIssuer();
        $firstContinuations = new NativeEffectContinuationCapabilityIssuer();
        $firstCredential = $firstCredentials->issue($authority, $authority['execution_boundary']['id'], $at);
        $first = (new NativeEffectAtomicAdmissionService($this->state, $firstCredentials, $firstContinuations))->admit($authority, $firstCredential, $at);
        self::assertNotNull($first->continuation);

        $freshCredentials = new NativeEffectCredentialCapabilityIssuer();
        $freshContinuations = new NativeEffectContinuationCapabilityIssuer();
        $unusedCredential = $freshCredentials->issue($authority, $authority['execution_boundary']['id'], $at);
        $replay = (new NativeEffectAtomicAdmissionService($this->state, $freshCredentials, $freshContinuations))->admit($authority, $unusedCredential, $at);

        self::assertFalse($replay->newlyPublished);
        self::assertNull($replay->continuation);
        self::assertTrue($freshCredentials->recognizes($unusedCredential));
        self::assertSame($first->admission, $replay->admission);
        $lookalike = new NativeEffectContinuationCapability(...array_values([
            'capabilityId' => $first->continuation->capabilityId,
            'admissionId' => $first->continuation->admissionId,
            'admissionDigest' => $first->continuation->admissionDigest,
            'semanticEffectTupleId' => $first->continuation->semanticEffectTupleId,
            'authorityConsumptionId' => $first->continuation->authorityConsumptionId,
            'processBoundaryId' => $first->continuation->processBoundaryId,
            'expiresAt' => $first->continuation->expiresAt,
        ]));
        self::assertFalse($firstContinuations->recognizes($lookalike));
        self::assertFalse($freshContinuations->recognizes($first->continuation));
    }

    public function testSameAuthorityCannotMoveToAnotherTuple(): void
    {
        [$authority, $at] = $this->authorityFixture();
        $credentials = new NativeEffectCredentialCapabilityIssuer();
        $continuations = new NativeEffectContinuationCapabilityIssuer();
        $service = new NativeEffectAtomicAdmissionService($this->state, $credentials, $continuations);
        $service->admit($authority, $credentials->issue($authority, $authority['execution_boundary']['id'], $at), $at);

        $changed = $authority;
        $changed['payload_digest'] = str_repeat('c', 64);
        $changed = NativeState::seal($changed);
        $changedCredential = $credentials->issue($changed, $changed['execution_boundary']['id'], $at);
        $this->fails('CNE302_EFFECT_AUTHORITY_ALREADY_USED', fn () => $service->admit($changed, $changedCredential, $at));
        self::assertTrue($credentials->recognizes($changedCredential));
        self::assertCount(1, glob($this->root.'/'.NativeEffectAtomicAdmissionService::ADMISSIONS.'/*.json') ?: []);
    }

    public function testSourcePinsTheAcyclicLockAndPostPublicationMintOrder(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/NativeEffectAtomicAdmissionService.php');
        $native = strpos($source, '$this->state->locked');
        $authority = strpos($source, "'canonical-native-effect-authority:");
        $tuple = strpos($source, "'canonical-native-effect-tuple:");
        $publish = strpos($source, '$this->records->put(self::ADMISSIONS');
        $mint = strpos($source, '$this->continuations->issueForNewWinner');
        foreach ([$native, $authority, $tuple, $publish, $mint] as $position) { self::assertIsInt($position); }
        self::assertTrue($native < $authority && $authority < $tuple && $tuple < $publish && $publish < $mint);
        foreach (['NativeEffectDoubleExecutionService', 'AgentMail', 'CredentialBroker', 'HttpClient', 'curl_'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    private function authorityFixture(): array
    {
        [$transitionAuthority, $at] = $this->readyTransition();
        $native = (new NativeConsumer($this->state, static fn () => $at))->execute($transitionAuthority);
        return [$this->effectAuthority($native['root'], $at), $at];
    }
}
