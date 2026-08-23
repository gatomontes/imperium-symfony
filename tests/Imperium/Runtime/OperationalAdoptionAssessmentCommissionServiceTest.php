<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\OperationalAdoptionAssessmentCommissionService;
use PHPUnit\Framework\TestCase;

final class OperationalAdoptionAssessmentCommissionServiceTest extends TestCase
{
    public function testIssuesThreeIndependentNonExercisableCommissions(): void
    {
        $root = sys_get_temp_dir().'/imperium-adoption-assessment-issue-'.bin2hex(random_bytes(5));
        try {
            [$openingId, $seneschalId, $composition] = $this->fixtures($root);
            $service = new OperationalAdoptionAssessmentCommissionService($root);
            $issuance = $service->issue($openingId, $seneschalId, $composition, new \DateTimeImmutable('2026-08-24T02:00:00+00:00'));

            self::assertSame('LEGATE_RESULT_ADOPTION_CURIAL_COMPOSITION_RESOLVED_ASSESSMENT_COMMISSIONS_ISSUED_PENDING_ACCEPTANCE', $issuance['status']);
            self::assertSame(['EVIDENCE_SUFFICIENCY', 'MISSION_OPERATIONAL_FIT', 'RISK_AUTHORITY_REVERSIBILITY'], array_keys($issuance['commissions']));
            self::assertTrue($issuance['curial_composition_resolved']);
            self::assertTrue($issuance['assessment_commissions_issued']);
            self::assertFalse($issuance['assessment_commissions_accepted']);
            self::assertFalse($issuance['assessment_authority']);
            foreach ($issuance['commissions'] as $reference) {
                $commission = json_decode((string) file_get_contents($root.'/var/imperium/operational/legate-result-adoption-assessment-commissions/'.$reference['id'].'.json'), true, 512, JSON_THROW_ON_ERROR);
                self::assertSame('ISSUED_PENDING_CURIALIS_ACCEPTANCE', $commission['status']);
                self::assertTrue($commission['recipient_acceptance_required']);
                self::assertFalse($commission['commission_exercisable']);
                self::assertFalse($commission['assessment_authority']);
                self::assertFalse($commission['execution_authority']);
            }
            self::assertSame($issuance, $service->issue($openingId, $seneschalId, $composition, new \DateTimeImmutable('2026-08-24T03:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testOneCurialisCannotOccupyMultipleJurisdictionsByImplication(): void
    {
        $root = sys_get_temp_dir().'/imperium-adoption-assessment-duplicate-'.bin2hex(random_bytes(5));
        try {
            [$openingId, $seneschalId, $composition] = $this->fixtures($root);
            $composition['MISSION_OPERATIONAL_FIT'] = $composition['EVIDENCE_SUFFICIENCY'];
            $this->expectExceptionMessage('CUR472_CURIAL_COMPOSITION_INVALID');
            (new OperationalAdoptionAssessmentCommissionService($root))->issue($openingId, $seneschalId, $composition, new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    private function fixtures(string $root): array
    {
        $instance = 'imperium-test';
        $seneschalId = 'operational-seat-binding-'.str_repeat('a', 20);
        $seneschal = $this->record(['schema' => 'imperium.operational-seat-binding/v1', 'binding_id' => $seneschalId, 'instance_id' => $instance, 'seat' => 'curia.seneschal', 'manifestation_id' => 'seneschal', 'occupancy_generation' => 1, 'status' => 'ACTIVE', 'binding_atomic' => true, 'sealed' => true]);
        $openingId = 'legate-result-adoption-evaluation-opening-'.str_repeat('b', 20);
        $judgments = [
            ['jurisdiction' => 'EVIDENCE_SUFFICIENCY', 'question' => 'Evidence?'],
            ['jurisdiction' => 'MISSION_OPERATIONAL_FIT', 'question' => 'Fit?'],
            ['jurisdiction' => 'RISK_AUTHORITY_REVERSIBILITY', 'question' => 'Risk?'],
        ];
        $opening = $this->record([
            'schema' => 'imperium.legate-result-adoption-evaluation-opening/v1', 'opening_id' => $openingId, 'instance_id' => $instance,
            'presiding_seneschal' => ['seat' => 'curia.seneschal', 'binding_id' => $seneschalId, 'binding_digest' => $seneschal['record_digest'], 'manifestation_id' => 'seneschal', 'occupancy_generation' => 1],
            'result' => ['output' => 'Recommendation.'], 'contract' => ['task' => 'Recommend.'],
            'evaluation_contract' => ['required_judgments' => $judgments],
            'status' => 'LEGATE_RESULT_ADOPTION_EVALUATION_OPENED_PENDING_CURIAL_COMPOSITION_NO_ASSESSMENT_AUTHORITY',
            'curial_composition_resolved' => false, 'assessment_commissions_issued' => false, 'assessment_authority' => false,
            'result_operationally_adopted' => false, 'execution_authority' => false, 'sealed' => true,
        ]);
        $this->write($root.'/var/imperium/operational/occupancy/'.$seneschalId.'.json', $seneschal);
        $this->write($root.'/var/imperium/operational/legate-result-adoption-evaluation-openings/'.$openingId.'.json', $opening);
        $composition = [];
        foreach (['EVIDENCE_SUFFICIENCY' => 'evidence', 'MISSION_OPERATIONAL_FIT' => 'operations', 'RISK_AUTHORITY_REVERSIBILITY' => 'risk'] as $jurisdiction => $slug) {
            $bindingId = 'operational-seat-binding-'.substr(hash('sha256', $jurisdiction), 0, 20);
            $occupant = $this->record([
                'schema' => 'imperium.operational-seat-binding/v1', 'binding_id' => $bindingId, 'instance_id' => $instance,
                'officer_class' => 'DELEGATE', 'seat' => 'curia.curialis.'.$slug, 'manifestation_id' => 'curialis-'.$slug,
                'occupancy_generation' => 1, 'source_qualification' => ['id' => 'qualification-'.$slug, 'digest' => str_repeat(substr(hash('sha256', $slug), 0, 1), 64)],
                'status' => 'ACTIVE', 'binding_atomic' => true, 'sealed' => true,
            ]);
            $this->write($root.'/var/imperium/operational/occupancy/'.$bindingId.'.json', $occupant);
            $composition[$jurisdiction] = $bindingId;
        }

        return [$openingId, $seneschalId, $composition];
    }

    private function record(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); return $record;
    }

    private function write(string $path, array $record): void
    {
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0770, true);
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->remove($child) : unlink($child); }
        rmdir($path);
    }
}
