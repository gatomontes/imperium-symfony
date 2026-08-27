<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\DecisionIntegrity\DecisionSurfaceMaterialFactsFingerprint;
use PHPUnit\Framework\TestCase;

final class DecisionSurfaceMaterialFactsFingerprintTest extends TestCase
{
    public function testEveryDeclaredMaterialDimensionInvalidatesPriorConsent(): void
    {
        $fingerprint = new DecisionSurfaceMaterialFactsFingerprint();
        $prior = $this->surface();
        $changes = [
            'options' => static function (array &$surface): void { $surface['options_presented'][] = ['option_id' => 'refuse'] + $surface['options_presented'][0]; },
            'consequences' => static function (array &$surface): void { $surface['material_consequences'] = 'Changed consequence.'; },
            'evidence' => static function (array &$surface): void { $surface['evidence'][0]['record_digest'] = str_repeat('b', 64); },
            'risk' => static function (array &$surface): void { $surface['risks'] = ['Changed risk.']; },
            'scope' => static function (array &$surface): void { $surface['decision_question'] = 'Changed exact decision scope?'; },
            'limitations' => static function (array &$surface): void { $surface['limitations'] = ['Changed limitation.']; },
            'expiry' => static function (array &$surface): void { $surface['expires_at'] = '2026-08-28T14:00:00+00:00'; },
            'recipient' => static function (array &$surface): void { $surface['decision_owner']['actor_id'] = 'imperator-replacement'; },
            'requested_authority' => static function (array &$surface): void { $surface['requested_authority'] = 'CHANGED_AUTHORITY'; },
        ];

        foreach ($changes as $category => $change) {
            $current = $prior;
            $change($current);
            self::assertNotSame($fingerprint->fingerprint($prior), $fingerprint->fingerprint($current), $category);
            self::assertContains($category, $fingerprint->changedCategories($prior, $current), $category);
        }
    }

    public function testStorageIdentityAndPresentationTimeAreNotMaterialFacts(): void
    {
        $fingerprint = new DecisionSurfaceMaterialFactsFingerprint();
        $prior = $this->surface();
        $current = $prior + [
            'surface_id' => 'decision-surface-current',
            'source_option_universe' => ['id' => 'universe-current', 'digest' => str_repeat('c', 64)],
            'source_presentation_directive' => ['id' => 'directive-current', 'digest' => str_repeat('d', 64)],
            'presented_at' => '2026-08-27T12:30:00+00:00',
        ];

        self::assertSame($fingerprint->fingerprint($prior), $fingerprint->fingerprint($current));
        self::assertSame([], $fingerprint->changedCategories($prior, $current));
    }

    private function surface(): array
    {
        $option = [
            'option_id' => 'authorize',
            'plain_language_explanation' => 'Authorize exactly one commitment.',
            'material_consequences' => 'Opens one acceptance.',
            'risks' => ['Availability may change.'],
            'costs' => [],
            'external_effects' => [],
            'reversibility' => 'Single use.',
            'authority_effect' => 'One bounded authority.',
        ];

        return [
            'decision_owner' => ['actor_id' => 'imperator', 'office_or_seat' => 'imperator', 'authority_basis' => 'charter', 'accountability_boundary' => 'Exact decision.'],
            'decision_question' => 'Authorize exactly one bounded commitment?',
            'options_presented' => [$option],
            'unavailable_options' => [],
            'prohibited_options' => [],
            'rejected_options' => [],
            'unexamined_options' => [],
            'material_consequences' => 'Opens one acceptance.',
            'risks' => ['Availability may change.'],
            'reversibility' => 'Single use.',
            'recommendation' => ['author' => 'curia.seneschal', 'recommended_option_id' => 'authorize', 'rationale' => 'Exact evidence supports presentation.'],
            'evidence' => [['artifact_id' => 'evidence', 'record_digest' => str_repeat('a', 64)]],
            'requested_authority' => 'ONE_EXACT_COMMITMENT',
            'authority_not_requested' => ['execution'],
            'limitations' => ['No substitution.'],
            'expires_at' => '2026-08-27T14:00:00+00:00',
            'allowed_dispositions' => ['AUTHORIZED', 'REFUSED'],
        ];
    }
}
