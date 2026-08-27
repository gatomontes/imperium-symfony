<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\DecisionIntegrity\DecisionIntegrityRecordStore;
use App\Imperium\Runtime\DecisionIntegrity\DefensibleDecisionRecordContract;
use App\Imperium\Runtime\DecisionIntegrity\DefensibleDecisionRecordValidator;
use App\Imperium\Runtime\DecisionIntegrity\DecisionSurfaceMaterialFactsFingerprint;
use App\Imperium\Runtime\DecisionIntegrity\InstitutionalDecisionSurfaceContract;
use App\Imperium\Runtime\DecisionIntegrity\InstitutionalDecisionSurfaceValidator;
use PHPUnit\Framework\TestCase;

final class DecisionIntegrityValidationAndPersistenceTest extends TestCase
{
    public function testEveryCanonicalSurfaceFieldIsMandatory(): void
    {
        $validator = new InstitutionalDecisionSurfaceValidator();
        foreach (InstitutionalDecisionSurfaceContract::REQUIRED_FIELDS as $field) {
            $surface = $this->surface();
            unset($surface[$field]);
            try {
                $validator->validate($surface);
                self::fail('Missing surface field accepted: '.$field);
            } catch (\RuntimeException $exception) {
                self::assertStringContainsString('DI100_DECISION_SURFACE_FIELD_REQUIRED', $exception->getMessage());
            }
        }
    }

    public function testEveryCanonicalDecisionRecordFieldIsMandatory(): void
    {
        $validator = new DefensibleDecisionRecordValidator();
        foreach (DefensibleDecisionRecordContract::REQUIRED_FIELDS as $field) {
            $record = $this->decision();
            unset($record[$field]);
            try {
                $validator->validate($record);
                self::fail('Missing decision field accepted: '.$field);
            } catch (\RuntimeException $exception) {
                self::assertStringContainsString('DI120_DECISION_RECORD_FIELD_REQUIRED', $exception->getMessage());
            }
        }
    }

    public function testContextFreePromptAndStaleEvidenceFailClosed(): void
    {
        $validator = new InstitutionalDecisionSurfaceValidator();
        $surface = $this->surface();
        $surface['decision_question'] = 'Proceed?';
        $this->expectFailure('DI104_CONTEXT_FREE_DECISION_PROMPT', static fn () => $validator->validate($surface));

        $surface = $this->surface();
        $surface['evidence'][0]['expires_at'] = '2026-08-27T11:59:59+00:00';
        $this->expectFailure('DI103_STALE_EVIDENCE', static fn () => $validator->validate($surface));
    }

    public function testResidualRiskRequiresExplicitAcceptanceByCompetentOwner(): void
    {
        $validator = new DefensibleDecisionRecordValidator();
        $record = $this->decision();
        $record['risks'][0]['residual_risk_owner']['competent_authority'] = false;
        $this->expectFailure('DI131_COMPETENT_RESIDUAL_RISK_OWNER_REQUIRED', static fn () => $validator->validate($record));

        $record = $this->decision();
        $record['risks'][0]['residual_risk_owner'] = null;
        $this->expectFailure('DI131_COMPETENT_RESIDUAL_RISK_OWNER_REQUIRED', static fn () => $validator->validate($record));
    }

    public function testImmutablePersistenceReturnsExactReplayAndRejectsConflictingReuse(): void
    {
        $root = $this->root();
        try {
            $store = new DecisionIntegrityRecordStore($root);
            $surface = $store->putSurface($this->surface());
            self::assertSame($surface, $store->putSurface($this->surface()));
            self::assertSame($surface, $store->readSurface($surface['surface_id']));

            $conflict = $this->surface();
            $conflict['material_consequences'] = 'A conflicting description under the same immutable identity.';
            $conflict['material_facts_fingerprint'] = (new DecisionSurfaceMaterialFactsFingerprint())->fingerprint($conflict);
            $this->expectFailure('PST111_IMMUTABLE_RECORD_CONFLICT', static fn () => $store->putSurface($conflict));

            $decision = $this->decision($surface);
            $sealed = $store->putDecision($decision);
            self::assertSame($sealed, $store->putDecision($decision));
            self::assertSame($sealed, $store->readDecision($sealed['decision_record_id']));

            $decision['rationale'] = 'A conflicting rationale cannot reuse the same canonical decision identity.';
            $this->expectFailure('PST111_IMMUTABLE_RECORD_CONFLICT', static fn () => $store->putDecision($decision));
        } finally {
            $this->remove($root);
        }
    }

    public function testSupersessionRequiresExactPriorDigestAndPreservesHistory(): void
    {
        $root = $this->root();
        try {
            $store = new DecisionIntegrityRecordStore($root);
            $surface = $store->putSurface($this->surface());
            $first = $store->putDecision($this->decision($surface));

            $second = $this->decision($surface);
            $second['decision_record_id'] = 'decision-record-bbbbbbbbbbbbbbbbbbbb';
            $second['authority_lineage'][0]['source'] = $second['decision_record_id'];
            $second['supersession'] = [
                'supersedes' => ['id' => $first['decision_record_id'], 'digest' => $first['record_digest']],
                'reason' => 'A later explicit decision supersedes reliance without rewriting history.',
            ];
            $sealedSecond = $store->putDecision($second);
            self::assertSame($first, $store->readDecision($first['decision_record_id']));
            self::assertSame($first['record_digest'], $sealedSecond['supersession']['supersedes']['digest']);

            $third = $second;
            $third['decision_record_id'] = 'decision-record-cccccccccccccccccccc';
            $third['authority_lineage'][0]['source'] = $third['decision_record_id'];
            $third['supersession']['supersedes']['digest'] = str_repeat('0', 64);
            $this->expectFailure('DI141_SUPERSESSION_LINEAGE_INVALID', static fn () => $store->putDecision($third));
        } finally {
            $this->remove($root);
        }
    }

    private function surface(): array
    {
        $surface = [
            'schema' => InstitutionalDecisionSurfaceContract::SCHEMA,
            'surface_id' => 'decision-surface-aaaaaaaaaaaaaaaaaaaa',
            'instance_id' => 'imperium-test',
            'proceeding_id' => 'proceeding-test-1',
            'source_option_universe' => ['id' => 'option-universe-test', 'digest' => str_repeat('7', 64)],
            'source_presentation_directive' => ['id' => 'presentation-directive-test', 'digest' => str_repeat('8', 64)],
            'decision_owner' => ['actor_id' => 'imperator-test', 'office_or_seat' => 'imperator', 'authority_basis' => 'charter-test', 'accountability_boundary' => 'Exact decision only.'],
            'decision_question' => 'Authorize only the exact bounded personnel commitment described on this surface?',
            'options_presented' => [[
                'option_id' => 'authorize-exact-commitment',
                'plain_language_explanation' => 'Authorize only the named commitment.',
                'material_consequences' => 'Guildhall may accept the exact commitment.',
                'risks' => ['The named Persona becomes reserved.'],
                'costs' => [],
                'external_effects' => [],
                'reversibility' => 'The later lifecycle may lawfully release the reservation.',
                'authority_effect' => 'One single-use Guildhall acceptance authority.',
            ]],
            'unavailable_options' => [],
            'prohibited_options' => [],
            'rejected_options' => [],
            'unexamined_options' => [],
            'material_consequences' => 'Only one exact downstream acceptance may be opened.',
            'risks' => ['Reservation temporarily changes availability.'],
            'reversibility' => 'No continuing authority survives consumption.',
            'recommendation' => ['author' => 'curia.seneschal', 'recommended_option_id' => 'authorize-exact-commitment', 'rationale' => 'The evidence supports this bounded option.'],
            'evidence' => [$this->evidence()],
            'requested_authority' => 'ONE_EXACT_DELEGATE_PERSONNEL_USE_COMMITMENT_ONLY',
            'authority_not_requested' => ['reservation', 'deployment', 'execution'],
            'limitations' => ['No substitution.', 'No continuing authority.'],
            'expires_at' => '2026-08-27T13:00:00+00:00',
            'material_facts_fingerprint' => '',
            'allowed_dispositions' => ['AUTHORIZED', 'REFUSED', 'RETURNED_FOR_REVISION'],
            'authorization_state' => [
                'decision_pending' => true,
                'authority_granted' => false,
                'decision_inferred' => false,
                'non_authorizing_signals' => InstitutionalDecisionSurfaceContract::NON_AUTHORIZING_SIGNALS,
            ],
            'presented_at' => '2026-08-27T12:00:00+00:00',
            'sealed' => true,
        ];
        $surface['material_facts_fingerprint'] = (new DecisionSurfaceMaterialFactsFingerprint())->fingerprint($surface);
        $surface['record_digest'] = hash('sha256', CanonicalJson::encode($surface));

        return $surface;
    }

    private function decision(?array $surface = null): array
    {
        $surface ??= $this->surface();

        $record = [
            'schema' => DefensibleDecisionRecordContract::SCHEMA,
            'decision_record_id' => 'decision-record-aaaaaaaaaaaaaaaaaaaa',
            'instance_id' => 'imperium-test',
            'proceeding_id' => 'proceeding-test-1',
            'source_decision_surface' => ['id' => $surface['surface_id'], 'digest' => $surface['record_digest']],
            'source_requests' => [['id' => 'request-test-1', 'digest' => str_repeat('c', 64)]],
            'prior_decisions' => [],
            'underlying_proceeding_evidence' => [['id' => 'turn-test-1', 'digest' => str_repeat('d', 64)]],
            'decision' => [
                'disposition' => 'AUTHORIZED',
                'decided_scope' => 'The exact presented personnel commitment.',
                'granted_authority' => ['ONE_EXACT_DELEGATE_PERSONNEL_USE_COMMITMENT_ONLY'],
                'denied_authority' => ['deployment', 'execution'],
                'resulting_state' => 'PENDING_GUILDHALL_ACCEPTANCE',
                'everything_remaining_unauthorized' => ['reservation', 'profile derivation', 'deployment', 'execution'],
            ],
            'decision_owner' => ['actor_id' => 'imperator-test', 'office_or_seat' => 'imperator', 'authority_basis' => 'charter-test', 'accountability_boundary' => 'Exact decision only.'],
            'options_considered' => [['option_id' => 'authorize-exact-commitment', 'examined_disposition' => 'SELECTED', 'reason' => 'Evidence supports the bounded commitment.']],
            'risks' => [[
                'identified_risk' => 'Persona availability changes.',
                'proposed_treatment' => 'Bind one exact reservation path.',
                'applied_treatment' => 'Authority remains single-use.',
                'residual_risk' => 'Temporary unavailability remains.',
                'residual_risk_owner' => ['actor_id' => 'imperator-test', 'office_or_seat' => 'imperator', 'authority_basis' => 'charter-test', 'competent_authority' => true],
                'acceptance_disposition' => 'ACCEPTED',
            ]],
            'evidence_relied_on' => [$this->evidence()],
            'rationale' => 'The exact sealed evidence supports this bounded decision and no wider authority.',
            'decided_at' => '2026-08-27T12:10:00+00:00',
            'limitations' => ['No substitution.', 'No continuing authority.'],
            'expires_at' => '2026-08-27T13:00:00+00:00',
            'authority_lineage' => [[
                'authority' => 'ONE_EXACT_DELEGATE_PERSONNEL_USE_COMMITMENT_ONLY',
                'source' => 'decision-record-aaaaaaaaaaaaaaaaaaaa',
                'consumer' => 'guildhall.guildmaster',
                'scope' => 'The exact presented personnel commitment.',
                'limitations' => ['No substitution.'],
                'expires_at' => '2026-08-27T13:00:00+00:00',
                'continuing_authority' => false,
            ]],
            'supersession' => ['supersedes' => null, 'reason' => null],
            'sealed' => true,
        ];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function evidence(): array
    {
        return [
            'artifact_id' => 'evidence-test-1',
            'record_digest' => str_repeat('f', 64),
            'provenance' => 'test-fixture',
            'version' => '1.0.0',
            'relevance' => 'Establishes exact option identity and consequence.',
            'sealed' => true,
            'observed_at' => '2026-08-27T11:00:00+00:00',
            'expires_at' => '2026-08-27T13:00:00+00:00',
        ];
    }

    private function expectFailure(string $error, callable $operation): void
    {
        try {
            $operation();
            self::fail('Expected failure: '.$error);
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString($error, $exception->getMessage());
        }
    }

    private function root(): string
    {
        return sys_get_temp_dir().'/imperium-decision-integrity-'.bin2hex(random_bytes(6));
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
