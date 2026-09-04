<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Mission\MissionCapability;
use App\Imperium\Runtime\Mission\MissionCapabilityConsumer;
use App\Imperium\Runtime\Mission\MissionDossier;
use App\Imperium\Runtime\Mission\OperatorMissionBoundary;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorizationService;
use PHPUnit\Framework\TestCase;

final class CanonicalMissionThreadBatch6AdversarialTest extends TestCase
{
    public function testMissingMissionAndLineageCannotReachReconciliationWriter(): void
    {
        $method = new \ReflectionMethod(NativeEffectReconciliationIssuanceAuthorizationService::class, 'authorize');
        self::assertCount(7, $method->getParameters());
        self::assertSame(MissionCapability::class, (string) $method->getParameters()[0]->getType());
        self::assertSame(MissionCapabilityConsumer::class, (string) $method->getParameters()[1]->getType());
        foreach ($method->getParameters() as $parameter) { self::assertFalse($parameter->isOptional()); }

        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuanceAuthorizationService.php');
        self::assertLessThan(strpos($source, 'NativeEffectReconciliationAuthorityFactory::build'), strpos($source, '$missionAuthority->consume'));
        self::assertStringNotContainsString('reconciliationIssuanceAuthorization()', (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/NativeEffect/CanonicalNativeEffectCorridor.php'));
    }

    public function testDossierMutationInvalidatesPreviouslyIssuedAuthority(): void
    {
        $original = MissionDossier::fromArray($this->dossier(20));
        $accepted = (new OperatorMissionBoundary())->accept($original, 10);
        $mutated = MissionDossier::fromArray($this->dossier(19));
        self::assertNotSame($original->identity(), $mutated->identity());
        $capability = $accepted->capability('inspect', 'inspector', str_repeat('a', 40));
        $this->fails('MIS203_CAPABILITY_MISSION_MISMATCH', fn () => $accepted->consumer()->consume(
            $capability, $mutated->missionId(), $mutated->identity(), 'inspect', 'inspector', str_repeat('a', 40), 10,
        ));
    }

    public function testTwoContendersCannotConsumeOneCapabilityTwice(): void
    {
        $accepted = (new OperatorMissionBoundary())->accept(MissionDossier::fromArray($this->dossier(20)), 10);
        $capability = $accepted->capability('inspect', 'inspector', str_repeat('a', 40));
        $outcomes = [];
        $contender = function () use ($accepted, $capability, &$outcomes): void {
            try {
                $accepted->consumer()->consume($capability, $accepted->dossier->missionId(), $accepted->dossier->identity(), 'inspect', 'inspector', str_repeat('a', 40), 10);
                $outcomes[] = 'CONSUMED';
            } catch (\RuntimeException $error) { $outcomes[] = $error->getMessage(); }
        };
        $first = new \Fiber($contender);
        $second = new \Fiber($contender);
        $first->start(); $second->start(); sort($outcomes);
        self::assertSame(['CONSUMED', 'MIS209_CAPABILITY_CONSUMED'], $outcomes);
    }

    public function testFinalWriterCoverageClaimNamesOnlyExercisedWriters(): void
    {
        $matrix = (string) file_get_contents(dirname(__DIR__, 3).'/docs/canonical-mission-thread-adversarial-matrix.md');
        foreach ([
            'CanonicalRepositoryInspectionMission',
            'NativeEffectReconciliationIssuanceAuthorizationService',
            'NativeEffectReconciliationAuthorityIssuanceService',
            'NativeEffectReconciliationAuthorityClaimDerivationService',
        ] as $writer) { self::assertStringContainsString($writer, $matrix); }
    }

    private function dossier(int $expiresAt): array
    {
        return [
            'schema' => MissionDossier::SCHEMA, 'mission_id' => 'mission-adversarial-proof',
            'mission_kind' => 'test', 'mission_version' => 1, 'operator_identity' => 'local-test',
            'target_snapshot' => str_repeat('a', 40), 'requested_acts' => ['inspect'],
            'permitted_acts' => [['action' => 'inspect', 'actor' => 'inspector', 'target' => str_repeat('a', 40)]],
            'prohibited_acts' => ['modify'], 'success_criteria' => ['proof'],
            'evidence_requirements' => ['receipt'], 'time_budget_seconds' => $expiresAt - 10,
            'resource_budget' => ['max_files' => 1], 'issued_at' => 10, 'expires_at' => $expiresAt,
            'terminal_disposition_rules' => ['success' => 'COMPLETED'],
            'authorization_provenance' => ['source' => 'operator-mission-order', 'grant_id' => 'grant-adversarial'],
        ];
    }

    private function fails(string $message, callable $call): void
    {
        try { $call(); self::fail('Expected '.$message); }
        catch (\RuntimeException $error) { self::assertSame($message, $error->getMessage()); }
    }
}
