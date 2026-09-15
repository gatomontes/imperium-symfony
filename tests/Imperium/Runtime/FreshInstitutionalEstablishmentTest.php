<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Citadel\Formation\{FormationFreshEstablishment as P, FormationInstitution, FormationOwnerFrame, FreshInstitutionPackage};
use App\Imperium\Runtime\Bootstrap\OperatorRootPersonnelInstallationService;
use App\Tests\Imperium\Runtime\Support\FreshEstablishmentFixture as F;
use PHPUnit\Framework\TestCase;

final class FreshInstitutionalEstablishmentTest extends TestCase
{
    public function testRealFoundingThenExactNativeEstablishmentAndHistoricalReplay(): void
    {
        $f = new F();
        try {
            $actors = $f->journal->inspect(function (array $frame, FormationOwnerFrame $owner) use ($f): array {
                $result = []; $institution = new FormationInstitution($f->root);
                foreach (FormationInstitution::SEATS as $role => $seat) { $result[$seat] = $institution->actorInOwner($owner, $role); }
                return $result;
            });
            self::assertCount(9, $actors);
            foreach ($actors as $seat => $actor) { self::assertSame($f->store->instance, $actor['instance_id']); self::assertSame($seat, $actor['seat']); }
            self::assertCount(1, $f->journal->read()['state']['onboarding']['bindings']);
            self::assertSame($f->completion['native_package'], FreshInstitutionPackage::verify($f->root, $f->reservation)['completion']);
            $head = $f->head(); $f->setTime($f->terms['expires_at'] + 1);
            self::assertSame($f->completion, $f->protocol->complete(P::reference($f->reservation)));
            self::assertSame($f->completion, $f->protocol->reserve($f->terms, $f->operator, $f->formation));
            self::assertSame($head, $f->head());
            // Installation competence is consumed history; it is not a tenure expiry.
            self::assertSame($actors['laboratorium.alchemist'], (new FormationInstitution($f->root))->actor('laboratorium'));
            $refusal = null;
            try { (new OperatorRootPersonnelInstallationService($f->root))->install($f->package); }
            catch (\RuntimeException $e) { $refusal = $e->getMessage(); }
            self::assertNotNull($refusal); self::assertStringContainsString('FRESH_ROOT_OWNED', $refusal);
        } finally { $f->close(); }
    }

    public function testFreshProcessAndBothPermanentSeatNativeChains(): void
    {
        $f = new F(); $models = [];
        try {
            $output = $f->root.'/fresh-read.out'; $errors = $f->root.'/fresh-read.err';
            $process = proc_open([PHP_BINARY, __DIR__.'/Support/fresh-establishment-reader.php', $f->root],
                [1 => ['file', $output, 'w'], 2 => ['file', $errors, 'w']], $pipes);
            self::assertIsResource($process);
            $deadline = microtime(true) + 30;
            do { $status = proc_get_status($process); if (!$status['running']) { break; } usleep(1000); } while (microtime(true) < $deadline);
            if ($status['running']) { proc_terminate($process); self::fail('Fresh reader exceeded bound'); }
            $closed = proc_close($process); $exit = $status['exitcode'] < 0 ? $closed : $status['exitcode'];
            self::assertSame(0, $exit, (string) file_get_contents($errors));
            $fresh = json_decode((string) file_get_contents($output), true, 32, JSON_THROW_ON_ERROR);
            self::assertCount(9, $fresh['actors']); $rows = [];
            foreach (\App\Imperium\Runtime\Citadel\Formation\FormationProfileDesignation::TARGETS as $seat) {
                $model = new \App\Tests\Imperium\Runtime\Support\FreshDesignationFixture($f, $seat); $models[] = $model;
                $event = $model->service->publish($model->envelope(), $model->m->candidate);
                $terms = ['candidate' => $model->m->candidate, 'scope' => $f->store->citadel, 'seat' => $seat, 'generation' => 1];
                $holder = $seat === 'courtyard.courtthane'
                    ? $f->personnel->appointCourtthane($model->m->candidate, $f->sign('APPOINT_COURTTHANE', $terms))
                    : $f->personnel->appointLocksmith($model->m->candidate, $f->sign('APPOINT_FORMATION_LOCKSMITH', $terms));
                $mapping = $f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner): array =>
                    (new \App\Imperium\Runtime\Citadel\Formation\FreshInstitutionalProfileMapping($f->root, $f->store))->prepareInOwner($owner, $seat));
                $content = \App\Imperium\Runtime\Onboarding\AuthorityAdmission\Policy::content($mapping, 'formation-profile-mapping');
                self::assertSame(\App\Imperium\Runtime\Citadel\Formation\FormationJournal::digest($holder['profile_artifact']),
                    \App\Imperium\Runtime\Citadel\Formation\FormationJournal::digest($content['profile_artifact']));
                self::assertSame($holder['generation'], $content['holder_generation']);
                self::assertSame($model->m->candidate, $model->current()['event']['candidate']);
                $rows[$seat] = ['designation' => $event, 'holder' => $holder, 'mapping' => $mapping,
                    'mapping_admitted' => false, 'assignment_applied' => false];
            }
            $evidence = getenv('PPC7_PUBLIC_EVIDENCE');
            if (is_string($evidence) && $evidence !== '') {
                if (!is_dir($evidence)) { mkdir($evidence, 0700, true); }
                file_put_contents($evidence.'/same-root-sequence.json', json_encode(['fresh_process' => $fresh, 'fresh_process_exit' => $exit,
                    'canonical_root' => realpath($f->root), 'initialization' => $f->initial, 'reservation' => $f->reservation,
                    'completion' => $f->completion, 'downstream' => $rows, 'external_ports' => 'EXPLICITLY_SYNTHETIC'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
                foreach (['var/imperium/citadel/formation', 'var/imperium/operator-root', 'var/imperium/offices'] as $relative) {
                    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($f->root.'/'.$relative, \FilesystemIterator::SKIP_DOTS));
                    foreach ($iterator as $file) {
                        self::assertFalse($file->isLink()); if (!$file->isFile()) { continue; }
                        $destination = $evidence.'/originals/'.$relative.'/'.str_replace('\\', '/', $iterator->getSubPathName());
                        if (!is_dir(dirname($destination))) { mkdir(dirname($destination), 0700, true); }
                        copy($file->getPathname(), $destination);
                    }
                }
            }
        } finally { foreach ($models as $model) { $model->close(); } $f->close(); }
    }
}
