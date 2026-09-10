<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Admission, Resolver, Rules};
use App\Tests\Imperium\Runtime\Support\OnboardingAuthorityFixture as F;
use PHPUnit\Framework\TestCase;

/** Reviewer regression requirements. Synthetic real producers, no direct state seeding. */
final class ProviderOnboardingAuthorityReviewRegressionTest extends TestCase
{
    private F $f;
    protected function setUp(): void { $this->f = new F(); $this->f->enroll(); }
    protected function tearDown(): void { $this->f->close(); }

    private function expectRefusal(callable $operation, string $message): void
    {
        $before = $this->f->head();
        $error = null;
        try { $operation(); } catch (\RuntimeException|\InvalidArgumentException $caught) { $error = $caught; }
        self::assertNotNull($error, $message);
        self::assertSame($before, $this->f->head(), 'Refusal must preserve the aggregate head');
    }

    private function shortSignedSlot(): array
    {
        $policy = $this->f->policy();
        $policy['body']['effect_slots'][0]['authority_mode'] = 'signed_act';
        $policy['body']['effect_slots'][0]['expires_at'] = $this->f->now + 1;
        unset($policy['record_digest']);
        $policy = Rules::seal($policy);
        $this->f->admitPolicy($policy);
        $ref = $policy['body']['effect_slots'][0]['terms_rule']['object_ref'];
        return [$policy, $this->f->sources[Rules::key($ref)]];
    }

    public function testExpiredSignedSlotCannotAuthorizeNewAdmission(): void
    {
        [$policy, $terms] = $this->shortSignedSlot();
        $this->f->now += 2;
        $envelope = $this->f->sign($terms, 'ADMIT_BOOTSTRAP_EVIDENCE', Rules::reference($policy));
        $envelope['payload']['expires_at'] = $policy['body']['expires_at'];
        $envelope = $this->f->resign($envelope);
        $this->expectRefusal(
            fn () => (new Admission($this->f->store))->retain(F::json($envelope), F::json($terms)),
            'Expired signed_act slot admitted a fresh dependent original'
        );
    }

    public function testSignedSlotExpiryStopsCurrentResolution(): void
    {
        [$policy, $terms] = $this->shortSignedSlot();
        $envelope = $this->f->sign($terms, 'ADMIT_BOOTSTRAP_EVIDENCE', Rules::reference($policy));
        $result = (new Admission($this->f->store))->retain(F::json($envelope), F::json($terms));
        $this->f->now += 2;
        $this->expectRefusal(
            fn () => (new Resolver($this->f->store))->current(F::authority($result), 'ADMIT_BOOTSTRAP_EVIDENCE', Rules::reference($terms)),
            'Expired signed_act slot still reports current static authority'
        );
    }

    public function testResuppliedSourcesCannotBypassRetainedRevocation(): void
    {
        [$policy, $originalAct] = $this->f->admitPolicy();
        $raws = array_map(F::json(...), array_values($this->f->sources));
        $this->f->revoke('act', $originalAct['payload']['nonce']);
        $newPolicy = $policy;
        $newPolicy['id'] = 'policy-review-second';
        unset($newPolicy['record_digest']);
        $newPolicy = Rules::seal($newPolicy);
        $act = $this->f->sign($newPolicy, 'AUTHORIZE_BOOTSTRAP_POLICY');
        $this->expectRefusal(
            fn () => (new Admission($this->f->store))->retain(F::json($act), F::json($newPolicy), $raws),
            'Resupplying exact revoked-source bytes admitted a new dependent policy'
        );
    }

    public function testExpiredPolicyDoesNotResolveAsCurrentAuthorization(): void
    {
        $policy = $this->f->policy();
        $policy['body']['expires_at'] = $this->f->now + 1;
        foreach ($policy['body']['effect_slots'] as &$slot) { $slot['expires_at'] = $this->f->now + 1; }
        unset($slot, $policy['record_digest']);
        $policy = Rules::seal($policy);
        [$policy, , $result] = $this->f->admitPolicy($policy);
        $this->f->now += 2;
        $this->expectRefusal(
            fn () => (new Resolver($this->f->store))->current(F::authority($result), 'AUTHORIZE_BOOTSTRAP_POLICY', Rules::reference($policy)),
            'Expired policy still reports current static policy authorization'
        );
    }
}
