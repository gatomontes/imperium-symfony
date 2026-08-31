<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionDecisionIssuerContract as Issuer;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionDecisionIssuerContractValidator as Validator;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionDecisionPrincipalContract as Principal;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionDecisionV2Contract as Decision;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionRealizationBatch1Test extends TestCase
{
    public function testExactAuthorityEmptyPrincipalAndIssuerValidate(): void
    {
        [$principal, $issuer] = $this->fixture();

        (new Validator())->assertPrincipal($principal);
        (new Validator())->assertIssuer($issuer, $principal);

        self::assertSame(Principal::SCHEMA, $principal['schema']);
        self::assertSame(Decision::SCHEMA, $issuer['decision_schema']);
        self::assertTrue($issuer['authority_empty']);
        self::assertFalse($principal['decision_authority_held']);
        self::assertFalse($issuer['decision_production_performed']);
    }

    public function testChangedPrincipalLineageAndSecretMaterialRefuse(): void
    {
        [$principal, $issuer] = $this->fixture();
        $issuer['exact_principal']['digest'] = str_repeat('f', 64);
        $issuer = $this->seal($issuer);

        $this->expectExceptionMessage('PBR110_PRODUCTION_DECISION_ISSUER_INVALID');
        (new Validator())->assertIssuer($issuer, $principal);
    }

    public function testPrincipalWithCredentialIdentityRefuses(): void
    {
        [$principal] = $this->fixture();
        $principal['operation_scope']['credential_reference'] = 'env://forbidden';
        $principal = $this->seal($principal);

        $this->expectExceptionMessage('PBR100_PRODUCTION_DECISION_PRINCIPAL_INVALID');
        (new Validator())->assertPrincipal($principal);
    }

    public function testContractsGrantNoAuthority(): void
    {
        foreach ([Principal::NON_AUTHORITIES, Issuer::NON_AUTHORITIES] as $posture) {
            foreach ($posture as $name => $value) {
                self::assertFalse($value, $name);
            }
        }

        self::assertSame('IDENTIFIED_NOT_ACTIVATED', Principal::STATUS);
        self::assertTrue(Issuer::INVARIANTS['authority_empty']);
        self::assertFalse(Issuer::INVARIANTS['decision_production_performed']);
        self::assertFalse(Issuer::INVARIANTS['continuing_authority']);
    }

    public function testDocumentationAuthorizesBatchTwoContractsOnly(): void
    {
        $doc = $this->document('docs/provider-binding-successor-production-realization-batch-1-contracts.md');
        $handoff = $this->document('docs/handoffs/provider-binding-successor-production-realization-batch-1-complete.md');

        foreach ([
            'BATCH_1_AUTHORITY_EMPTY_PRODUCTION_DECISION_PRINCIPAL_AND_ISSUER_CONTRACTS_COMPLETE',
            'The principal remains IDENTIFIED_NOT_ACTIVATED.',
            'The issuer remains authority-empty.',
            'No decision is produced and no authority is held.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Production Realization Batch 2 single-use authority issuance and durable custody contracts may next be considered.',
            'may define separately versioned contracts and pure validators only',
            'may not produce a decision, issue or consume authority, create a successor',
            'may not activate a principal or provider binding',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
            'The provider binding remains BOUND_INACTIVE.',
            'The required v3 execution admission remains NOT_IMPLEMENTED.',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function fixture(): array
    {
        $principal = $this->seal([
            'schema' => Principal::SCHEMA,
            'principal_id' => 'imperator-successor-production-principal.1',
            'instance_id' => 'instance.1',
            'office' => 'imperator',
            'seat' => 'provider-binding-successor-production-decision',
            'binding_id' => 'binding.1',
            'generation' => 1,
            'decision_scope' => Principal::DECISION_SCOPE,
            'source_principal_activation' => [
                'id' => 'principal-activation.1',
                'digest' => str_repeat('a', 64),
                'schema' => 'imperium.imperator.principal-activation/v1',
            ],
            'operation_scope' => [
                'operation' => 'provider.binding.successor.production',
                'target_id' => 'binding-successor-target.1',
            ],
            'replay_contention_root' => 'binding-reconciliation-root.1',
            'status' => Principal::STATUS,
            'decision_authority_held' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);

        $issuer = $this->seal([
            'schema' => Issuer::SCHEMA,
            'issuer_id' => 'provider-binding-successor-production-decision-issuer.1',
            'instance_id' => $principal['instance_id'],
            'exact_principal' => [
                'id' => $principal['principal_id'],
                'digest' => $principal['record_digest'],
                'schema' => $principal['schema'],
            ],
            'decision_schema' => Decision::SCHEMA,
            'permitted_transition' => Issuer::PERMITTED_TRANSITION,
            'decision_scope' => Principal::DECISION_SCOPE,
            'operation_scope' => $principal['operation_scope'],
            'replay_contention_root' => $principal['replay_contention_root'],
            'authority_empty' => true,
            'decision_production_performed' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);

        return [$principal, $issuer];
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
