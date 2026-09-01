<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\ProviderBindingSuccessorAtomicLiveTransitionAuthorityDurableCustodyBoundaryContract as Custody;
use App\Imperium\Runtime\Clavium\ProviderBindingSuccessorAtomicLiveTransitionAuthorityProcessLocalDeliveryBoundaryContract as Delivery;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorAtomicLiveTransitionAuthorityBoundaryContractValidator as Validator;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorAtomicLiveTransitionAuthorityContract as Authority;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorAtomicLiveTransitionAuthorityIssuanceBoundaryContract as Issuance;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorAtomicLiveTransitionDecisionResultContract as Decision;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorAtomicLiveTransitionBatch2Test extends TestCase
{
    public function testExactDecisionBoundEmptyAuthorityBoundariesValidate(): void
    {
        [$decision, $custody, $delivery, $issuance] = $this->fixture();

        (new Validator())->assertJoin($issuance, $custody, $delivery, $decision);

        self::assertFalse($issuance['authority_issued']);
        self::assertFalse($custody['authority_present']);
        self::assertFalse($delivery['authority_delivered']);
        self::assertSame(
            Delivery::DELIVERY_KIND,
            $delivery['delivery_kind'],
        );
    }

    public function testChangedDeliveryDigestInIssuanceRefusesTheJoin(): void
    {
        [$decision, $custody, $delivery, $issuance] = $this->fixture();
        $issuance['delivery_target']['digest'] = str_repeat('f', 64);
        $issuance = $this->seal($issuance);

        $this->expectExceptionMessage(
            'PBL840_ATOMIC_TRANSITION_AUTHORITY_BOUNDARY_JOIN_INVALID',
        );
        (new Validator())->assertJoin($issuance, $custody, $delivery, $decision);
    }

    public function testProcessLocalOrSecretMaterialClaimRefusesDelivery(): void
    {
        [, $custody, $delivery] = $this->fixture();
        $delivery['process_local_identity_materialized'] = true;
        $delivery = $this->seal($delivery);

        $this->expectExceptionMessage(
            'PBL830_ATOMIC_TRANSITION_AUTHORITY_DELIVERY_INVALID',
        );
        (new Validator())->assertDelivery($delivery, $custody);
    }

    public function testRefusedDecisionCannotAuthorizeIssuance(): void
    {
        [$decision, , , $issuance] = $this->fixture();
        $decision['disposition'] = 'REFUSED';
        $decision = $this->seal($decision);
        $issuance['source_decision'] = $this->reference($decision, 'decision_id');
        $issuance = $this->seal($issuance);

        $this->expectExceptionMessage(
            'PBL810_ATOMIC_TRANSITION_AUTHORITY_ISSUANCE_INVALID',
        );
        (new Validator())->assertIssuance($issuance, $decision);
    }

    public function testContractsRemainNonOperationalAndAuthorityEmpty(): void
    {
        foreach ([
            Authority::NON_AUTHORITIES,
            Issuance::NON_AUTHORITIES,
            Custody::NON_AUTHORITIES,
            Delivery::NON_AUTHORITIES,
        ] as $posture) {
            self::assertNotEmpty($posture);
            self::assertNotContains(true, $posture);
        }

        $roots = [
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/',
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Clavium/',
        ];
        $source = '';
        foreach ([
            [$roots[0], 'ProviderBindingSuccessorAtomicLiveTransitionAuthorityContract.php'],
            [$roots[0], 'ProviderBindingSuccessorAtomicLiveTransitionAuthorityIssuanceBoundaryContract.php'],
            [$roots[0], 'ProviderBindingSuccessorAtomicLiveTransitionAuthorityBoundaryContractValidator.php'],
            [$roots[1], 'ProviderBindingSuccessorAtomicLiveTransitionAuthorityDurableCustodyBoundaryContract.php'],
            [$roots[1], 'ProviderBindingSuccessorAtomicLiveTransitionAuthorityProcessLocalDeliveryBoundaryContract.php'],
        ] as [$root, $file]) {
            $source .= (string) file_get_contents($root.$file);
        }

        foreach ([
            'ImmutableRecordStore', 'MutableStateStore',
            'AuthorityConsumptionStore', 'CredentialBroker',
            'ProviderTransport', 'public function issue',
            'public function consume', 'public function deliver',
            'public function execute',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesTransactionContractsNextOnly(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-atomic-live-transition-batch-2-authority-contracts.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-atomic-live-transition-batch-2-complete.md',
        );

        foreach ([
            'BATCH_2_AUTHORITY_EMPTY_TRANSITION_AUTHORITY_ISSUANCE_CUSTODY_AND_DELIVERY_CONTRACTS_COMPLETE',
            'process-local delivery are separately versioned and pure-validated',
            'PROCESS_LOCAL_SINGLE_USE_REFERENCE',
            'CONTRACT_ONLY_NOT_ISSUED',
            'CONTRACT_ONLY_EMPTY',
            'CONTRACT_ONLY_NOT_DELIVERED',
            'No authority exists in any of those records',
            'No producer service, issuer service, persistence store',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Atomic Live Transition Batch 3 exact-root transaction journal, canonical lock order, write-set, recovery-state and combined winner/receipt contracts with pure validation and an inert seam may next be considered.',
            'may define contracts, pure validators and an inert seam only',
            'may not persist a journal',
            'may not issue or consume live authority',
            'may not admit execution',
            'may not adopt a successor or change binding state',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not start a provider effect',
            'may not authorize retry',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function fixture(): array
    {
        $ref = fn (string $id, string $digit, string $schema): array => [
            'id' => $id, 'digest' => str_repeat($digit, 64), 'schema' => $schema,
        ];
        $root = 'binding-reconciliation-root.1';
        $consumer = [
            'service' => 'la-cortine.provider-binding-successor-atomic-live-transition',
            'transition' => Authority::PERMITTED_TRANSITION,
            'same_root_lock_required' => true,
        ];
        $target = [
            'authority_id' => 'atomic-live-transition-authority.1',
            'authority_schema' => Authority::SCHEMA,
            'consumer_service' => $consumer['service'],
            'permitted_transition' =>
                'consume-and-commit-provider-binding-successor-atomic-live-transition',
            'replay_contention_root' => $root,
            'single_use' => true,
        ];

        $decision = $this->seal([
            'schema' => Decision::SCHEMA,
            'decision_id' => 'atomic-live-transition-decision.1',
            'instance_id' => 'instance.1',
            'producer' => $ref('producer.1', 'a', 'producer/v1'),
            'principal_input' => $ref('input.1', 'b', 'input/v1'),
            'exact_principal' => $ref('principal.1', 'c', 'principal/v1'),
            'source_binding' => $ref('binding.1', 'd', 'binding/v1'),
            'successor_binding_target' => $ref('target.1', 'e', 'target/v1'),
            'adoption_decision' => $ref('adoption.1', 'f', 'adoption/v1'),
            'v3_admission' => $ref('admission.1', '1', 'admission/v3'),
            'adoption_join' => $ref('join.1', '2', 'join/v1'),
            'authority_issuance_target' => $target,
            'operation_scope' => ['operation' => 'atomic-live-transition'],
            'replay_contention_root' => $root,
            'decision_scope' => 'provider-binding-successor-atomic-live-transition',
            'disposition' => 'AUTHORIZED',
            'decision_performed' => true,
            'authority_empty' => true,
            'live_transition_performed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);

        $custody = $this->seal([
            'schema' => Custody::SCHEMA,
            'custody_boundary_id' => 'atomic-transition-custody.1',
            'instance_id' => 'instance.1',
            'authority_schema' => Authority::SCHEMA,
            'custody_key_kind' => 'exact_replay_contention_root',
            'replay_contention_root' => $root,
            'authorized_consumer' => $consumer,
            'delivery_schema' => Delivery::SCHEMA,
            'single_authority' => true,
            'authority_present' => false,
            'authority_consumed' => false,
            'secret_material_persisted' => false,
            'process_local_identity_persisted' => false,
            'continuing_authority' => false,
            'status' => Custody::STATUS,
            'sealed' => true,
        ]);

        $delivery = $this->seal([
            'schema' => Delivery::SCHEMA,
            'delivery_boundary_id' => 'atomic-transition-delivery.1',
            'instance_id' => 'instance.1',
            'authority_schema' => Authority::SCHEMA,
            'custody_source' => $this->reference($custody, 'custody_boundary_id'),
            'authorized_consumer' => $consumer,
            'replay_contention_root' => $root,
            'delivery_kind' => Delivery::DELIVERY_KIND,
            'authority_delivered' => false,
            'process_local_identity_materialized' => false,
            'secret_material_present' => false,
            'durable_delivery_material_persisted' => false,
            'continuing_authority' => false,
            'status' => Delivery::STATUS,
            'sealed' => true,
        ]);

        $issuance = $this->seal([
            'schema' => Issuance::SCHEMA,
            'issuance_boundary_id' => 'atomic-transition-issuance.1',
            'instance_id' => 'instance.1',
            'source_decision' => $this->reference($decision, 'decision_id'),
            'source_issuance_target' => $target,
            'authority_schema' => Authority::SCHEMA,
            'replay_contention_root' => $root,
            'custody_target' => $this->reference($custody, 'custody_boundary_id'),
            'delivery_target' => $this->reference($delivery, 'delivery_boundary_id'),
            'authority_single_use' => true,
            'authority_exercisable' => false,
            'authority_issued' => false,
            'continuing_authority' => false,
            'status' => Issuance::STATUS,
            'sealed' => true,
        ]);

        return [$decision, $custody, $delivery, $issuance];
    }

    private function reference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function document(string $path): string
    {
        return (string) preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(dirname(__DIR__, 3).'/'.$path),
        );
    }
}
