<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{NativeState, NativeAuthority, NativeSuccessor, NativeAdmission, NativeBindingReader};
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3Contract as V3;
use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3ContractValidator as Validator;

require_once __DIR__.'/NativeTransitionBatch2Test.php';

class NativeTransitionBatch3Test extends NativeTransitionBatch2Test
{
    public function testSelectedV3CandidateDoesNotChangeEffectiveBindingWithoutPublication(): void
    {
        [$p, $a, $at] = $this->nativeInputs();
        $s = (new NativeSuccessor($this->state, static fn () => $at))->create($p['principal_version_id'], NativeState::ref($a, 'principal_activation_id'));
        $authority = (new NativeAuthority($this->state, static fn () => $at))->issue($p['principal_version_id'], $s['successor']['successor_id']);
        $records = (new NativeAdmission($this->state))->records($authority['authority']['authority_id'], $at);
        self::assertSame(V3::SCHEMA, $records['v3_admission']['schema']);
        (new Validator())->assertResult($records['v3_admission']);
        $this->fails('PBR400_SUCCESSOR_ADMISSION_V3_BOUNDARY_INVALID', fn () => (new Validator())->assert($records['v3_admission']));
        self::assertSame('NOT_IMPLEMENTED', V3::STATUS);
        self::assertFalse($records['v3_admission']['effect_start_permitted']);
        $read = (new NativeBindingReader($this->state))->read('imperium-test', 'provider-binding', 'email.send', $at);
        self::assertSame('BOUND_INACTIVE', $read['effective_status']);
        self::assertNull($read['receipt']);
    }

    public function testScopeOnlyIssuanceCannotProduceAnAdmission(): void
    {
        $p = $this->activate();
        $a = (new NativeAuthority($this->state, static fn () => 100))->issue($p['principal_version_id']);
        $this->fails('NIR_EXACT_SUCCESSOR_REQUIRED', fn () => (new NativeAdmission($this->state))->records($a['authority']['authority_id'], 100));
    }
}
