<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\DecisionIntegrity\DecisionIntegrityRecordStore;
use App\Imperium\Runtime\DecisionIntegrity\DecisionSurfaceAssembler;
use App\Imperium\Runtime\DecisionIntegrity\DecisionSurfaceMaterialChangeDetector;
use App\Imperium\Runtime\DecisionIntegrity\DecisionSurfaceMaterialFactsFingerprint;
use App\Imperium\Runtime\DecisionIntegrity\DecisionSurfaceOptionUniverseContract;
use App\Imperium\Runtime\DecisionIntegrity\DecisionSurfacePresentationDirectiveContract;
use App\Imperium\Runtime\DecisionIntegrity\InstitutionalDecisionSurfaceContract;
use PHPUnit\Framework\TestCase;

final class DecisionSurfaceAssemblerTest extends TestCase
{
    public function testAssemblerPreservesCuriaAuthorshipAndSeparatesEveryMaterialAlternative(): void
    {
        $root = $this->root();
        try {
            $universe = $this->universe();
            $directive = $this->directive($universe);
            $surface = (new DecisionSurfaceAssembler(new DecisionIntegrityRecordStore($root)))->assemble($universe, $directive, new \DateTimeImmutable('2026-08-27T12:00:00+00:00'));

            self::assertSame($directive['decision_question'], $surface['decision_question']);
            self::assertSame($directive['recommendation'], $surface['recommendation']);
            self::assertSame(['option-authorize'], array_column($surface['options_presented'], 'option_id'));
            self::assertSame(['option-unavailable'], array_column($surface['unavailable_options'], 'option_id'));
            self::assertSame(['option-prohibited'], array_column($surface['prohibited_options'], 'option_id'));
            self::assertSame(['option-rejected'], array_column($surface['rejected_options'], 'option_id'));
            self::assertSame(['option-unexamined'], array_column($surface['unexamined_options'], 'option_id'));
            self::assertSame($universe['options'][0]['plain_language_explanation'], $surface['options_presented'][0]['plain_language_explanation']);
            self::assertTrue($surface['authorization_state']['decision_pending']);
            self::assertFalse($surface['authorization_state']['authority_granted']);
            self::assertFalse($surface['authorization_state']['decision_inferred']);
            self::assertSame(InstitutionalDecisionSurfaceContract::NON_AUTHORIZING_SIGNALS, $surface['authorization_state']['non_authorizing_signals']);
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $surface['material_facts_fingerprint']);
            self::assertTrue($surface['sealed']);
            self::assertFileExists($root.'/var/imperium/decision-integrity/option-universes/'.$surface['source_option_universe']['id'].'.json');
            self::assertFileExists($root.'/var/imperium/decision-integrity/presentation-directives/'.$surface['source_presentation_directive']['id'].'.json');
        } finally {
            $this->remove($root);
        }
    }

    public function testMateriallyRelevantOptionOmittedFromEveryCategoryFailsStopped(): void
    {
        $root = $this->root();
        try {
            $universe = $this->universe();
            $directive = $this->directive($universe);
            $directive['unexamined_option_ids'] = [];
            $directive = $this->seal($directive);

            $this->expectFailure('DI155_MATERIAL_OPTION_OMITTED', fn () => (new DecisionSurfaceAssembler(new DecisionIntegrityRecordStore($root)))->assemble($universe, $directive, new \DateTimeImmutable('2026-08-27T12:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testDuplicateOrFalseAvailabilityClassificationFailsStopped(): void
    {
        $root = $this->root();
        try {
            $universe = $this->universe();
            $directive = $this->directive($universe);
            $directive['rejected_option_ids'][] = 'option-authorize';
            $directive = $this->seal($directive);
            $this->expectFailure('DI154_OPTION_CLASSIFICATION_INVALID', fn () => (new DecisionSurfaceAssembler(new DecisionIntegrityRecordStore($root)))->assemble($universe, $directive, new \DateTimeImmutable('2026-08-27T12:00:00+00:00')));

            $directive = $this->directive($universe);
            $directive['unavailable_option_ids'] = ['option-rejected'];
            $directive['rejected_option_ids'] = ['option-unavailable'];
            $directive = $this->seal($directive);
            $this->expectFailure('DI154_OPTION_CLASSIFICATION_INVALID', fn () => (new DecisionSurfaceAssembler(new DecisionIntegrityRecordStore($root)))->assemble($universe, $directive, new \DateTimeImmutable('2026-08-27T12:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testChangedOrUnsealedSourceCannotBeAssembledOrReinterpreted(): void
    {
        $root = $this->root();
        try {
            $universe = $this->universe();
            $directive = $this->directive($universe);
            $universe['options'][0]['plain_language_explanation'] = 'Tampered explanation.';
            $this->expectFailure('DI150_OPTION_UNIVERSE_INVALID', fn () => (new DecisionSurfaceAssembler(new DecisionIntegrityRecordStore($root)))->assemble($universe, $directive, new \DateTimeImmutable('2026-08-27T12:00:00+00:00')));

            $universe = $this->universe();
            $directive = $this->directive($universe);
            $directive['sealed'] = false;
            $directive = $this->seal($directive);
            $this->expectFailure('DI151_PRESENTATION_DIRECTIVE_INVALID', fn () => (new DecisionSurfaceAssembler(new DecisionIntegrityRecordStore($root)))->assemble($universe, $directive, new \DateTimeImmutable('2026-08-27T12:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testIdenticalAssemblyReplaysExactSurfaceWithoutCreatingAuthority(): void
    {
        $root = $this->root();
        try {
            $universe = $this->universe();
            $directive = $this->directive($universe);
            $assembler = new DecisionSurfaceAssembler(new DecisionIntegrityRecordStore($root));
            $at = new \DateTimeImmutable('2026-08-27T12:00:00+00:00');
            $first = $assembler->assemble($universe, $directive, $at);
            $replay = $assembler->assemble($universe, $directive, $at);

            self::assertSame($first, $replay);
            self::assertFalse($replay['authorization_state']['authority_granted']);
            self::assertFalse($replay['authorization_state']['decision_inferred']);
        } finally {
            $this->remove($root);
        }
    }

    public function testMaterialChangeMakesPriorConsentStaleWithoutContinuationAuthority(): void
    {
        $root = $this->root();
        try {
            $universe = $this->universe();
            $directive = $this->directive($universe);
            $prior = (new DecisionSurfaceAssembler(new DecisionIntegrityRecordStore($root)))->assemble($universe, $directive, new \DateTimeImmutable('2026-08-27T12:00:00+00:00'));
            $current = $prior;
            unset($current['record_digest']);
            $current['surface_id'] = 'decision-surface-bbbbbbbbbbbbbbbbbbbb';
            $current['material_consequences'] = 'The exact downstream consequence has materially changed.';
            $current['material_facts_fingerprint'] = (new DecisionSurfaceMaterialFactsFingerprint())->fingerprint($current);
            $current = $this->seal($current);

            $assessment = (new DecisionSurfaceMaterialChangeDetector())->assess($prior, $current);

            self::assertSame('FRESH_DECISION_SURFACE_REQUIRED', $assessment['status']);
            self::assertSame(['consequences'], $assessment['changed_material_fact_categories']);
            self::assertTrue($assessment['prior_consent_stale']);
            self::assertTrue($assessment['fresh_decision_surface_required']);
            self::assertFalse($assessment['authority_granted']);
            self::assertFalse($assessment['continuation_authority']);
        } finally {
            $this->remove($root);
        }
    }

    private function universe(): array
    {
        $evidence = $this->evidence();
        $record = [
            'schema' => DecisionSurfaceOptionUniverseContract::SCHEMA,
            'universe_id' => 'option-universe-aaaaaaaaaaaaaaaaaaaa',
            'instance_id' => 'imperium-test',
            'proceeding_id' => 'proceeding-test-1',
            'options' => [
                $this->option('option-authorize', 'AVAILABLE', $evidence['artifact_id']),
                $this->option('option-unavailable', 'UNAVAILABLE', $evidence['artifact_id']),
                $this->option('option-prohibited', 'PROHIBITED', $evidence['artifact_id']),
                $this->option('option-rejected', 'AVAILABLE', $evidence['artifact_id']),
                $this->option('option-unexamined', 'AVAILABLE', $evidence['artifact_id']),
            ],
            'evidence' => [$evidence],
            'sealed' => true,
        ];

        return $this->seal($record);
    }

    private function directive(array $universe): array
    {
        $record = [
            'schema' => DecisionSurfacePresentationDirectiveContract::SCHEMA,
            'directive_id' => 'presentation-directive-aaaaaaaaaaaaaaaaaaaa',
            'instance_id' => 'imperium-test',
            'proceeding_id' => 'proceeding-test-1',
            'source_option_universe' => ['id' => $universe['universe_id'], 'digest' => $universe['record_digest']],
            'decision_owner' => ['actor_id' => 'imperator-test', 'office_or_seat' => 'imperator', 'authority_basis' => 'charter-test', 'accountability_boundary' => 'Exact decision only.'],
            'decision_question' => 'Authorize only the exact bounded personnel commitment described on this surface?',
            'presented_option_ids' => ['option-authorize'],
            'unavailable_option_ids' => ['option-unavailable'],
            'prohibited_option_ids' => ['option-prohibited'],
            'rejected_option_ids' => ['option-rejected'],
            'unexamined_option_ids' => ['option-unexamined'],
            'material_consequences' => 'Only one exact downstream acceptance may be opened.',
            'risks' => ['Reservation temporarily changes availability.'],
            'reversibility' => 'No continuing authority survives consumption.',
            'recommendation' => ['author' => 'curia.seneschal', 'recommended_option_id' => 'option-authorize', 'rationale' => 'The exact evidence supports presenting this bounded option.'],
            'evidence' => [$this->evidence()],
            'requested_authority' => 'ONE_EXACT_DELEGATE_PERSONNEL_USE_COMMITMENT_ONLY',
            'authority_not_requested' => ['reservation', 'deployment', 'execution'],
            'limitations' => ['No substitution.', 'No continuing authority.'],
            'expires_at' => '2026-08-27T13:00:00+00:00',
            'allowed_dispositions' => ['AUTHORIZED', 'REFUSED', 'RETURNED_FOR_REVISION'],
            'authored_at' => '2026-08-27T11:50:00+00:00',
            'sealed' => true,
        ];

        return $this->seal($record);
    }

    private function option(string $id, string $availability, string $evidenceId): array
    {
        return [
            'option_id' => $id,
            'materially_relevant' => true,
            'availability' => $availability,
            'classification_reason' => 'Curia authored the exact classification from sealed evidence.',
            'plain_language_explanation' => 'Curia authored explanation for '.$id.'.',
            'material_consequences' => 'Material consequence for '.$id.'.',
            'risks' => ['Bounded test risk.'],
            'costs' => [],
            'external_effects' => [],
            'reversibility' => 'Reversibility is explicitly disclosed.',
            'authority_effect' => 'Authority effect is explicitly bounded.',
            'evidence' => [$evidenceId],
        ];
    }

    private function evidence(): array
    {
        return [
            'artifact_id' => 'evidence-test-1',
            'record_digest' => str_repeat('f', 64),
            'provenance' => 'test-fixture',
            'version' => '1.0.0',
            'relevance' => 'Establishes exact option identity and classification.',
            'sealed' => true,
            'observed_at' => '2026-08-27T11:00:00+00:00',
            'expires_at' => '2026-08-27T13:00:00+00:00',
        ];
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
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
        return sys_get_temp_dir().'/imperium-decision-surface-assembly-'.bin2hex(random_bytes(6));
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
