<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Mission\MissionCapability;
use App\Imperium\Runtime\Mission\MissionCapabilityVerifier;
use App\Imperium\Runtime\Mission\MissionDossier;
use App\Imperium\Runtime\Mission\OperatorMissionBoundary;
use App\Imperium\Runtime\NativeEffect\CanonicalNativeEffectCorridor;
use PHPUnit\Framework\TestCase;

final class CanonicalMissionThreadBatch2Test extends TestCase
{
    public function testOperatorCapabilityIsExactSingleUseAndConsumerCannotIssue(): void
    {
        $accepted = (new OperatorMissionBoundary())->accept(MissionDossier::fromArray($this->dossier()), 10);
        $capability = $accepted->capability('inspect', 'inspector', str_repeat('a', 40));
        $consumer = $accepted->consumer();
        self::assertFalse(method_exists($consumer, 'issue'));
        $receipt = $consumer->consume($capability, 'mission-capability-proof', $accepted->dossier->identity(), 'inspect', 'inspector', str_repeat('a', 40), 10);
        self::assertSame('mission-capability-proof', $receipt['mission_id']);
        $this->fails('MIS209_CAPABILITY_CONSUMED', fn () => $consumer->consume($capability, 'mission-capability-proof', $accepted->dossier->identity(), 'inspect', 'inspector', str_repeat('a', 40), 10));
    }

    public function testCapabilitySubstitutionExpiryRevocationAndForgeryFailClosed(): void
    {
        foreach ([
            ['MIS203_CAPABILITY_MISSION_MISMATCH', 'other-mission', 'inspect', 'inspector', str_repeat('a', 40), 10],
            ['MIS204_CAPABILITY_ACTION_MISMATCH', 'mission-capability-proof', 'modify', 'inspector', str_repeat('a', 40), 10],
            ['MIS205_CAPABILITY_ACTOR_MISMATCH', 'mission-capability-proof', 'inspect', 'other', str_repeat('a', 40), 10],
            ['MIS206_CAPABILITY_TARGET_MISMATCH', 'mission-capability-proof', 'inspect', 'inspector', str_repeat('b', 40), 10],
            ['MIS207_CAPABILITY_EXPIRED', 'mission-capability-proof', 'inspect', 'inspector', str_repeat('a', 40), 20],
        ] as [$message, $mission, $action, $actor, $target, $at]) {
            $accepted = (new OperatorMissionBoundary())->accept(MissionDossier::fromArray($this->dossier()), 10);
            $capability = $accepted->capability('inspect', 'inspector', str_repeat('a', 40));
            $this->fails($message, fn () => $accepted->consumer()->consume($capability, $mission, $accepted->dossier->identity(), $action, $actor, $target, $at));
        }

        $accepted = (new OperatorMissionBoundary())->accept(MissionDossier::fromArray($this->dossier()), 10);
        $capability = $accepted->capability('inspect', 'inspector', str_repeat('a', 40));
        self::assertInstanceOf(MissionCapabilityVerifier::class, $accepted->consumer());
        $accepted->consumer()->revoke($capability);
        $this->fails('MIS208_CAPABILITY_REVOKED', fn () => $accepted->consumer()->consume($capability, 'mission-capability-proof', $accepted->dossier->identity(), 'inspect', 'inspector', str_repeat('a', 40), 10));

        $forged = new MissionCapability(
            $capability->missionId, $capability->dossierIdentity, $capability->action,
            $capability->actor, $capability->target, $capability->notBefore, $capability->expiresAt,
            $capability->nonce, $capability->authorizationProvenance, str_repeat('0', 64),
        );
        $this->fails('MIS202_CAPABILITY_FORGED', fn () => $accepted->consumer()->consume($forged, 'mission-capability-proof', $accepted->dossier->identity(), 'inspect', 'inspector', str_repeat('a', 40), 10));
    }

    public function testUnauthenticatedReconciliationAuthorizationFactoryIsAbsent(): void
    {
        self::assertFalse(method_exists(CanonicalNativeEffectCorridor::class, 'reconciliationIssuanceAuthorization'));
    }

    private function dossier(): array
    {
        return [
            'schema' => MissionDossier::SCHEMA, 'mission_id' => 'mission-capability-proof',
            'mission_kind' => 'test', 'mission_version' => 1, 'operator_identity' => 'local-test',
            'target_snapshot' => str_repeat('a', 40), 'requested_acts' => ['inspect'],
            'permitted_acts' => [['action' => 'inspect', 'actor' => 'inspector', 'target' => str_repeat('a', 40)]],
            'prohibited_acts' => ['modify'], 'success_criteria' => ['proof'],
            'evidence_requirements' => ['receipt'], 'time_budget_seconds' => 10,
            'resource_budget' => ['max_files' => 1], 'issued_at' => 10, 'expires_at' => 20,
            'terminal_disposition_rules' => ['success' => 'COMPLETED'],
            'authorization_provenance' => ['source' => 'operator-mission-order', 'grant_id' => 'grant-proof'],
        ];
    }

    private function fails(string $message, callable $call): void
    {
        try { $call(); self::fail('Expected '.$message); }
        catch (\RuntimeException $error) { self::assertSame($message, $error->getMessage()); }
    }
}
