<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\ProviderBindingActivationRevocationContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationCanonicalContractValidator;
use PHPUnit\Framework\TestCase;

final class FrozenRuntimeCoverageTripwireRestorationBatch2Test extends TestCase
{
    public function testAbsentAttestationPrincipalStillRequiresExactDecisionActorBinding(): void
    {
        $method = new \ReflectionMethod(
            ProviderExecutorPrincipalActivationCanonicalContractValidator::class,
            'targetMatchesDecision',
        );
        $target = [
            'principal_id' => 'provider-executor-principal-1',
            'binding_id' => 'provider-binding-1',
            'generation' => 2,
            'process_boundary_id' => 'php-process-1',
            'provider_id' => 'agentmail',
            'operation' => 'email.send',
        ];
        $decision = [
            'actor' => ['binding_id' => 'provider-binding-1'],
            'scope' => [
                'principal_id' => 'provider-executor-principal-1',
                'principal_generation' => 2,
                'process_boundary_id' => 'php-process-1',
                'provider_id' => 'agentmail',
                'operation' => 'email.send',
            ],
        ];
        $validator = new ProviderExecutorPrincipalActivationCanonicalContractValidator();

        self::assertTrue($method->invoke($validator, $target, $decision, []));

        $substituted = $target;
        $substituted['binding_id'] = 'substitute-binding';
        self::assertFalse($method->invoke($validator, $substituted, $decision, []));

        self::assertTrue($method->invoke(
            $validator,
            $target,
            $decision,
            ['principal' => ['binding_id' => 'provider-binding-1']],
        ));
        self::assertFalse($method->invoke(
            $validator,
            $target,
            $decision,
            ['principal' => ['binding_id' => 'substitute-binding']],
        ));
    }

    public function testHistoricalSeparateRevocationShapeIsNotACombinedAdmissionWinner(): void
    {
        self::assertSame(
            'DO_NOT_PRODUCE_SEPARATELY',
            ProviderBindingActivationRevocationContract::PRODUCTION_POSTURE,
        );
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/'
            .'GovernedProviderExecutionCombinedAdmissionService.php',
        );

        self::assertStringNotContainsString('legacyRevocationId', $source);
        self::assertStringNotContainsString('public const string REVOCATIONS', $source);
        self::assertStringContainsString(
            'ProviderBindingActivationRevocationWinnerService::WINNERS',
            $source,
        );
    }

    public function testAdjudicationRecordsSeparateDispositionsAndClosedBoundary(): void
    {
        $document = (string) file_get_contents(
            dirname(__DIR__, 3)
            .'/docs/frozen-runtime-coverage-tripwire-restoration-batch-2-adjudication.md',
        );
        foreach ([
            'BATCH_2_PR_728_PROVIDER_RUNTIME_AND_VOCABULARY_CHANGES_ADJUDICATED',
            'REVERTED_CONTRADICTED_ATOMIC_WINNER_CONTRACT',
            'RETAINED_FAIL_CLOSED_ACTOR_BINDING_FALLBACK',
            'CORRECTED_TO_EXACT_SIX_ROLE_CLASSIFIED_INVENTORY',
            'Only Frozen Runtime Coverage Tripwire Restoration Batch 3',
            'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $document);
        }
    }
}
