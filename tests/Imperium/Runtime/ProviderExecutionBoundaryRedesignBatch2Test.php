<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\DurableProviderExecutionAuthorityIssuanceContract;
use App\Imperium\Runtime\Imperator\ProviderExecutionBoundaryDefinitionIssuanceContract;
use App\Imperium\Runtime\Imperator\ProviderExecutionBoundaryRedesignIssuanceContractValidator;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalAttestationIssuanceContract;
use App\Imperium\Runtime\Imperator\SingleOperationProviderBindingActivationIssuanceContract;
use PHPUnit\Framework\TestCase;

final class ProviderExecutionBoundaryRedesignBatch2Test extends TestCase
{
    public function testDecisionAndIssuanceContractsAreSeparateAndNonAuthorizing(): void
    {
        $contracts = $this->contracts();

        self::assertCount(4, array_unique(array_map(
            static fn (string $contract): string => $contract::DECISION_SCHEMA,
            $contracts,
        )));
        self::assertCount(4, array_unique(array_map(
            static fn (string $contract): string => $contract::ISSUANCE_SCHEMA,
            $contracts,
        )));
        self::assertCount(4, array_unique(array_map(
            static fn (string $contract): string => $contract::PERMITTED_TRANSITION,
            $contracts,
        )));

        foreach ($contracts as $contract) {
            self::assertSame(1, $contract::VERSION);
            self::assertSame(['AUTHORIZED', 'REFUSED'], $contract::DISPOSITIONS);
            self::assertContains('issuance_authority', $contract::REQUIRED_DECISION_FIELDS);
            self::assertContains('consumed_issuance_authority', $contract::REQUIRED_ISSUANCE_FIELDS);
            self::assertContains('issued_artifact', $contract::REQUIRED_ISSUANCE_FIELDS);
            foreach ($contract::NON_AUTHORITIES as $permission) {
                self::assertFalse($permission);
            }
        }
    }

    public function testValidatorAcceptsExactAuthorizedDecision(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T14:15:00+00:00');
        $decision = $this->decision(
            ProviderExecutionBoundaryDefinitionIssuanceContract::class,
            $at,
        );

        (new ProviderExecutionBoundaryRedesignIssuanceContractValidator())
            ->assertDecision(
                $decision,
                ProviderExecutionBoundaryDefinitionIssuanceContract::class,
                $at,
            );

        self::addToAssertionCount(1);
    }

    public function testValidatorRejectsWrongTargetKind(): void
    {
        $at = new \DateTimeImmutable('2026-08-30T14:15:00+00:00');
        $decision = $this->decision(
            ProviderExecutionBoundaryDefinitionIssuanceContract::class,
            $at,
        );
        $decision['target']['kind'] = 'durable_provider_execution_authority';
        $decision = $this->seal($decision);

        $this->expectExceptionMessage('PEB200_SOURCE_DECISION_INVALID');
        (new ProviderExecutionBoundaryRedesignIssuanceContractValidator())
            ->assertDecision(
                $decision,
                ProviderExecutionBoundaryDefinitionIssuanceContract::class,
                $at,
            );
    }

    public function testBatchDocumentationKeepsProductionAndExecutionClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $contracts = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/provider-execution-boundary-redesign-decision-issuance-contracts.md',
            ),
        );
        $handoff = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/handoffs/provider-execution-boundary-redesign-batch-2-complete.md',
            ),
        );

        foreach ([
            'BATCH_2_DECISION_ISSUANCE_CONTRACTS_VALIDATED_NO_PRODUCTION',
            'Contract existence, validation and an authorized disposition do not produce',
            'validator writes no record and consumes nothing',
            'CredentialCapability',
        ] as $proof) {
            self::assertNotFalse(stripos($contracts, $proof), $proof);
        }

        foreach ([
            'Only Batch 3 may next be considered',
            'must remain inert',
            'may not issue durable provider-execution authority',
            'handle a credential or capability',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'Provider Execution Assurance remains paused',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    /**
     * @return list<class-string>
     */
    private function contracts(): array
    {
        return [
            ProviderExecutionBoundaryDefinitionIssuanceContract::class,
            ProviderExecutorPrincipalAttestationIssuanceContract::class,
            DurableProviderExecutionAuthorityIssuanceContract::class,
            SingleOperationProviderBindingActivationIssuanceContract::class,
        ];
    }

    /**
     * @param class-string $contract
     */
    private function decision(string $contract, \DateTimeImmutable $at): array
    {
        $digest = str_repeat('a', 64);
        $reference = static fn (string $id): array => [
            'id' => $id,
            'digest' => $digest,
            'schema' => 'imperium.test.reference/v1',
        ];
        $basis = [];
        foreach ($contract::REQUIRED_BASIS_FIELDS as $field) {
            $basis[$field] = $reference(str_replace('_', '-', $field).'-1');
        }

        return $this->seal([
            'schema' => $contract::DECISION_SCHEMA,
            'decision_id' => 'provider-execution-boundary-decision-1',
            'instance_id' => 'instance-1',
            'source_authority' => $reference('source-authority-1'),
            'actor' => [
                'principal_id' => 'imperator-principal-1',
                'office' => 'imperator',
                'seat' => 'imperator',
                'binding_id' => 'imperator-binding-1',
                'generation' => 1,
            ],
            'target' => [
                'kind' => $contract::TARGET_KIND,
                'id' => 'provider-execution-boundary-1',
                'digest' => $digest,
                'schema' => 'imperium.la-cortine.provider-execution-boundary/v1',
            ],
            'basis' => $basis,
            'disposition' => 'AUTHORIZED',
            'rationale' => 'Exact inert definition may be issued.',
            'limitations' => 'No runtime or execution authority.',
            'issuance_authority' => [
                'authority_id' => 'provider-execution-boundary-issuance-authority-1',
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'issuer_service' => 'imperator.provider-execution-boundary-definition-issuer',
                'permitted_transition' => $contract::PERMITTED_TRANSITION,
                'target_digest' => $digest,
                'expires_at' => $at->modify('+10 minutes')->format(DATE_ATOM),
                'consumed' => false,
                'continuing_authority' => false,
            ],
            'decided_at' => $at->format(DATE_ATOM),
            'expires_at' => $at->modify('+10 minutes')->format(DATE_ATOM),
            'external_action_performed' => false,
            'sealed' => true,
        ]);
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }
}
