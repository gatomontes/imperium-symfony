<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Command\{CitadelPreparationCommand, CitadelPublicInstitutionsCommand};
use App\Imperium\Runtime\Citadel\Formation\{FormationPreparation, FormationInstitution, FormationJournal, UnavailableFormationTransport};
use App\Imperium\Runtime\Clavium\ClaimBoundCredentialBroker;
use App\Imperium\Runtime\LaCortine\CredentialBroker;
use App\Tests\Imperium\Runtime\Support\CitadelFormationFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\{ContainerBuilder, Reference};
use App\Imperium\Runtime\Clock;

final class CitadelReadinessPreparationTest extends TestCase
{
    private CitadelFormationFixture $f;
    protected function setUp(): void { $this->f = new CitadelFormationFixture(); }
    protected function tearDown(): void { $this->f->close(); }

    public function testPreparedBytesAreConsumedByActualGrantAndDefaultTransportStillRefuses(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $terms = $f->terms($id, 'interview');
        $before = $f->journal->read();
        $packet = $this->command('decision', $this->request($terms), 0);
        self::assertSame($before, $f->journal->read());
        self::assertSame([], $f->transport->calls);
        self::assertFalse($packet['activation']);
        $decision = $f->signPrepared($packet);
        $assembled = $this->command('assemble', ['packet' => $packet, 'public_key' => $before['state']['trust']['public_key'], 'signature' => $decision['signature']], 0);
        self::assertSame($decision, $assembled['decision']);
        $this->command('assemble', ['packet' => $packet, 'public_key' => $before['state']['trust']['public_key'], 'signature' => base64_encode(str_repeat('x', 64))], 1);
        $session = $f->run('grant', ['intakeId' => $id, 'phase' => 'interview', 'terms' => $packet['object'], 'decision' => $decision]);
        // Explicitly exercise the refusing production boundary, separate from the synthetic DI fixture.
        try { (new UnavailableFormationTransport())->inspect([], $terms); self::fail('Live adapter must refuse'); }
        catch (\RuntimeException $e) { self::assertStringContainsString('CMF034', $e->getMessage()); }
        self::assertSame([], $f->journal->read()['state']['sessions'][$session]['attempts']);
        $f->transport->response = $f::understanding();
        $f->run('call', ['sessionId' => $session, 'attemptId' => 'prepared-interview-0001']);
        self::assertCount(1, $f->transport->calls);
        self::assertEmpty($f->journal->read()['state']['dossiers'] ?? []);
    }

    #[DataProvider('tampering')]
    public function testExactPreparedSignatureRejectsChangedAuthorityOrDestination(string $field): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint(); $terms = $f->terms($id, 'interview');
        $packet = $this->command('decision', $this->request($terms), 0);
        $decision = $f->signPrepared($packet);
        if ($field === 'destination') { $terms['destination'] = 'other-destination'; }
        elseif ($field === 'model') { $terms['model'] = 'other-model'; }
        else { $decision['payload'][$field] = str_repeat('a', 64); }
        try { $f->run('grant', ['intakeId' => $id, 'phase' => 'interview', 'terms' => $terms, 'decision' => $decision]); self::fail('Tamper accepted'); }
        catch (\RuntimeException $e) { self::assertStringContainsString('CMF022', $e->getMessage()); }
        self::assertSame([], $f->transport->calls);
        self::assertEmpty($f->journal->read()['state']['sessions'] ?? []);
    }
    public static function tampering(): iterable { foreach (['destination', 'model', 'trust_fingerprint', 'citadel_id', 'object_digest'] as $f) { yield $f => [$f]; } }

    public function testPublicInspectionNeverTreatsSyntheticEvidenceAsGenuineAuthority(): void
    {
        $state = $this->f->journal->read();
        $input = ['schema' => 'imperium.citadel-readiness-public/v1', 'installation' => null, 'trust' => $state['state']['trust'],
            'institutions' => [], 'appointments' => [], 'transport' => ['supported' => true]];
        $report = $this->command('inspect', $input, 2);
        self::assertTrue($report['rows']['trust']['byte_consistency']);
        self::assertSame('SUPPLIED_UNVERIFIED_OWNER_EVIDENCE', $report['rows']['trust']['status']);
        self::assertFalse($report['live_ready']);
        self::assertSame($state, $this->f->journal->read());
        $input['trust']['competence'] = 'APPROVE_CANONICAL_MISSION_PLAN';
        self::assertSame('INVALID_OR_EXPIRED_PUBLIC_TRUST', $this->command('inspect', $input, 2)['rows']['trust']['status']);
    }

    public function testPublicExportReadsNativeWitnessesWithoutWritesAndEmptyRootStaysEmpty(): void
    {
        $before = $this->files($this->f->root);
        $tester = new CommandTester(new CitadelPublicInstitutionsCommand(new FormationInstitution($this->f->root)));
        self::assertSame(0, $tester->execute([]));
        self::assertCount(9, json_decode($tester->getDisplay(), true)['institutions']);
        self::assertSame($before, $this->files($this->f->root));
        $empty = $this->f->root.'/empty'; mkdir($empty);
        $tester = new CommandTester(new CitadelPublicInstitutionsCommand(new FormationInstitution($empty)));
        self::assertSame(2, $tester->execute([]));
        self::assertSame([], $this->files($empty));
    }

    public function testFormationClaimCannotAcquireLegacyCredentialCapability(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint(); $session = $f->grant($id, 'interview');
        $f->transport->response = $f::understanding(); $f->run('call', ['sessionId' => $session, 'attemptId' => 'claim-schema-proof-0001']);
        $claim = $f->journal->read()['state']['sessions'][$session]['attempts']['claim-schema-proof-0001']['claim'];
        $credentials = $this->createMock(CredentialBroker::class);
        $credentials->expects(self::never())->method('issue'); $credentials->expects(self::never())->method('consume');
        $broker = new ClaimBoundCredentialBroker($f->root, $credentials);
        $this->expectExceptionMessage('CLV430_CREDENTIAL_GRANT_INVALID');
        $broker->consume($claim, $f->clock->now(), static fn () => throw new \LogicException('Must not reach HTTP'));
    }

    public function testPreparationRefusesExpiryMissingPricingAndActivationInjection(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $request = $this->request($f->terms($id, 'interview'));
        $bad = $request; $bad['expires_at'] = $f->clock->at; $this->command('decision', $bad, 1);
        $bad = $request; $bad['object']['pricing'] = []; $this->command('decision', $bad, 1);
        $bad = $request; $bad['root'] = $f->root; $this->command('decision', $bad, 1);
        $bad = $request; $bad['effect'] = 'ACTIVATE_LIVE_TRANSPORT'; $this->command('decision', $bad, 1);
        self::assertSame([], $f->transport->calls);
    }

    private function command(string $mode, array $input, int $expected): array
    {
        $c = new ContainerBuilder(); $c->register(Clock::class)->setSynthetic(true)->setPublic(true);
        $c->register(FormationPreparation::class)->setAutowired(true);
        $c->register(CitadelPreparationCommand::class)->setAutowired(true)->setPublic(true);
        $c->compile(); $c->set(Clock::class, $this->f->clock);
        $path = $this->f->root.'/public-preparation.json'; file_put_contents($path, json_encode($input));
        $before = $this->files($this->f->root);
        $tester = new CommandTester($c->get(CitadelPreparationCommand::class));
        self::assertSame($expected, $tester->execute(['mode' => $mode, 'public-file' => $path]), $tester->getDisplay());
        self::assertSame($before, $this->files($this->f->root));
        return $expected === 1 ? [] : json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function request(array $terms): array
    {
        $state = $this->f->journal->read()['state'];
        return ['schema' => 'imperium.citadel-preparation-request/v1', 'citadel_id' => $state['citadel_id'],
            'trust_fingerprint' => $state['trust']['fingerprint'], 'effect' => 'AUTHORIZE_INTERVIEW_SESSION', 'object' => $terms,
            'expires_at' => $this->f->clock->at + 3600,
            'source_identity' => ['commit' => str_repeat('a', 40), 'tree' => str_repeat('b', 40), 'public_export_digest' => str_repeat('c', 64)]];
    }

    private function files(string $root): array
    {
        $out = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->isFile()) { $out[$file->getPathname()] = hash_file('sha256', $file->getPathname()); }
        }
        ksort($out); return $out;
    }
}
