<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Authority\AuthorityInput;
use App\Imperium\Runtime\Garrison\{SubordinatePersonaCanonicalAdmissionService, GarrisonInventoryResponseService};
use App\Tests\Imperium\Runtime\Support\CitadelAuthorityFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use App\Tests\Imperium\Runtime\Support\CitadelAuthorityProcess as Process;

final class CitadelAuthorityInterfacesTest extends TestCase
{
    private CitadelAuthorityFixture $f;
    protected function setUp(): void { $this->f = new CitadelAuthorityFixture(); }
    protected function tearDown(): void { $this->f->close(); }

    public function testCommandDiExportAndInspectionKeepPrivateStateAndHistoricalReceiptDistinct(): void
    {
        $this->f->seed(); $path = $this->f->root.'/var/imperium/bootstrap-state.json'; $before = file_get_contents($path);
        [$exit, $projection, $text] = $this->f->command('recruiter-export', 'synthetic-authority-test');
        self::assertSame(2, $exit); self::assertStringNotContainsString($this->f::SECRET, $text);
        self::assertNull($projection['source_original_digest']); self::assertFalse($projection['authenticated_provenance']);
        self::assertSame(10, $projection['source']['state_generation']); self::assertSame(2, $projection['source']['successor']['occupancy_generation']);
        self::assertFileExists($this->f->root.'/var/imperium/bootstrap.lock'); self::assertSame($before, file_get_contents($path));
        [$exit, $report] = $this->f->command('recruiter-inspect', json_decode(CanonicalJson::encode($projection), true));
        self::assertSame(2, $exit); self::assertFalse($report['currentness_verified']);
        [$exit, $report] = $this->f->command('recruiter-compare', ['projection' => $projection, 'instance_id' => 'synthetic-authority-test']);
        self::assertSame(2, $exit); self::assertTrue($report['same_at_local_snapshot']); self::assertFalse($report['currentness_verified']);
        self::assertSame($before, file_get_contents($path)); self::assertSame([], glob($path.'.tmp.*'));
    }

    #[DataProvider('badSource')]
    public function testSourceAmbiguitiesFailWithoutDisclosure(string $case): void
    {
        $s = $this->f::state();
        switch ($case) {
            case 'missing': array_splice($s['events'], 1, 1); break;
            case 'failed': $s['events'][1]['result'] = 'FAILED'; break;
            case 'duplicate': $s['events'][] = $s['events'][1]; $s['events'][3]['generation'] = $s['generation'] = 11; break;
            case 'wrong-instance': $s['binding']['instance_id'] = 'another-instance'; break;
            case 'wrong-seat': $s['events'][1]['output']['successor']['seat'] = 'garrison.constable'; break;
            case 'wrong-generation': $s['events'][1]['output']['successor']['occupancy_generation'] = 3; break;
            case 'wrong-predecessor': $s['events'][1]['output']['successor']['predecessor'] = 'other'; break;
            case 'wrong-qualification': $s['events'][1]['output']['qualification_packet_digest'] = str_repeat('0', 64); break;
            case 'unconsumed': $s['events'][1]['output']['commission']['consumed'] = false; break;
            case 'retired': $s['state'] = 'RETIRED'; break;
            case 'superseded': $s['events'][1]['output']['successor']['authority'] = 'superseded'; break;
            case 'private-output': $s['events'][1]['output']['private'] = $this->f::SECRET; break;
            case 'wrong-version': $s['schema'] = 'imperium.bootstrap-state/v0-root'; break;
            case 'revision': $s['generation'] = 999; break;
        }
        $this->f->seed($s); [$exit, $result, $text] = $this->f->command('recruiter-export', 'synthetic-authority-test');
        self::assertSame(1, $exit); self::assertSame('REFUSED', $result['disposition']); self::assertStringNotContainsString($this->f::SECRET, $text);
        self::assertFalse($result['execution_authority']);
    }
    public static function badSource(): iterable
    {
        foreach (['missing','failed','duplicate','wrong-instance','wrong-seat','wrong-generation','wrong-predecessor','wrong-qualification','unconsumed','retired','superseded','private-output','wrong-version','revision'] as $case) { yield $case => [$case]; }
    }

    public function testStaleProjectionAndBorrowedProvenanceRefuseEvenWhenResealed(): void
    {
        $this->f->seed(); $p = $this->f->recruiter()->export('synthetic-authority-test');
        $s = $this->f::state(); $s['generation'] = 11; $s['events'][] = ['transition' => 'LATER', 'result' => 'SUCCESS', 'generation' => 11]; $this->f->seed($s);
        [$exit, $result] = $this->f->command('recruiter-compare', ['projection' => $p, 'instance_id' => 'synthetic-authority-test']);
        self::assertSame(1, $exit); self::assertSame('CAI022_STALE_SOURCE_PROJECTION', $result['code']);
        foreach (['authenticated_provenance', 'source_original_digest', 'source_projection_digest', 'schema', 'execution_authority'] as $field) {
            $bad = $p; $bad[$field] = match ($field) {'schema' => 'unsupported/v2', 'authenticated_provenance', 'execution_authority' => true, default => str_repeat('e', 64)};
            unset($bad['record_digest']); $bad = AuthorityInput::seal($bad);
            self::assertSame(1, $this->f->command('recruiter-inspect', $bad)[0]);
        }
        $forged = $p; $forged['observed_at'] += 1; unset($forged['record_digest']); $forged = AuthorityInput::seal($forged);
        // A self-consistent forged observation is only structural, never authenticated/current.
        [$exit, $result] = $this->f->command('recruiter-inspect', $forged);
        self::assertSame(2, $exit); self::assertFalse($result['authenticated_provenance']); self::assertFalse($result['currentness_verified']);
    }

    public function testRequestPreservesExistingPowersAndNeverAcceptsAnIncomingKeyAsIssuer(): void
    {
        $input = $this->f->requestInput(); [$exit, $request] = $this->f->command('garrison-request', $input);
        self::assertSame(2, $exit); self::assertNull($request['issuer']); self::assertNull($request['terms']['prior_revision']);
        self::assertSame(1, $request['terms']['occupancy_generation']); self::assertTrue($request['terms']['observed_existing_powers']['inventory_response_authority']);
        self::assertNull($request['terms']['observed_existing_powers']['persona_reservation_disposition_authority']);
        self::assertCount(2, $request['terms']['requested_extension']);
        self::assertSame($request, $this->f->command('garrison-request', $input)[1]);
        $keypair = sodium_crypto_sign_keypair(); $key = sodium_crypto_sign_publickey($keypair);
        $signature = sodium_crypto_sign_detached(CanonicalJson::encode($request), sodium_crypto_sign_secretkey($keypair));
        foreach (['CITADEL_MISSION_FORMATION', 'APPROVE_CANONICAL_MISSION_PLAN', 'FORGED_NATIVE_GARRISON_REVISION'] as $competence) {
            $verification = ['request' => $request, 'occupancy' => $input['occupancy'], 'decision' => ['public_key' => base64_encode($key),
                'signature' => base64_encode($signature), 'competence' => $competence, 'revoked' => false]];
            [$exit, $result] = $this->f->command('garrison-verify', $verification);
            self::assertSame(2, $exit); self::assertFalse($result['decision_accepted']); self::assertFalse($result['revision_written']);
            try { $this->f->garrison()->resolveForAdmission($verification); self::fail('Forged issuer accepted'); }
            catch (\RuntimeException $e) { self::assertSame('CAI036_AUTHENTIC_NATIVE_REVISION_RESOLVER_UNAVAILABLE', $e->getMessage()); }
        }
        self::assertDirectoryDoesNotExist($this->f->root.'/var/imperium/offices/garrison');
    }

    public function testChangedPredecessorOverbroadTermsStaleRevisionAndExpiryCannotGainAuthority(): void
    {
        $i = $this->f->requestInput(); $r = $this->f->garrison()->prepare($i);
        foreach (['instance_id', 'manifestation_id', 'occupancy_generation', 'prior_revision', 'requested_extension'] as $field) {
            $bad = $r; $bad['terms'][$field] = match ($field) {'occupancy_generation' => 2, 'prior_revision' => ['id' => 'competing', 'digest' => str_repeat('c', 64)], 'requested_extension' => ['execution_authority' => true], default => 'wrong-actor'};
            unset($bad['record_digest']); $bad = AuthorityInput::seal($bad);
            self::assertSame(1, $this->f->command('garrison-inspect', ['request' => $bad, 'occupancy' => $i['occupancy']])[0]);
        }
        $changed = $i['occupancy']; $changed['inventory_response_authority'] = false; unset($changed['record_digest']); $changed = AuthorityInput::seal($changed);
        self::assertSame(1, $this->f->command('garrison-inspect', ['request' => $r, 'occupancy' => $changed])[0]);
        $this->f->now += 60;
        [$exit, $result] = $this->f->command('garrison-verify', ['request' => $r, 'occupancy' => $i['occupancy'], 'decision' => ['revoked' => true]]);
        self::assertSame(2, $exit); self::assertSame('EXPIRED', $result['time_status']); self::assertFalse($result['decision_accepted']);
    }

    public function testActualAdmissionCannotConsumeRequestWhileInventoryRetainsOriginalPower(): void
    {
        $o = $this->f::occupancy(); $dir = $this->f->root.'/var/imperium/offices/garrison';
        mkdir($dir.'/occupancy', 0770, true); $path = $dir.'/occupancy/'.$o['binding_id'].'.json';
        file_put_contents($path, json_encode($o, JSON_THROW_ON_ERROR)); $original = file_get_contents($path);
        $id = 'guildhall-garrison-persona-admission-delivery-'.str_repeat('c', 20);
        mkdir($dir.'/inbox/canonical-subordinate-persona-admissions', 0770, true);
        $delivery = AuthorityInput::seal(['route_class' => 'CANONICAL_GUILDHALL_TO_GARRISON', 'status' => 'DELIVERED_PENDING_CONSTABLE_ADMISSION_DISPOSITION',
            'recipient' => ['seat' => 'garrison.constable'], 'instance_id' => $o['instance_id']]);
        file_put_contents($dir.'/inbox/canonical-subordinate-persona-admissions/'.$id.'.json', json_encode($delivery, JSON_THROW_ON_ERROR));
        $request = $this->f->garrison()->prepare($this->f->requestInput());
        for ($replay = 0; $replay < 2; ++$replay) {
            $this->f->garrison()->verifyRevision(['request' => $request, 'occupancy' => $o, 'decision' => ['forged' => true]]);
            try { (new SubordinatePersonaCanonicalAdmissionService($this->f->root))->admit($id, $o['binding_id']); self::fail('Old occupancy admitted'); }
            catch (\RuntimeException $e) { self::assertSame('GA87_CANONICAL_ADMISSION_CHAIN_INVALID', $e->getMessage()); }
        }
        self::assertSame($original, file_get_contents($path)); self::assertCount(1, glob($dir.'/occupancy/*.json'));
        self::assertDirectoryDoesNotExist($dir.'/custody'); self::assertDirectoryDoesNotExist($dir.'/subordinate-persona-admission-dispositions');
        $inquiryId = 'garrison-inquiry-'.str_repeat('d', 20);
        $inquiry = AuthorityInput::seal(['inquiry_id' => $inquiryId, 'status' => 'CONSTABLE_ACTIVATION_REQUIRED', 'instance_id' => $o['instance_id'],
            'proceeding_id' => 'synthetic-proceeding', 'requester' => ['seat' => 'guildhall.guildmaster'], 'inventory_questions' => [], 'requested_facts' => []]);
        file_put_contents($dir.'/inbox/'.$inquiryId.'.json', json_encode($inquiry, JSON_THROW_ON_ERROR));
        $response = (new GarrisonInventoryResponseService($this->f->root))->respond($inquiryId);
        self::assertTrue($response['authoritative_inventory_response']); self::assertSame($o['record_digest'], $response['responder']['occupancy_digest']);
        self::assertSame($original, file_get_contents($path)); self::assertFalse($response['execution_authority']);
        // Putting a preparation artifact in the old occupancy slot cannot become a new format bypass.
        file_put_contents($path, json_encode($request, JSON_THROW_ON_ERROR));
        try { (new SubordinatePersonaCanonicalAdmissionService($this->f->root))->admit($id, $o['binding_id']); self::fail('Request treated as occupancy'); }
        catch (\RuntimeException $e) { self::assertSame('GA87_CANONICAL_ADMISSION_CHAIN_INVALID', $e->getMessage()); }
        try { (new GarrisonInventoryResponseService($this->f->root))->respond($inquiryId); self::fail('Request treated as inventory authority'); }
        catch (\RuntimeException $e) { self::assertStringStartsWith('GA64_', $e->getMessage()); }
        file_put_contents($path, $original);
        self::assertCount(1, glob($dir.'/occupancy/*.json')); self::assertDirectoryDoesNotExist($dir.'/custody');
    }

    public function testMalformedPrivateStateDuplicateKeysAndLimitsDoNotLeakOrLeaveTemporaryFiles(): void
    {
        $this->f->seed(); $p = $this->f->root.'/var/imperium/bootstrap-state.json';
        foreach (['{"secret":"'.$this->f::SECRET.'",', '{"schema":"first","schema":"'.$this->f::SECRET.'"}', str_repeat(' ', 1048577)] as $bad) {
            file_put_contents($p, $bad); [$exit, $result, $text] = $this->f->command('recruiter-export', 'synthetic-authority-test');
            self::assertSame(1, $exit); self::assertStringNotContainsString($this->f::SECRET, $text); self::assertSame($bad, file_get_contents($p));
            self::assertSame([], glob($p.'.tmp.*'));
        }
        self::assertSame(1, $this->f->command('unsupported-mode', $this->f::SECRET)[0]);
        self::assertSame(1, $this->f->command('recruiter-inspect', $this->f->root.'/'.$this->f::SECRET)[0]);
    }

    public function testExporterSerializesWithActualStateStoreWriterAndRecoversAfterWriterInterruption(): void
    {
        $this->f->seed(); $worker = __DIR__.'/Support/citadel-authority-worker.php';
        $process = fn (string $mode): Process => new Process([PHP_BINARY, '-d', 'allow_url_fopen=0', '-d',
            'disable_functions=curl_exec,curl_multi_exec,fsockopen,pfsockopen,stream_socket_client,socket_connect', $worker, $mode, $this->f->root]);
        $writer = $process('writer'); $exporter = $process('export');
        try {
            $writer->start(); $this->awaitMarker('writer-locked', $writer);
            $exporter->start(); $this->awaitMarker('export-started', $exporter);
            usleep(150000); self::assertTrue($exporter->isRunning()); self::assertSame('', $exporter->getOutput());
            file_put_contents($this->f->root.'/release-writer', 'synthetic');
            self::assertSame(0, $writer->wait()); self::assertSame(0, $exporter->wait());
            $result = json_decode($exporter->getOutput(), true, 48, JSON_THROW_ON_ERROR);
            self::assertSame(11, $result['source']['state_generation']); self::assertFalse($result['authenticated_provenance']);
            self::assertStringNotContainsString($this->f::SECRET, $exporter->getOutput().$exporter->getErrorOutput());
        } finally { $writer->stop(0); $exporter->stop(0); }
        // These are exact fixture marker files under the verified generated root.
        unlink($this->f->root.'/release-writer'); unlink($this->f->root.'/writer-locked');
        $original = file_get_contents($this->f->root.'/var/imperium/bootstrap-state.json');
        $writer = $process('writer');
        try { $writer->start(); $this->awaitMarker('writer-locked', $writer); $writer->stop(0); }
        finally { $writer->stop(0); }
        $result = $this->f->recruiter()->export('synthetic-authority-test');
        self::assertSame(11, $result['source']['state_generation']); self::assertSame($original, file_get_contents($this->f->root.'/var/imperium/bootstrap-state.json'));
        self::assertSame([], glob($this->f->root.'/var/imperium/bootstrap-state.json.tmp.*'));
    }

    private function awaitMarker(string $name, Process $process): void
    {
        $until = microtime(true) + 8;
        while (!is_file($this->f->root.'/'.$name)) {
            if (!$process->isRunning() || microtime(true) > $until) { self::fail('Synthetic process did not reach marker: '.$process->getErrorOutput()); }
            usleep(10000);
        }
    }
}
