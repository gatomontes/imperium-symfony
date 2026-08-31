<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorCreationAuthorityV2Contract as Authority;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionDecisionV2Contract as Decision;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorExecutionAdoptionTargetContract as AdoptionTarget;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorProductionAdoptionContractValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorProductionAdoptionFixtureStore;

class ProviderBindingSuccessorProductionAdoptionBatch2ATest extends ProviderBindingActivationStateReconciliationBatch2Test
{
    public function testCorrectedCallerSuppliedChainValidatesOffline(): void
    {
        $fixture = $this->productionFixture();
        $validator = new ProviderBindingSuccessorProductionAdoptionContractValidator();

        $validator->assertDecision(...$this->productionDecisionArguments($fixture));
        $validator->assertAuthority(...$this->productionAuthorityArguments($fixture));
        $validator->assertAdoptionTarget(...$this->adoptionArguments($fixture));

        self::assertFalse($fixture['authority']['consumed']);
        self::assertFalse($fixture['adoption']['live_adoption_performed']);
        self::assertSame('BOUND_INACTIVE', $fixture['binding']['status']);
    }

    public function testExpiryLineageDriftAndSecretMaterialFailClosed(): void
    {
        $validator = new ProviderBindingSuccessorProductionAdoptionContractValidator();

        $expired = $this->productionFixture();
        $expired['decision']['validity']['expires_at'] = '2026-08-31T00:30:00+00:00';
        $expired['decision'] = $this->seal($expired['decision']);
        $this->expectFailure(
            'PBA700_PRODUCTION_DECISION_INVALID',
            fn () => $validator->assertDecision(...$this->productionDecisionArguments($expired)),
        );

        $drift = $this->productionFixture();
        $drift['authority']['source_issuance_target']['authority_id'] = 'successor-authority.drift';
        $drift['authority'] = $this->seal($drift['authority']);
        $this->expectFailure(
            'PBA710_CREATION_AUTHORITY_INVALID',
            fn () => $validator->assertAuthority(...$this->productionAuthorityArguments($drift)),
        );

        $secret = $this->productionFixture();
        $secret['adoption']['credential_reference'] = 'env://forbidden';
        $secret['adoption'] = $this->seal($secret['adoption']);
        $this->expectFailure(
            'PBA720_ADOPTION_TARGET_INVALID',
            fn () => $validator->assertAdoptionTarget(...$this->adoptionArguments($secret)),
        );
    }

    public function testDefectiveV1SchemaAndImplementedAdmissionClaimFailClosed(): void
    {
        $validator = new ProviderBindingSuccessorProductionAdoptionContractValidator();

        $legacy = $this->productionFixture();
        $legacy['decision']['schema'] =
            'imperium.imperator.provider-binding-successor-production-decision/v1';
        $legacy['decision'] = $this->seal($legacy['decision']);
        $this->expectFailure(
            'PBA700_PRODUCTION_DECISION_INVALID',
            fn () => $validator->assertDecision(...$this->productionDecisionArguments($legacy)),
        );

        $implemented = $this->productionFixture();
        $implemented['adoption']['required_admission_contract']['status'] = 'IMPLEMENTED';
        $implemented['adoption'] = $this->seal($implemented['adoption']);
        $this->expectFailure(
            'PBA720_ADOPTION_TARGET_INVALID',
            fn () => $validator->assertAdoptionTarget(...$this->adoptionArguments($implemented)),
        );
    }

    public function testSegregatedImmutableStoresReplayExactlyAndConflictOnChange(): void
    {
        $fixture = $this->productionFixture();
        $root = sys_get_temp_dir().'/imperium-pba-batch2a-'.bin2hex(random_bytes(8));
        mkdir($root, 0770, true);

        try {
            $store = new ProviderBindingSuccessorProductionAdoptionFixtureStore($root);
            $first = $store->putDecision(...$this->productionDecisionArguments($fixture));
            $replay = $store->putDecision(...$this->productionDecisionArguments($fixture));
            self::assertSame($first, $replay);

            $changed = $fixture;
            $changed['decision']['limitations'] = ['offline_fixture_only', 'changed'];
            $changed['decision'] = $this->seal($changed['decision']);
            $this->expectFailure(
                'PST111_IMMUTABLE_RECORD_CONFLICT',
                fn () => $store->putDecision(...$this->productionDecisionArguments($changed)),
            );

            self::assertNotSame(
                ProviderBindingSuccessorProductionAdoptionFixtureStore::DECISIONS,
                ProviderBindingSuccessorProductionAdoptionFixtureStore::AUTHORITIES,
            );
            self::assertNotSame(
                ProviderBindingSuccessorProductionAdoptionFixtureStore::AUTHORITIES,
                ProviderBindingSuccessorProductionAdoptionFixtureStore::ADOPTION_TARGETS,
            );
        } finally {
            $this->removeTree($root);
        }
    }

    public function testDocumentationPreservesOfflineEvidenceOnly(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-production-adoption-batch-2a-validation.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-production-adoption-batch-2a-complete.md',
        );

        foreach ([
            'BATCH_2A_FAIL_CLOSED_V2_VALIDATORS_AND_IMMUTABLE_OFFLINE_FIXTURE_STORES_COMPLETE',
            'Exact replay converges.',
            'Changed evidence for the same identity conflicts.',
            'The defective v1 digest cycle refuses validation.',
            'The provider binding remains BOUND_INACTIVE.',
            'UNKNOWN_REPLAY_PROHIBITED remains binding.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Production Adoption Batch 3 disposable-root offline interruption, replay, conflict, expiry, revocation and same-root contention proof may next be considered.',
            'may not activate a principal or provider binding',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    protected function productionFixture(): array
    {
        $base = $this->fixture();
        $decisionAuthority = $this->seal([
            'schema' => 'imperium.imperator.provider-binding-successor-decision-authority/v1',
            'authority_id' => 'successor-decision-authority.1',
            'instance_id' => 'instance.1',
            'principal_id' => 'principal.1',
            'status' => 'ACTIVE',
            'sealed' => true,
        ]);
        $actor = [
            ...$base['input']['actor'],
            'decision_scope' => 'DECIDE_EXACT_PROVIDER_BINDING_SUCCESSOR_PRODUCTION',
        ];
        $issuance = [
            'authority_id' => 'successor-creation-authority.1',
            'authority_schema' => Authority::SCHEMA,
            'successor_target' => $this->reference($base['target'], 'target_id'),
            'permitted_transition' => Decision::PERMITTED_TRANSITION,
            'replay_contention_root' => $base['target']['replay_contention_root'],
            'authority_single_use' => true,
            'continuing_authority' => false,
        ];
        $decision = $this->seal([
            'schema' => Decision::SCHEMA,
            'decision_id' => 'successor-production-decision.1',
            'instance_id' => 'instance.1',
            'competent_actor' => $actor,
            'source_decision_authority' => $this->reference($decisionAuthority, 'authority_id'),
            'reconciled_target' => $this->reference($base['target'], 'target_id'),
            'reconciled_decision_input' => $this->reference($base['input'], 'decision_input_id'),
            'requested_transition' => Decision::PERMITTED_TRANSITION,
            'disposition' => 'AUTHORIZED',
            'limitations' => ['offline_fixture_only'],
            'validity' => $base['target']['validity'],
            'successor_creation_authority_issuance_target' => $issuance,
            'decided_at' => '2026-08-31T00:20:00+00:00',
            'sealed' => true,
        ]);
        $authority = $this->seal([
            'schema' => Authority::SCHEMA,
            'authority_id' => $issuance['authority_id'],
            'instance_id' => 'instance.1',
            'source_decision' => $this->reference($decision, 'decision_id'),
            'source_issuance_target' => $issuance,
            'competent_actor' => $actor,
            'successor_target' => $issuance['successor_target'],
            'permitted_transition' => Authority::PERMITTED_TRANSITION,
            'replay_contention_root' => $issuance['replay_contention_root'],
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'validity' => $decision['validity'],
            'consumed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);
        $adoption = $this->seal([
            'schema' => AdoptionTarget::SCHEMA,
            'adoption_target_id' => 'successor-adoption-target.1',
            'instance_id' => 'instance.1',
            'completed_successor' => $this->reference($base['successor'], 'successor_id'),
            'active_principal_activation' => $base['successor']['active_principal_activation'],
            'provider_binding_descriptor' => $base['successor']['provider_binding_descriptor'],
            'provider_assurance_admission' => $base['successor']['provider_assurance_admission'],
            'execution_boundary' => $base['successor']['execution_boundary'],
            'operation_scope' => $base['successor']['operation_scope'],
            'replay_contention_root' => $base['successor']['replay_contention_root'],
            'required_admission_contract' => [
                'schema' => 'imperium.la-cortine.governed-provider-execution-admission/v3',
                'version' => 3,
                'status' => 'NOT_IMPLEMENTED',
            ],
            ...AdoptionTarget::REQUIRED_INVARIANTS,
            'sealed' => true,
        ]);

        return [...$base, ...compact('decisionAuthority', 'decision', 'authority', 'adoption')];
    }

    protected function productionDecisionArguments(array $fixture): array
    {
        return [
            $fixture['decision'],
            $fixture['decisionAuthority'],
            $fixture['target'],
            $fixture['input'],
            $fixture['principal'],
            $fixture['binding'],
            $fixture['assurance'],
            $fixture['boundary'],
            new \DateTimeImmutable('2026-08-31T01:00:00+00:00'),
        ];
    }

    protected function productionAuthorityArguments(array $fixture): array
    {
        return [
            $fixture['authority'],
            ...$this->productionDecisionArguments($fixture),
        ];
    }

    protected function adoptionArguments(array $fixture): array
    {
        return [
            $fixture['adoption'],
            $fixture['successor'],
            $fixture['input'],
            $fixture['target'],
            $fixture['principal'],
            $fixture['binding'],
            $fixture['assurance'],
            $fixture['boundary'],
            new \DateTimeImmutable('2026-08-31T01:00:00+00:00'),
        ];
    }
}
