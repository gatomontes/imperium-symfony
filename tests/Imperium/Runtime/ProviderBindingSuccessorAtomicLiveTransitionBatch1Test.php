<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorAtomicLiveTransitionDecisionContractValidator as Validator;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorAtomicLiveTransitionDecisionPrincipalInputContract as Input;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorAtomicLiveTransitionDecisionProducerContract as Producer;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorAtomicLiveTransitionDecisionResultContract as Result;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorAtomicLiveTransitionBatch1Test extends TestCase
{
    public function testExactDecisionChainValidatesPurely(): void
    {
        [$input, $producer, $result] = $this->fixture();
        $validator = new Validator();

        $validator->assertInput($input);
        $validator->assertProducer($producer, $input);
        $validator->assertResult($result, $producer, $input);

        self::assertTrue($input['authority_empty']);
        self::assertFalse($producer['decision_production_performed']);
        self::assertTrue($result['decision_performed']);
        self::assertTrue($result['authority_empty']);
        self::assertFalse($result['live_transition_performed']);
        self::assertFalse($result['continuing_authority']);
    }

    public function testResultSubstitutionAndTamperingRefuse(): void
    {
        [$input, $producer, $result] = $this->fixture();
        $result['source_binding']['id'] = 'provider-binding.2';
        $result = $this->seal($result);

        $this->expectExceptionMessage(
            'PBL720_ATOMIC_TRANSITION_DECISION_RESULT_INVALID',
        );
        (new Validator())->assertResult($result, $producer, $input);
    }

    public function testSecretBearingInputRefuses(): void
    {
        [$input] = $this->fixture();
        $input['operation_scope']['credential_reference'] = 'env://forbidden';
        $input = $this->seal($input);

        $this->expectExceptionMessage(
            'PBL700_ATOMIC_TRANSITION_DECISION_INPUT_INVALID',
        );
        (new Validator())->assertInput($input);
    }

    public function testIssuanceTargetIsAcyclicAndAuthorityEmpty(): void
    {
        [$input, $producer, $result] = $this->fixture();

        (new Validator())->assertResult($result, $producer, $input);
        self::assertSame(
            Result::REQUIRED_ISSUANCE_TARGET_FIELDS,
            array_keys($result['authority_issuance_target']),
        );
        self::assertArrayNotHasKey('digest', $result['authority_issuance_target']);
        self::assertTrue($result['authority_issuance_target']['single_use']);
        self::assertTrue($result['authority_empty']);
    }

    public function testContractsHaveNoProducerOrPersistenceDependency(): void
    {
        $root = dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/';
        $source = '';
        foreach ([
            'ProviderBindingSuccessorAtomicLiveTransitionDecisionPrincipalInputContract.php',
            'ProviderBindingSuccessorAtomicLiveTransitionDecisionProducerContract.php',
            'ProviderBindingSuccessorAtomicLiveTransitionDecisionResultContract.php',
            'ProviderBindingSuccessorAtomicLiveTransitionDecisionContractValidator.php',
        ] as $file) {
            $source .= (string) file_get_contents($root.$file);
        }

        foreach ([
            'AtomicTransition', 'ImmutableRecordStore', 'MutableStateStore',
            'AuthorityConsumptionStore', 'CredentialBroker', 'ProviderTransport',
            'public function produce', 'public function issue',
            'public function consume', 'public function execute',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testDocumentationAuthorizesAuthorityContractsNextOnly(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-atomic-live-transition-batch-1-decision-contracts.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-atomic-live-transition-batch-1-complete.md',
        );

        foreach ([
            'BATCH_1_AUTHORITY_EMPTY_TRANSITION_DECISION_INPUT_PRODUCER_AND_RESULT_CONTRACTS_COMPLETE',
            'value-shaped single-use authority issuance target',
            'contains no not-yet-existing authority-record digest',
            'acyclic decision-then-authority order',
            'No producer service, persistence dependency',
            'performs no live transition',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Atomic Live Transition Batch 2 single-use transition-authority issuance, durable custody and process-local delivery contracts with pure validation may next be considered.',
            'may define contracts and pure validators only',
            'may not produce a decision',
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
        $scope = ['operation' => 'provider.binding.successor.atomic-live-transition'];
        $root = 'binding-reconciliation-root.1';

        $input = $this->seal([
            'schema' => Input::SCHEMA,
            'input_id' => 'atomic-live-transition-input.1',
            'instance_id' => 'instance.1',
            'exact_principal' => $ref(
                'live-adoption-principal.1',
                'a',
                'imperium.imperator.provider-binding-successor-live-adoption-decision-principal/v1',
            ),
            'source_binding' => $ref(
                'provider-binding.1',
                'b',
                'imperium.la-cortine.provider-implementation-binding/v1',
            ),
            'successor_binding_target' => $ref(
                'successor-binding-target.1',
                'c',
                'imperium.la-cortine.provider-binding-successor-execution-adoption-target/v1',
            ),
            'adoption_decision' => $ref(
                'live-adoption-decision.1',
                'd',
                'imperium.imperator.provider-binding-successor-execution-adoption-decision-boundary/v1',
            ),
            'v3_admission' => $ref(
                'successor-admission-v3.1',
                'e',
                'imperium.la-cortine.governed-provider-execution-admission/v3',
            ),
            'adoption_join' => $ref(
                'successor-to-v3-join.1',
                'f',
                'imperium.la-cortine.provider-binding-successor-to-v3-adoption-join-boundary/v1',
            ),
            'operation_scope' => $scope,
            'replay_contention_root' => $root,
            'decision_scope' => Input::DECISION_SCOPE,
            'exact_combined_transition_required' => true,
            'authority_empty' => true,
            'continuing_authority' => false,
            'status' => Input::STATUS,
            'sealed' => true,
        ]);

        $producer = $this->seal([
            'schema' => Producer::SCHEMA,
            'producer_id' => 'atomic-live-transition-decision-producer.1',
            'instance_id' => 'instance.1',
            'principal_input' => $this->reference($input, 'input_id'),
            'decision_result_schema' => Result::SCHEMA,
            'decision_scope' => Input::DECISION_SCOPE,
            'permitted_dispositions' => Producer::PERMITTED_DISPOSITIONS,
            'operation_scope' => $scope,
            'replay_contention_root' => $root,
            'authority_empty' => true,
            'decision_production_performed' => false,
            'continuing_authority' => false,
            'status' => Producer::STATUS,
            'sealed' => true,
        ]);

        $result = $this->seal([
            'schema' => Result::SCHEMA,
            'decision_id' => 'atomic-live-transition-decision.1',
            'instance_id' => 'instance.1',
            'producer' => $this->reference($producer, 'producer_id'),
            'principal_input' => $this->reference($input, 'input_id'),
            'exact_principal' => $input['exact_principal'],
            'source_binding' => $input['source_binding'],
            'successor_binding_target' => $input['successor_binding_target'],
            'adoption_decision' => $input['adoption_decision'],
            'v3_admission' => $input['v3_admission'],
            'adoption_join' => $input['adoption_join'],
            'authority_issuance_target' => [
                'authority_id' => 'atomic-live-transition-authority.1',
                'authority_schema' =>
                    'imperium.imperator.provider-binding-successor-atomic-live-transition-authority/v1',
                'consumer_service' =>
                    'la-cortine.provider-binding-successor-atomic-live-transition',
                'permitted_transition' =>
                    'consume-and-commit-provider-binding-successor-atomic-live-transition',
                'replay_contention_root' => $root,
                'single_use' => true,
            ],
            'operation_scope' => $scope,
            'replay_contention_root' => $root,
            'decision_scope' => Input::DECISION_SCOPE,
            'disposition' => 'AUTHORIZED',
            'decision_performed' => true,
            'authority_empty' => true,
            'live_transition_performed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);

        return [$input, $producer, $result];
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
