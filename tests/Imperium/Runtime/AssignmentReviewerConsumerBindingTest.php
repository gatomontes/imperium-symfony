<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Tests\Imperium\Runtime\Support\{AssignmentFixture, CitadelFormationFixture};
use App\Imperium\Runtime\Citadel\Formation\{BoundedFormationTransport, FormationCognition};
use App\Imperium\Runtime\Clavium\{ProviderResponseEnvelopeService, FormationSessionLeaseService};
use App\Imperium\Runtime\Onboarding\Assignment\SettingsBoundTransport;
use App\Imperium\Runtime\Curia\ReceivingFormationHandoffService;
use PHPUnit\Framework\TestCase;

/** Independent negative integration probe; both roots use real synthetic producers. */
final class AssignmentReviewerConsumerBindingTest extends TestCase
{
    public function testForeignSettingsAndUnassessedProfileCannotReachActualFormationTransport(): void
    {
        $assignment = new AssignmentFixture();
        $formation = new CitadelFormationFixture();
        try {
            $assignment->assessed();
            $d = $assignment->fresh->d;
            $assignment->ledger()->advance($d->f::json($d->request('apply-assignments')));
            $settings = $assignment->settings();
            $tuple = $settings->resolve('courtyard.courtthane');
            $onboardingBefore = $d->f->store->journal->read();
            $callsBefore = $assignment->requests;

            $holder = $formation->appoint();
            $intake = $formation->receive()['intake_id'];
            self::assertNotSame($d->f->root, $formation->root);
            self::assertNotSame($tuple['profile_ref']['digest'], $holder['profile_artifact']['content_digest']);
            $terms = $formation->terms($intake, 'interview');
            $terms['provider'] = $tuple['provider'];
            $terms['model'] = $tuple['model_id'];
            $terms['model_settings'] = $tuple;
            // Legitimate independent formation owner signs its real session;
            // this does not make its different Profile the assessed O4 Profile.
            $session = $formation->grant($intake, 'interview', $terms);
            $sink = new class implements BoundedFormationTransport {
                public array $calls = [];
                public function inspect(array $request, array $terms): array
                { return ['calls'=>1,'input_tokens'=>1,'output_tokens'=>1,'cost_microusd'=>2,'milliseconds'=>1000]; }
                public function invoke(array $claim, array $request, array $terms): array
                {
                    $this->calls[] = ['claim'=>$claim,'request'=>$request,'terms'=>$terms];
                    throw new \RuntimeException('REVIEWER_STOP_AT_OFFLINE_TRANSPORT');
                }
            };
            $runtime = new FormationCognition($formation->journal, $formation->signatures, $formation->personnel,
                new SettingsBoundTransport($settings, $sink, 'courtyard.courtthane'),
                $formation->container->get(ProviderResponseEnvelopeService::class),
                $formation->container->get(FormationSessionLeaseService::class), $formation->clock,
                new ReceivingFormationHandoffService($formation->root, $formation->journal));
            $error = null;
            try { $runtime->call($session, 'reviewer-profile-scope-01'); }
            catch (\RuntimeException|\InvalidArgumentException $e) { $error = $e->getMessage(); }
            fwrite(STDOUT, json_encode(['probe'=>'O4 consumer aggregate/Profile binding',
                'transport_calls'=>count($sink->calls),'settings_profile_digest'=>$tuple['profile_ref']['digest'],
                'executing_profile_digest'=>$holder['profile_artifact']['content_digest'],
                'executing_profile_verified_by_formation'=>true,'separate_temporary_aggregates'=>true,
                'formation_result'=>$error], JSON_THROW_ON_ERROR)."\n");
            self::assertSame($callsBefore, $assignment->requests);
            self::assertSame($onboardingBefore, $d->f->store->journal->read());
            self::assertCount(0, $sink->calls, 'Settings from another aggregate and a different assessed Profile reached the actual FormationCognition transport.');
        } finally { $formation->close(); $assignment->close(); }
    }
}
