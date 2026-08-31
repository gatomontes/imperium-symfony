<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorCreationAuthorityContract as CreationAuthority;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionDecisionContract as ProductionDecision;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorExecutionAdoptionTargetContract as AdoptionTarget;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionAdoptionBatch1Test extends TestCase
{
    public function testProductionDecisionBindsTheExactCompetentActorAndTransition(): void
    {
        self::assertSame(
            'CREATE_EXACT_RECONCILED_PROVIDER_BINDING_SUCCESSOR',
            ProductionDecision::PERMITTED_TRANSITION,
        );
        self::assertSame(['AUTHORIZED', 'REFUSED'], ProductionDecision::DISPOSITIONS);
        self::assertContains('competent_actor', ProductionDecision::REQUIRED_FIELDS);
        self::assertContains('source_decision_authority', ProductionDecision::REQUIRED_FIELDS);
        self::assertContains('reconciled_target', ProductionDecision::REQUIRED_FIELDS);
        self::assertContains('successor_creation_authority', ProductionDecision::REQUIRED_FIELDS);

        foreach (ProductionDecision::NON_AUTHORITIES as $name => $value) {
            self::assertFalse($value, $name);
        }
    }

    public function testCreationAuthorityIsSingleUseUnconsumedAndDecisionBound(): void
    {
        self::assertContains('source_decision', CreationAuthority::REQUIRED_FIELDS);
        self::assertContains('successor_target', CreationAuthority::REQUIRED_FIELDS);
        self::assertContains('replay_contention_root', CreationAuthority::REQUIRED_FIELDS);
        self::assertTrue(CreationAuthority::REQUIRED_INVARIANTS['authority_single_use']);
        self::assertTrue(CreationAuthority::REQUIRED_INVARIANTS['authority_exercisable']);
        self::assertFalse(CreationAuthority::REQUIRED_INVARIANTS['consumed']);
        self::assertFalse(CreationAuthority::REQUIRED_INVARIANTS['continuing_authority']);

        foreach (CreationAuthority::NON_AUTHORITIES as $name => $value) {
            self::assertFalse($value, $name);
        }
    }

    public function testAdoptionTargetCannotSynthesizeAdoptOrStartAnEffect(): void
    {
        self::assertContains('completed_successor', AdoptionTarget::REQUIRED_FIELDS);
        self::assertContains('required_admission_contract', AdoptionTarget::REQUIRED_FIELDS);

        foreach (AdoptionTarget::REQUIRED_INVARIANTS as $name => $value) {
            self::assertFalse($value, $name);
        }
        foreach (AdoptionTarget::NON_AUTHORITIES as $name => $value) {
            self::assertFalse($value, $name);
        }
    }

    public function testContractsRecursivelyExcludeSecretsAndProcessLocalCapabilityIdentity(): void
    {
        $encoded = json_encode([
            ProductionDecision::REQUIRED_FIELDS,
            ProductionDecision::REQUIRED_ACTOR_FIELDS,
            CreationAuthority::REQUIRED_FIELDS,
            CreationAuthority::REQUIRED_ROOT_FIELDS,
            AdoptionTarget::REQUIRED_FIELDS,
            AdoptionTarget::REQUIRED_OPERATION_SCOPE_FIELDS,
        ], JSON_THROW_ON_ERROR);

        foreach ([
            'credential_bytes',
            'environment_variable',
            'provider_token',
            'authentication_material',
            'callback_identity',
            'object_identity',
            'capability_identity',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $encoded);
        }
    }

    public function testDocumentationAuthorizesPureOfflineValidationNextOnly(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-production-adoption-batch-1-contracts.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-production-adoption-batch-1-complete.md',
        );

        foreach ([
            'BATCH_1_AUTHORITY_EMPTY_PRODUCTION_DECISION_CREATION_AUTHORITY_AND_ADOPTION_TARGET_CONTRACTS_COMPLETE',
            'There is no producer, validator, fixture, store, consumption record',
            'The current v2 combined admission is unchanged.',
            'The provider binding remains BOUND_INACTIVE.',
            'UNKNOWN_REPLAY_PROHIBITED remains binding.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Production Adoption Batch 2 pure fail-closed validators and segregated immutable caller-supplied offline fixture stores may next be considered.',
            'may not activate a principal or provider binding',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function document(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
