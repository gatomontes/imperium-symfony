<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Tests\Imperium\Runtime\Support\ModelPreparationFixture as F;
use App\Imperium\Runtime\Citadel\Formation\{FormationModelPreparation as P, FormationJournal as J, FormationOwnerFrame};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormationModelPreparationTest extends TestCase
{
    public static function seats(): iterable
    {
        yield ['courtyard.courtthane']; yield ['clavium.locksmith'];
    }

    #[DataProvider('seats')]
    public function testActualOriginalsStrictIngressAndOwnFullLifecycle(string $seat): void
    {
        $f = new F($seat);
        try {
            $assembly = $f->assembly();
            self::assertNotEmpty($assembly);
            self::assertSame($f->authorization['id'], $f->artifact['model_binding']['authorization_id']);
            self::assertSame(P::EVIDENCE, $f->envelope['payload']['schema']);
            self::assertSame(1, $f->correspondence['binding_generation']);
            if ($dest = getenv('PPC5_PUBLIC_EVIDENCE')) {
                foreach (['citadel/formation', 'operator-root/installations', 'operator-root/packages', 'offices/garrison/occupancy',
                    'offices/guildhall/occupancy', 'offices/laboratorium/occupancy', 'offices/senate/occupancy', 'offices/conscription/occupancy'] as $dir) {
                    $out = $dest.'/originals/'.$seat.'/'.$dir;
                    if (!is_dir($out)) { mkdir($out, 0770, true); }
                    foreach (glob($f->f->root.'/var/imperium/'.$dir.'/*.json') ?: [] as $file) { copy($file, $out.'/'.basename($file)); }
                }
                file_put_contents($dest.'/originals/'.$seat.'/lifecycle.json', json_encode(['candidate' => $f->candidate, 'assembly' => $assembly,
                    'profile' => $f->artifact, 'correspondence' => $f->correspondence, 'external_facts' => 'SYNTHETIC_UNVERIFIED'], JSON_THROW_ON_ERROR));
            }
            self::assertSame($f->seal, $f->preparation->seal($f->sealingEnvelope));
            $head = $f->head();
            $f->f->clock->at += 1201;
            self::assertSame($f->seal, $f->preparation->seal($f->sealingEnvelope));
            self::assertSame($head, $f->head());
            $this->refuses(fn() => $f->assembly());
        } finally { $f->close(); }
    }

    public static function substitutions(): iterable
    {
        foreach (['schema', 'id', 'digest', 'generation', 'configuration', 'seat', 'source_line', 'model', 'profile', 'old_schema'] as $case) { yield $case => [$case]; }
    }

    #[DataProvider('substitutions')]
    public function testStrictVerifierRefusesOriginalSubstitution(string $case): void
    {
        $f = new F();
        try {
            $profile = $f->artifact; $c = $f->correspondence; $seat = $f->seat;
            switch ($case) {
                case 'schema': $c['seal']['schema'] = P::AUTHORIZATION; break;
                case 'id': $c['seal']['id'] = 'missing-original'; break;
                case 'digest': $c['seal']['digest'] = str_repeat('a', 64); break;
                case 'generation': $c['binding_generation'] = 2; break;
                case 'configuration': $c['configuration_ref']['digest'] = 'sha256:'.str_repeat('a', 64); break;
                case 'seat': $seat = 'clavium.locksmith'; break;
                case 'source_line': $profile['model_binding']['source_line']['line_digest'] = str_repeat('a', 64); break;
                case 'model': $profile['model_binding']['provider_model_version'] = 'other'; break;
                case 'profile': $profile['profile_version'] = '9.9'; break;
                case 'old_schema': $c['seal']['schema'] = 'imperium.conscription-profile-model-binding/v1'; break;
            }
            $head = $f->head();
            $this->refuses(fn() => $f->f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner) => $f->preparation->verifyInOwner($owner, $profile, $seat, $c)));
            self::assertSame($head, $f->head());
        } finally { $f->close(); }
    }

    public function testSuccessorGenerationIsNativeAndInvalidatesPriorCurrentUse(): void
    {
        $f = new F();
        try {
            $t = $f->bindingTerms; $t['id'] = 'binding-successor'; $t['expected_head'] = $f->head();
            $this->refuses(fn() => $f->preparation->prepareBinding($t));
            $t['predecessor'] = P::reference($f->binding);
            $next = $f->preparation->prepareBinding($t);
            self::assertSame(2, $next['body']['binding_generation']);
            $this->refuses(fn() => $f->assembly());
            self::assertSame($f->seal, $f->preparation->seal($f->sealingEnvelope));
        } finally { $f->close(); }
    }

    public static function effects(): iterable
    {
        foreach (['APPROVE_FORMATION_PROFILE', 'DELEGATE_PERSONNEL_EVIDENCE', 'AUTHORIZE_BOOTSTRAP_POLICY', 'APPROVE_MODEL', 'SEAL_MISSION_MODEL', 'TOOL_PROVIDER_BINDING'] as $effect) { yield [$effect]; }
    }

    #[DataProvider('effects')]
    public function testOtherOwnerEffectsDoNotAcquirePreparationCompetence(string $effect): void
    {
        $f = new F(finish: false);
        try {
            $t = $f->authorizationTerms; $t['id'] = 'new-authorization'; $t['nonce'] = bin2hex(random_bytes(24)); $t['expected_head'] = $f->head();
            $head = $f->head();
            $this->refuses(fn() => $f->preparation->authorize($t, $f->ownerSign($t, $effect)));
            self::assertSame($head, $f->head());
        } finally { $f->close(); }
    }

    public static function revokedOriginals(): iterable { yield ['authorization']; yield ['delegation']; }

    #[DataProvider('revokedOriginals')]
    public function testRevocationPreservesHistoricalSealButRefusesCurrent(string $field): void
    {
        $f = new F();
        try {
            $nonce = $f->$field['body']['decision']['payload']['nonce'];
            $f->f->signatures->revoke($f->f->sign('REVOKE_DECISION', ['nonce' => $nonce]), $nonce);
            self::assertSame($f->seal, $f->preparation->seal($f->sealingEnvelope));
            $this->refuses(fn() => $f->assembly());
        } finally { $f->close(); }
    }

    public function testChangedReplayAndCompetingSignedSealHaveNoSecondEffect(): void
    {
        $f = new F(finish: false);
        try {
            $payload = $f->sealingEnvelope['payload']; $payload['nonce'] = bin2hex(random_bytes(24));
            $other = $f->sealerSign($payload);
            $first = $f->preparation->seal($f->sealingEnvelope); $head = $f->head();
            $this->refuses(fn() => $f->preparation->seal($other));
            self::assertSame($head, $f->head());
            self::assertSame($first, $f->preparation->seal($f->sealingEnvelope));
        } finally { $f->close(); }
    }

    public function testWrongAndExpiredOwnerRefuse(): void
    {
        $f = new F(); $other = new \App\Tests\Imperium\Runtime\Support\CitadelFormationFixture();
        try {
            $this->refuses(fn() => $other->journal->inspect(fn(array $frame, FormationOwnerFrame $owner) => $f->preparation->verifyInOwner($owner, $f->artifact, $f->seat, $f->correspondence)));
            $escaped = $f->f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner) => $owner);
            $this->refuses(fn() => $f->preparation->verifyInOwner($escaped, $f->artifact, $f->seat, $f->correspondence));
            self::assertNotEmpty($f->assembly());
        } finally { $f->close(); $other->close(); }
    }

    public function testClosedAuthorizationRefusalMatrixAndHistoricalOriginalReplay(): void
    {
        $f = new F(finish: false);
        try {
            $base = $f->authorizationTerms; $base['id'] = 'second-authorization'; $base['expected_head'] = $f->head();
            $base['nonce'] = bin2hex(random_bytes(24));
            $changes = [
                static function (&$t) { $t['context']['instance_id'] = 'wrong-instance'; },
                static function (&$t) { $t['context']['citadel_id'] = 'wrong-formation'; },
                static function (&$t) { $t['context']['seat'] = 'clavium.locksmith'; },
                static function (&$t) { $t['context']['steward']['id'] = 'conscription'; },
                static function (&$t) { $t['source_line']['line_number'] = 2; },
                static function (&$t) { $t['source_line']['schema'] = P::BINDING; },
                static function (&$t) { $t['source_line']['binding_generation'] = 9; },
                static function (&$t) { $t['binding']['schema'] = P::DELEGATION; },
                static function (&$t) { $t['binding']['digest'] = str_repeat('f', 64); },
                static function (&$t) { $t['delegation']['id'] = 'unknown-delegation'; },
                static function (&$t) { $t['source_profile']['profile_version'] = '9.0'; },
                static function (&$t) { $t['source_evidence'] = str_repeat('e', 64); },
                static function (&$t) { $t['purpose'] = 'APPROVE_MODEL'; },
                static function (&$t) { $t['expires_at'] = $t['not_before']; },
                static function (&$t) { $t['expires_at'] = $t['not_before'] + 3601; },
                static function (&$t) { $t['expected_head']['generation']++; },
                static function (&$t) { $t['extra'] = 'not-in-schema'; },
            ];
            $head = $f->head();
            foreach ($changes as $change) {
                $t = $base; $change($t);
                $this->refuses(fn() => $f->preparation->authorize($t, $f->ownerSign($t)));
                self::assertSame($head, $f->head());
            }
            $f->f->clock->at += 3601;
            $a = $f->authorization;
            self::assertSame($a, $f->preparation->authorize($a['body']['terms'], $a['body']['decision']));
            $d = $f->delegation;
            self::assertSame($d, $f->preparation->delegate($d['body']['terms'], $d['body']['decision']));
            self::assertSame($f->binding, $f->preparation->prepareBinding($f->bindingTerms));
            self::assertSame($head, $f->head());
            $this->refuses(fn() => $f->preparation->seal($f->sealingEnvelope));
        } finally { $f->close(); }
    }

    public function testWrapperBytesLineageVacancyAndVersionedIngressHaveNoFallback(): void
    {
        $f = new F();
        try {
            $base = $f->bindingTerms; $base['id'] = 'binding-next-test'; $base['predecessor'] = P::reference($f->binding); $base['expected_head'] = $f->head();
            $changes = [
                static function (&$t) { $t['lineage_id'] = 'renamed-lineage'; $t['predecessor'] = null; },
                static function (&$t) { $t['predecessor'] = null; },
                static function (&$t) { $t['predecessor']['id'] = $t['id']; },
                static function (&$t) { $t['binding_generation'] = 900; },
                static function (&$t) { $t['specification']['model_id'] = 'unapproved-model'; },
                static function (&$t) { $t['specification']['provider'] = 'unapproved-provider'; },
                static function (&$t) { $t['configuration_original']['body']['content'] .= ' '; },
                static function (&$t) { $t['configuration_original']['producer']['service'] = 'substituted-metadata'; },
                static function (&$t) { $t['binding_original']['schema'] = 'imperium.other-source/v1'; },
                static function (&$t) { $t['specification']['configuration']['max_tokens'] = 1; },
                static function (&$t) { $t['specification']['access_assertion_required'] = false; },
                static function (&$t) { $t['binding_original']['sources'] = array_fill(0, 65, $t['predecessor']); },
                static function (&$t) { $t['binding_original']['producer']['service'] = str_repeat('x', 131073); },
            ];
            $head = $f->head();
            foreach ($changes as $change) { $t = $base; $change($t); $this->refuses(fn() => $f->preparation->prepareBinding($t)); self::assertSame($head, $f->head()); }
            // Rehashed wrapper metadata is a new original, not a content-only alias.
            $next = $base; $next['binding_original']['producer']['service'] = 'different-metadata';
            unset($next['binding_original']['record_digest']);
            $next['binding_original'] = \App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules::seal($next['binding_original']);
            $b = $f->preparation->prepareBinding($next);
            self::assertNotSame($f->binding['body']['binding_ref'], $b['body']['binding_ref']);
            $this->refuses(fn() => $f->assembly());
            $this->refuses(fn() => $f->f->personnel->recordModelBoundProfile($f->envelope));
            $old = $f->f->journal->read()['state']['personnel_evidence'][$f->oldCandidate['profile']];
            $this->refuses(fn() => $f->f->personnel->recordAuthorizedModelBoundProfile($old));
        } finally { $f->close(); }
    }

    public function testFiniteBoundsAndTrustTamperRefuseCurrentAndPreserveHistoricalSeal(): void
    {
        $f = new F();
        try {
            // Deliberate invalid-custody negative fixture; never positive issuer evidence.
            $f->f->journal->change(static function (array &$state): void { $state['trust']['revoked'] = true; });
            $this->refuses(fn() => $f->assembly());
            self::assertSame($f->seal, $f->preparation->seal($f->sealingEnvelope));
            $f->f->journal->change(static function (array &$state): void {
                $state['model_preparation']['bindings'] = array_fill_keys(array_map(static fn(int $i): string => 'tampered-'.$i, range(1, P::MAX + 1)), []);
            });
            $this->refuses(fn() => $f->preparation->seal($f->sealingEnvelope));
        } finally { $f->close(); }
    }

    public function testStrictLifecycleRejectsPpc2SyntheticEvidence(): void
    {
        $f = new \App\Tests\Imperium\Runtime\Support\ModelBoundFormationFixture();
        try {
            self::assertNotEmpty($f->assembly()); // Historical structural meaning is preserved.
            $this->refuses(fn() => $f->f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner) =>
                $f->f->personnel->authorizedModelCandidateInOwner($owner, $f->candidate, $frame['state']['citadel_id'], $f->seat)));
        } finally { $f->close(); }
    }

    public function testNewAuthorizationCannotReuseOutputProfileVersionAndNativePartialPackageRefuses(): void
    {
        $f = new F();
        try {
            $t = $f->authorizationTerms; $t['id'] = 'another-authorization'; $t['nonce'] = bin2hex(random_bytes(24)); $t['expected_head'] = $f->head();
            $a = $f->preparation->authorize($t, $f->ownerSign($t));
            $p = $f->preparation->sealingPayload(P::reference($a), $f->head(), bin2hex(random_bytes(24)));
            $head = $f->head(); $this->refuses(fn() => $f->preparation->seal($f->sealerSign($p)));
            self::assertSame($head, $f->head());
            // Interrupted institutional package is negative disposable custody, not an alternative producer.
            $packages = glob($f->f->root.'/var/imperium/operator-root/packages/*.json');
            self::assertNotEmpty($packages); file_put_contents($packages[0], '{');
            try { $f->assembly(); self::fail('Incomplete native package must refuse'); }
            catch (\RuntimeException|\JsonException $e) { self::assertNotSame('', $e->getMessage()); }
            self::assertSame($f->seal, $f->preparation->seal($f->sealingEnvelope));
        } finally { $f->close(); }
    }

    public function testInitializationUnavailableUntilExplicitSignedHeadAndRefusesInFlight(): void
    {
        $f = new \App\Tests\Imperium\Runtime\Support\CitadelFormationFixture();
        try {
            $p = new P($f->journal, $f->signatures, $f->clock, new \App\Imperium\Runtime\Citadel\Formation\FormationInstitution($f->root));
            $this->refuses(fn() => $p->sealingPayload(['schema' => P::AUTHORIZATION, 'id' => 'unavailable-original', 'digest' => str_repeat('a', 64)],
                ['generation' => 1, 'digest' => str_repeat('b', 64)], bin2hex(random_bytes(24))));
            $f->journal->change(static function (array &$s): void { $s['sessions'] = ['synthetic-in-flight' => []]; });
            $frame = $f->journal->read();
            $t = ['schema' => P::STATE, 'instance_id' => 'synthetic-parent-imperium', 'citadel_id' => $frame['state']['citadel_id'],
                'expected_head' => ['generation' => $frame['generation'], 'digest' => $frame['record_digest']]];
            $this->refuses(fn() => $p->initialize($t, $f->sign('INITIALIZE_FORMATION_MODEL_PREPARATION', $t)));
            self::assertArrayNotHasKey('model_preparation', $f->journal->read()['state']);
        } finally { $f->close(); }
    }

    public function testCompleteHAncestryIsOrderedExactAndConsumedByRealStrictLifecycle(): void
    {
        $f = new F(finish: false);
        try {
            $leaf = $f->bindingTerms['binding_original']; unset($leaf['record_digest']);
            $leaf['id'] = 'ancestor-leaf'; $leaf['body']['kind'] = 'public-note'; $leaf['body']['content'] = 'Synthetic public dependency';
            $leaf['body']['content_digest'] = 'sha256:'.hash('sha256', $leaf['body']['content']);
            $leaf = \App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules::seal($leaf);
            $parent = $leaf; unset($parent['record_digest']); $parent['id'] = 'ancestor-parent';
            $parent['sources'] = [\App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules::reference($leaf)];
            $parent = \App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules::seal($parent);
            $t = $f->bindingTerms; $t['id'] = 'binding-with-ancestry'; $t['predecessor'] = P::reference($f->binding); $t['expected_head'] = $f->head();
            unset($t['binding_original']['record_digest']);
            $t['binding_original']['sources'] = [\App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules::reference($parent)];
            $t['binding_original'] = \App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules::seal($t['binding_original']);
            $head = $f->head();
            foreach ([[], [$parent, $leaf], [$leaf, $leaf, $parent]] as $ancestors) {
                $t['supporting_originals'] = $ancestors;
                $this->refuses(fn() => $f->preparation->prepareBinding($t)); self::assertSame($head, $f->head());
            }
            $t['supporting_originals'] = [$leaf, $parent]; $f->binding = $f->preparation->prepareBinding($t);
            self::assertSame(2, $f->binding['body']['binding_generation']);
            $d = $f->delegation['body']['terms']; $d['id'] = 'sealer-with-ancestry'; $d['binding'] = P::reference($f->binding); $d['expected_head'] = $f->head();
            $f->delegation = $f->preparation->delegate($d, $f->f->sign('DELEGATE_FORMATION_MODEL_SEALING', $d));
            $a = $f->authorizationTerms; $a['id'] = 'authorization-with-ancestry'; $a['nonce'] = bin2hex(random_bytes(24));
            $a['binding'] = P::reference($f->binding); $a['source_line'] = P::sourceLine($f->binding);
            $a['delegation'] = P::reference($f->delegation); $a['expected_head'] = $f->head();
            $f->authorization = $f->preparation->authorize($a, $f->ownerSign($a));
            $f->sealingEnvelope = $f->sealerSign($f->preparation->sealingPayload(P::reference($f->authorization), $f->head(), bin2hex(random_bytes(24))));
            $f->finish(); self::assertNotEmpty($f->assembly());
            self::assertSame(P::SOURCE, $f->sealingEnvelope['payload']['source_evidence']['schema']);
            $bad = $a; $bad['id'] = 'wrong-typed-source'; $bad['expected_head'] = $f->head(); $bad['nonce'] = bin2hex(random_bytes(24));
            $bad['source_evidence']['schema'] = P::AUTHORIZATION;
            $this->refuses(fn() => $f->preparation->authorize($bad, $f->ownerSign($bad)));
        } finally { $f->close(); }
    }

    public function testCompleteTypedSourceOriginalHasItsOwnBound(): void
    {
        $f = new F(finish: false); $pair = sodium_crypto_sign_keypair(); $secret = sodium_crypto_sign_secretkey($pair);
        try {
            $d = ['role' => 'laboratorium', 'public_key' => base64_encode(sodium_crypto_sign_publickey($pair)),
                'scope' => $f->context['citadel_id'], 'expires_at' => $f->f->clock->at + 3600, 'actor' => $f->f->personnel->authoritySource('laboratorium')];
            $id = $f->f->personnel->delegate($d, $f->f->sign('DELEGATE_PERSONNEL_EVIDENCE', $d));
            $p = ['delegation' => $id, 'scope' => $d['scope'], 'kind' => 'DERIVED_PROFILE',
                'subject' => $f->authorizationTerms['source_profile']['profile_id'],
                'sources' => [$f->oldCandidate['persona'], $f->oldCandidate['suitability']],
                'content' => ['seat' => $f->seat, 'artifact' => $f->authorizationTerms['source_profile'], 'extra' => str_repeat('x', 262144)],
                'expires_at' => $d['expires_at']];
            $id = $f->f->personnel->record(['payload' => $p, 'signature' => base64_encode(sodium_crypto_sign_detached(\App\Bootstrap\CanonicalJson::encode($p), $secret))]);
            $t = $f->authorizationTerms; $t['id'] = 'oversized-source-authorization'; $t['nonce'] = bin2hex(random_bytes(24));
            $t['source_evidence'] = $f->preparation->sourceOriginal($id); $t['expected_head'] = $f->head();
            try { $f->preparation->authorize($t, $f->ownerSign($t)); self::fail('Complete source bound required'); }
            catch (\RuntimeException $e) { self::assertSame('PPC5_SOURCE_PROFILE_BOUND', $e->getMessage()); }
            self::assertSame($t['expected_head'], $f->head());
        } finally { sodium_memzero($secret); $f->close(); }
    }

    private function refuses(callable $call): void
    {
        try { $call(); } catch (\RuntimeException $e) { self::assertNotSame('', $e->getMessage()); return; }
        self::fail('Expected bounded refusal');
    }
}
