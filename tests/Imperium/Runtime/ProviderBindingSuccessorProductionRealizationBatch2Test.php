<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\ProviderBindingSuccessorCreationAuthorityDurableCustodyBoundaryContract as Custody;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorCreationAuthorityBoundaryContractValidator as Validator;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorCreationAuthorityIssuanceBoundaryContract as Issuance;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorCreationAuthorityV2Contract as Authority;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionDecisionIssuerContract as DecisionIssuer;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionDecisionPrincipalContract as Principal;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionDecisionV2Contract as Decision;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionRealizationBatch2Test extends TestCase
{
    public function testExactEmptyIssuanceAndCustodyBoundariesJoin(): void
    {
        [$issuance, $custody] = $this->fixture();
        $validator = new Validator();

        $validator->assertIssuanceBoundary($issuance);
        $validator->assertCustodyBoundary($custody);
        $validator->assertJoin($issuance, $custody);

        self::assertFalse($issuance['authority_issued']);
        self::assertFalse($custody['authority_present']);
        self::assertFalse($custody['authority_consumed']);
    }

    public function testChangedCustodyDigestRefusesTheJoin(): void
    {
        [$issuance, $custody] = $this->fixture();
        $issuance['custody_target']['digest'] = str_repeat('f', 64);
        $issuance = $this->seal($issuance);

        $this->expectExceptionMessage('PBR220_SUCCESSOR_AUTHORITY_BOUNDARY_JOIN_INVALID');
        (new Validator())->assertJoin($issuance, $custody);
    }

    public function testSecretOrProcessLocalPersistenceClaimsRefuse(): void
    {
        [, $custody] = $this->fixture();
        $custody['process_local_identity_persisted'] = true;
        $custody = $this->seal($custody);

        $this->expectExceptionMessage('PBR210_SUCCESSOR_AUTHORITY_CUSTODY_BOUNDARY_INVALID');
        (new Validator())->assertCustodyBoundary($custody);
    }

    public function testContractsRemainAuthorityEmpty(): void
    {
        foreach ([Issuance::NON_AUTHORITIES, Custody::NON_AUTHORITIES] as $posture) {
            foreach ($posture as $name => $value) {
                self::assertFalse($value, $name);
            }
        }

        self::assertFalse(Issuance::INVARIANTS['authority_issued']);
        self::assertFalse(Custody::INVARIANTS['authority_present']);
        self::assertFalse(Custody::INVARIANTS['secret_material_persisted']);
        self::assertFalse(Custody::INVARIANTS['process_local_identity_persisted']);
    }

    public function testDocumentationAuthorizesBatchThreeInertSeamOnly(): void
    {
        $doc = $this->document('docs/provider-binding-successor-production-realization-batch-2-contracts.md');
        $handoff = $this->document('docs/handoffs/provider-binding-successor-production-realization-batch-2-complete.md');

        foreach ([
            'BATCH_2_AUTHORITY_EMPTY_SUCCESSOR_CREATION_ISSUANCE_AND_DURABLE_CUSTODY_CONTRACTS_COMPLETE',
            'No authority exists in either boundary.',
            'The issuance status is CONTRACT_ONLY_NOT_ISSUED.',
            'The custody status is CONTRACT_ONLY_EMPTY.',
            'Process-local capability identity is not persisted.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Production Realization Batch 3 atomic same-root authority-consumption and successor-creation winner contracts may next be considered.',
            'may define contracts, pure validators and an inert transactional seam only',
            'may not issue authority, consume live authority, create a live successor',
            'may not activate a principal or provider binding',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function fixture(): array
    {
        $custody = $this->seal([
            'schema' => Custody::SCHEMA,
            'custody_boundary_id' => 'successor-authority-custody.1',
            'instance_id' => 'instance.1',
            'authority_schema' => Authority::SCHEMA,
            'custody_key_kind' => 'exact_replay_contention_root',
            'replay_contention_root' => 'binding-reconciliation-root.1',
            'authorized_consumer' => [
                'service' => 'la-cortine.future-atomic-successor-creation',
                'transition' => Authority::PERMITTED_TRANSITION,
                'same_root_lock_required' => true,
            ],
            'single_authority' => true,
            'authority_present' => false,
            'authority_consumed' => false,
            'secret_material_persisted' => false,
            'process_local_identity_persisted' => false,
            'continuing_authority' => false,
            'status' => Custody::STATUS,
            'sealed' => true,
        ]);

        $issuance = $this->seal([
            'schema' => Issuance::SCHEMA,
            'issuance_boundary_id' => 'successor-authority-issuance.1',
            'instance_id' => 'instance.1',
            'exact_principal' => [
                'id' => 'imperator-successor-production-principal.1',
                'digest' => str_repeat('a', 64),
                'schema' => Principal::SCHEMA,
            ],
            'decision_issuer' => [
                'id' => 'provider-binding-successor-production-decision-issuer.1',
                'digest' => str_repeat('b', 64),
                'schema' => DecisionIssuer::SCHEMA,
            ],
            'decision_schema' => Decision::SCHEMA,
            'authority_schema' => Authority::SCHEMA,
            'permitted_transition' => Issuance::PERMITTED_TRANSITION,
            'replay_contention_root' => $custody['replay_contention_root'],
            'custody_target' => [
                'id' => $custody['custody_boundary_id'],
                'digest' => $custody['record_digest'],
                'schema' => $custody['schema'],
            ],
            'authority_single_use' => true,
            'authority_exercisable' => false,
            'authority_issued' => false,
            'continuing_authority' => false,
            'status' => Issuance::STATUS,
            'sealed' => true,
        ]);

        return [$issuance, $custody];
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function document(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
