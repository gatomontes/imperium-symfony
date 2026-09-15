<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\{FormationInstitution as I, FormationOwnerFrame as O, FormationModelBoundProfileContract as B, FreshInstitutionPackage as Package, FreshInstitutionalProfileMapping as Mapping};
use App\Tests\Imperium\Runtime\FreshEstablishmentRevocationDurabilityTest as Proof;
use PHPUnit\Framework\Assert;

/** Adversarial current reads after real completed producers; never imports accepted journal state. */
final class FreshConsumerClosure
{
    public static function prove(FreshEstablishmentFixture $f, array $models): void
    {
        $state = $f->journal->read()['state']; $calls = []; $institution = new I($f->root);
        Proof::refuses(fn() => $f->personnel->currentCourtthane($state), 'PPC7_CURRENT_CANDIDATE_OWNER_REQUIRED');
        Proof::refuses(fn() => $f->personnel->currentLocksmith($state), 'PPC303_CURRENT_OWNER_STATE_REQUIRED');
        Proof::refuses(fn() => $f->personnel->candidate($state, $models[0]->m->candidate, $f->store->citadel, $models[0]->seat), 'PPC7_CURRENT_CANDIDATE_OWNER_REQUIRED');
        Proof::refuses(fn() => $f->personnel->currentCastellan($state), 'CY001_LEGACY_CASTELLAN_FRESH_USE_REFUSED');
        Proof::refuses(fn() => $f->personnel->appointCastellan($models[0]->m->candidate, []), 'CY001_LEGACY_CASTELLAN_FRESH_USE_REFUSED');
        $command = new \Symfony\Component\Console\Tester\CommandTester(new \App\Command\CitadelPublicInstitutionsCommand($institution));
        Assert::assertSame(2, $command->execute([])); $publicObservation = json_decode($command->getDisplay(), true, 64, JSON_THROW_ON_ERROR);
        Assert::assertFalse($publicObservation['live_ready']); Assert::assertFalse($publicObservation['execution_authority']);
        Assert::assertCount(9, $publicObservation['institutions']);
        foreach ($publicObservation['institutions'] as $row) { Assert::assertSame('UNVERIFIED', $row['status']); }
        foreach (I::SEATS as $role => $seat) {
            $calls['actor:'.$role] = fn() => $institution->actor($role);
            $calls['actorInOwner:'.$role] = fn() => $f->journal->inspect(fn(array $frame, O $owner) => $institution->actorInOwner($owner, $role));
            $calls['authoritySource:'.$role] = fn() => $f->personnel->authoritySource($role);
        }
        foreach ($state['personnel_delegations'] as $delegation) {
            $role = $delegation['terms']['role'];
            $calls['delegate:'.$role] = fn() => $f->personnel->delegate($delegation['terms'], $delegation['decision']);
        }
        foreach ($state['personnel_evidence'] as $envelope) {
            if (!isset($envelope['payload']['schema'])) { $calls['record:'.$envelope['payload']['kind']] = fn() => $f->personnel->record($envelope); }
        }
        foreach ($models as $model) {
            $seat = $model->seat; $candidate = $model->m->candidate;
            $role = $seat === 'courtyard.courtthane' ? 'courtthane' : 'locksmith'; $holder = $state[$role];
            $method = $role === 'courtthane' ? 'appointCourtthane' : 'appointLocksmith';
            $current = $role === 'courtthane' ? 'currentCourtthaneInOwner' : 'currentLocksmithInOwner';
            $calls['appointmentReplay:'.$seat] = fn() => $f->personnel->$method($candidate, $holder['decision']);
            $calls['currentHolder:'.$seat] = fn() => $f->journal->inspect(fn(array $frame, O $owner) => $f->personnel->$current($owner));
            $calls['candidateInOwner:'.$seat] = fn() => $f->journal->inspect(fn(array $frame, O $owner) => $f->personnel->candidateInOwner($owner, $frame['state'], $candidate, $f->store->citadel, $seat));
            $calls['authorizedModelCandidateInOwner:'.$seat] = fn() => $f->journal->inspect(fn(array $frame, O $owner) => $f->personnel->authorizedModelCandidateInOwner($owner, $candidate, $f->store->citadel, $seat));
            $calls['recordAuthorizedModelBoundProfile:'.$seat] = fn() => $f->personnel->recordAuthorizedModelBoundProfile($model->m->envelope);
            $calls['designationCurrent:'.$seat] = fn() => $model->current();
            $calls['modelVerifyInOwner:'.$seat] = fn() => $f->journal->inspect(fn(array $frame, O $owner) => $model->m->preparation->verifyInOwner($owner, $model->m->artifact, $seat, $model->m->correspondence));
            $calls['mapping:'.$seat] = fn() => $f->journal->inspect(fn(array $frame, O $owner) => (new Mapping($f->root, $f->store))->prepareInOwner($owner, $seat));
        }
        // Real PPC2 ingress with its own native delegation and valid signed versioned payload.
        $pair = sodium_crypto_sign_keypair(); $secret = sodium_crypto_sign_secretkey($pair);
        $t = ['role' => 'laboratorium', 'public_key' => base64_encode(sodium_crypto_sign_publickey($pair)), 'scope' => $f->store->citadel,
            'expires_at' => $f->clock->at + 300, 'actor' => $f->personnel->authoritySource('laboratorium')];
        $delegation = $f->personnel->delegate($t, $f->sign('DELEGATE_PERSONNEL_EVIDENCE', $t));
        $m = $models[0]->m; $payload = $m->envelope['payload']; $payload['schema'] = B::SCHEMA; $payload['delegation'] = $delegation;
        $payload['expires_at'] = $t['expires_at']; unset($payload['content']['correspondence']);
        $envelope = ['payload' => $payload, 'signature' => base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload), $secret))];
        sodium_memzero($secret);
        Assert::assertNotEmpty($f->personnel->recordModelBoundProfile($envelope));
        $calls['recordModelBoundProfile'] = fn() => $f->personnel->recordModelBoundProfile($envelope);
        $adapter = new \App\Imperium\Runtime\Citadel\Formation\FreshInstitutionalPreparation($f->root, $f->store);
        foreach (['model_preparation' => 'initializeModel', 'profile_designations' => 'initializeDesignations'] as $kind => $method) {
            $original = $state[$kind]['initialization'];
            $calls[$method] = fn() => $adapter->$method($original['terms'], $original['decision']);
        }
        $unconsumed = [];
        foreach ($models as $model) {
            $m = $model->m; $terms = $m->authorizationTerms;
            $terms['id'] = 'pending-preparation-'.bin2hex(random_bytes(8)); $terms['nonce'] = bin2hex(random_bytes(24));
            $terms['source_profile'] = $m->artifact; $terms['source_evidence'] = $m->preparation->sourceOriginal($m->candidate['profile']); $terms['expected_head'] = $f->head();
            $unconsumed[$model->seat] = $m->preparation->authorize($terms, $m->ownerSign($terms));
        }
        foreach ($models as $model) {
            $seat = $model->seat; $m = $model->m; $head = $f->head();
            $pendingRef = \App\Imperium\Runtime\Citadel\Formation\FormationModelPreparation::reference($unconsumed[$seat]);
            $sealEnvelope = $m->sealerSign($m->preparation->sealingPayload($pendingRef, $head, bin2hex(random_bytes(24))));
            $calls['modelSeal:'.$seat] = fn() => $m->preparation->seal($sealEnvelope);
            $terms = $m->bindingTerms; $terms['id'] = 'new-binding-'.bin2hex(random_bytes(8)); $terms['expected_head'] = $head;
            $terms['predecessor'] = \App\Imperium\Runtime\Citadel\Formation\FormationModelPreparation::reference($m->binding);
            $calls['prepareBinding:'.$seat] = fn() => $m->preparation->prepareBinding($terms);
            $terms = $m->delegation['body']['terms']; $terms['id'] = 'new-delegation-'.bin2hex(random_bytes(8)); $terms['expected_head'] = $head;
            $decision = $f->sign('DELEGATE_FORMATION_MODEL_SEALING', $terms);
            $calls['modelDelegate:'.$seat] = fn() => $m->preparation->delegate($terms, $decision);
            $terms = $m->authorizationTerms; $terms['id'] = 'new-authorization-'.bin2hex(random_bytes(8)); $terms['expected_head'] = $head; $terms['nonce'] = bin2hex(random_bytes(24));
            $decision = $m->ownerSign($terms);
            $calls['modelAuthorize:'.$seat] = fn() => $m->preparation->authorize($terms, $decision);
            $reference = \App\Imperium\Runtime\Citadel\Formation\FormationModelPreparation::reference($m->authorization);
            $calls['sealingPayload:'.$seat] = fn() => $m->preparation->sealingPayload($reference, $head, bin2hex(random_bytes(24)));
            $d = $state['profile_designations']['delegations'][$model->delegations[\App\Imperium\Runtime\Citadel\Formation\FormationProfileDesignation::DESIGNATE]['id']];
            $terms = $d['terms']; $terms['expected_head'] = $head; $decision = $f->sign('DELEGATE_FORMATION_PROFILE_DESIGNATION', $terms);
            $calls['designationDelegate:'.$seat] = fn() => $model->service->delegate($terms, $decision);
            $oldEvent = array_values(array_filter($state['profile_designations']['events'], static fn(array $event): bool => $event['envelope']['payload']['target'] === $seat))[0];
            $newEnvelope = $model->envelope($oldEvent); // Signature and current head are valid; current actor precedes later predecessor reuse checks.
            $calls['designationPublish:'.$seat] = fn() => $model->service->publish($newEnvelope, $model->m->candidate);
        }
        $before = $f->journal->read(); $layout = Package::layout($f->root, $f->reservation);
        $relative = array_key_first($layout['files']); $path = $f->root.'/'.$relative; $bytes = file_get_contents($path); $rows = [];
        foreach (['missing', 'corrupt', 'successor'] as $damage) {
            $successor = $f->root.'/var/imperium/bootstrap-state.json';
            try {
                if ($damage === 'missing') { unlink($path); }
                elseif ($damage === 'corrupt') { file_put_contents($path, $bytes.' '); }
                else { file_put_contents($successor, '{}'); }
                foreach ($calls as $name => $call) {
                    // Exercise every distinct ingress on missing placement. Additional corruption/successor
                    // states cover all nine actors plus the shared downstream candidate/appointment/mapping joins.
                    if ($damage !== 'missing' && !str_starts_with($name, 'actor:') && !str_starts_with($name, 'candidateInOwner:')
                        && !str_starts_with($name, 'appointmentReplay:') && !str_starts_with($name, 'mapping:')) { continue; }
                    $expected = $damage === 'successor' ? 'PPC7_SUCCESSOR' : 'PPC7_NATIVE_ORIGINAL';
                    Proof::refuses($call, $expected); $rows[] = ['entry' => $name, 'damage' => $damage, 'refusal' => $expected];
                }
                Assert::assertSame($before, $f->journal->read());
            } finally {
                // Restore only this disposable test's original bytes between rejection experiments.
                if ($damage !== 'successor') { file_put_contents($path, $bytes); }
                elseif (is_file($successor)) { unlink($successor); }
            }
        }
        foreach ($models as $model) {
            $seat = $model->seat; $role = $seat === 'courtyard.courtthane' ? 'courtthane' : 'locksmith';
            $method = $role === 'courtthane' ? 'appointCourtthane' : 'appointLocksmith'; $holder = $state[$role];
            Assert::assertSame($holder, $f->personnel->$method($model->m->candidate, $holder['decision']));
        }
        Assert::assertSame($before, $f->journal->read());
        Proof::evidence($f, 'actual-consumer-entries', ['before' => $before, 'after' => $f->journal->read(), 'mutated_native_path' => $relative,
            'original_bytes_base64' => base64_encode($bytes), 'observations' => $rows, 'public_command' => $publicObservation,
            'source_of_authority' => 'Original supported same-root chain; temporary fixture damage only; no seeded journal authority']);
    }
}
