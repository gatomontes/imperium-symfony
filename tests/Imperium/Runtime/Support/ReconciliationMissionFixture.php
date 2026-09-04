<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime\Support;

use App\Imperium\Runtime\Mission\MissionDossier;
use App\Imperium\Runtime\Mission\OperatorMissionBoundary;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorizationService;

final class ReconciliationMissionFixture
{
    public static function arguments(string $admissionId, int $at, int $expiresAt): array
    {
        $missionId = 'mission-reconciliation-'.substr(hash('sha256', $admissionId."\0".$at."\0".$expiresAt), 0, 32);
        $dossier = MissionDossier::fromArray([
            'schema' => MissionDossier::SCHEMA,
            'mission_id' => $missionId,
            'mission_kind' => 'native-effect-reconciliation-issuance',
            'mission_version' => 1,
            'operator_identity' => 'local-test-operator',
            'target_snapshot' => str_repeat('a', 40),
            'requested_acts' => [NativeEffectReconciliationIssuanceAuthorizationService::MISSION_ACTION],
            'permitted_acts' => [[
                'action' => NativeEffectReconciliationIssuanceAuthorizationService::MISSION_ACTION,
                'actor' => NativeEffectReconciliationIssuanceAuthorizationService::MISSION_ACTOR,
                'target' => $admissionId,
            ]],
            'prohibited_acts' => ['provider-invocation', 'credential-resolution', 'remote-publication'],
            'success_criteria' => ['exact-reconciliation-issuance-authorized'],
            'evidence_requirements' => ['mission-authorization-consumption'],
            'time_budget_seconds' => $expiresAt - $at,
            'resource_budget' => ['max_issuances' => 1],
            'issued_at' => $at,
            'expires_at' => $expiresAt,
            'terminal_disposition_rules' => ['success' => 'COMPLETED', 'invalid' => 'REFUSED'],
            'authorization_provenance' => ['source' => 'operator-mission-order', 'grant_id' => 'grant-'.$missionId],
        ]);
        $accepted = (new OperatorMissionBoundary())->accept($dossier, $at);
        return [
            $accepted->capability(NativeEffectReconciliationIssuanceAuthorizationService::MISSION_ACTION, NativeEffectReconciliationIssuanceAuthorizationService::MISSION_ACTOR, $admissionId),
            $accepted->consumer(), $missionId, $dossier->identity(), $admissionId, $at, $expiresAt,
        ];
    }

    private function __construct() {}
}
