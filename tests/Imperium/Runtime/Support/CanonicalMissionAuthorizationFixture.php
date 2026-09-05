<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime\Support;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Mission\CanonicalMissionPlan;

final class CanonicalMissionAuthorizationFixture
{
    public static function persist(string $root, array $overrides = []): array
    {
        $mission = array_replace_recursive(self::mission(), $overrides['mission'] ?? []);
        $plan = ['canonical_mission' => $mission];
        $dossierId = 'curia-planning-dossier-'.str_repeat('a', 20);
        $line = ['line_number' => 1, 'section' => 'mission_plan.canonical_mission', 'text' => 'Exact canonical mission object.', 'source' => 'source_plan'];
        $line['line_digest'] = hash('sha256', CanonicalJson::encode($line));
        $reviewAuthorityId = 'imperator-planning-dossier-review-authority-'.str_repeat('b', 20);
        $dossier = self::seal([
            'schema' => 'imperium.curia-planning-dossier/v1',
            'dossier_id' => $dossierId,
            'dossier_version' => 1,
            'instance_id' => 'imperium-test',
            'source_plan' => ['proceeding_id' => 'proceeding-canonical-mission', 'instance_id' => 'imperium-test', 'turn_sequence' => 1, 'turn_digest' => str_repeat('1', 64)],
            'mission_plan' => $plan,
            'lines' => [$line],
            'line_count' => 1,
            'imperator_review_authority' => ['authority_id' => $reviewAuthorityId, 'authority_single_use' => true, 'review_authority' => true],
            'status' => 'CURIA_PLANNING_DOSSIER_SEALED_PENDING_IMPERATOR_REVIEW',
            'sealed' => true,
        ]);
        $reviewId = 'imperator-planning-dossier-review-'.str_repeat('c', 20);
        $derivationAuthorityId = 'mission-authorization-derivation-authority-'.str_repeat('d', 20);
        $review = self::seal([
            'schema' => 'imperium.imperator-planning-dossier-review/v1',
            'review_id' => $reviewId,
            'dossier' => ['id' => $dossierId, 'version' => 1, 'digest' => $dossier['record_digest'], 'line_count' => 1],
            'actor' => $overrides['actor'] ?? ['kind' => 'imperator', 'id' => 'imperator-development-root'],
            'disposition' => $overrides['disposition'] ?? 'APPROVE_DOSSIER',
            'all_lines_acknowledged' => $overrides['acknowledged'] ?? true,
            'reviewed_at' => '2026-09-04T12:00:00+00:00',
            'mission_authorization_derivation_authority' => [
                'authority_id' => $derivationAuthorityId,
                'authority_single_use' => true,
                'dossier' => ['id' => $dossierId, 'digest' => $dossier['record_digest']],
                'derivation_authority' => true,
                'execution_authority' => false,
                'status' => 'OPEN_PENDING_MISSION_AUTHORIZATION_DERIVATION',
            ],
            'dossier_approval' => $overrides['approved'] ?? true,
            'status' => 'IMPERATOR_PLANNING_DOSSIER_APPROVED_PENDING_MISSION_AUTHORIZATION',
            'sealed' => true,
        ]);
        $authorizationId = 'mission-authorization-'.str_repeat('e', 20);
        $authorization = self::seal([
            'schema' => 'imperium.mission-authorization/v1',
            'authorization_id' => $authorizationId,
            'instance_id' => 'imperium-test',
            'authority_source' => [
                'dossier' => ['id' => $dossierId, 'version' => 1, 'digest' => $dossier['record_digest']],
                'imperator_review' => ['id' => $reviewId, 'digest' => $review['record_digest']],
                'derivation_authority_id' => $derivationAuthorityId,
            ],
            'authorized_dossier_lines' => [$line],
            'mission_plan' => $plan,
            'derived_at' => '2026-09-04T12:01:00+00:00',
            'derivation_authority' => ['id' => $derivationAuthorityId, 'consumed' => true, 'continuing_authority' => false],
            'direct_execution_prohibited' => true,
            'silent_scope_expansion_prohibited' => true,
            'execution_authority' => false,
            'status' => 'MISSION_AUTHORIZATION_SEALED_PENDING_AUTHORIZED_PREPARATION',
            'sealed' => true,
        ]);
        self::write($root.'/var/imperium/offices/curia/planning-dossiers/'.$dossierId.'.json', $dossier);
        self::write($root.'/var/imperium/offices/curia/planning-dossier-reviews/'.$reviewId.'.json', $review);
        self::write($root.'/var/imperium/authorizations/missions/'.$authorizationId.'.json', $authorization);

        return compact('authorizationId', 'authorization', 'dossier', 'review', 'mission');
    }

    public static function mission(): array
    {
        return [
            'schema' => CanonicalMissionPlan::SCHEMA,
            'mission_id' => 'mission-canonical-authenticity-test',
            'mission_kind' => 'repository-read-only-inspection',
            'target_repository' => 'synthetic/test-repository',
            'target_commit' => str_repeat('1', 40),
            'target_tree' => str_repeat('2', 40),
            'requested_permissions' => ['read-git-objects', 'record-local-evidence'],
            'prohibitions' => ['modify-target', 'network-access', 'credential-access', 'provider-access'],
            'budget' => ['max_files' => 10, 'max_bytes' => 100000, 'max_findings' => 10, 'max_seconds' => 60],
            'expires_at' => '2026-09-04T13:00:00+00:00',
            'success_criteria' => ['verified-snapshot-evidence-produced'],
            'failure_criteria' => ['authorization-or-snapshot-verification-fails'],
            'evidence_requirements' => ['commit-tree-blob-identities', 'durable-transition-consumptions'],
            'lifecycle_transitions' => [
                ['action' => 'admit', 'actor' => 'mission-admission-controller', 'target' => 'mission', 'from' => 'AUTHORIZED', 'to' => 'ADMITTED'],
                ['action' => 'inspect', 'actor' => 'repository-inspector', 'target' => 'snapshot', 'from' => 'ADMITTED', 'to' => 'EVIDENCE_ASSEMBLED'],
                ['action' => 'complete', 'actor' => 'mission-dispositioner', 'target' => 'mission', 'from' => 'EVIDENCE_ASSEMBLED', 'to' => 'COMPLETED'],
            ],
        ];
    }

    private static function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }

    private static function write(string $path, array $record): void
    {
        if (!is_dir(dirname($path))) { mkdir(dirname($path), 0770, true); }
        file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    }

    private function __construct() {}
}
