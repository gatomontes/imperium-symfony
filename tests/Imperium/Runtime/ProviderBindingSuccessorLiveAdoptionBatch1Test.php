<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorExecutionAdoptionDecisionBoundaryContract as Decision;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorLiveAdoptionDecisionIssuerContract as Issuer;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorLiveAdoptionDecisionIssuerContractValidator as Validator;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorLiveAdoptionDecisionPrincipalContract as Principal;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorLiveAdoptionBatch1Test extends TestCase
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

    public function testChangedPrincipalLineageRefuses(): void
    {
        [$principal, $issuer] = $this->fixture();
        $issuer['exact_principal']['digest'] = str_repeat('f', 64);
        $issuer = $this->seal($issuer);

        $this->expectExceptionMessage(
            'PBL110_LIVE_ADOPTION_DECISION_ISSUER_INVALID',
        );
        (new Validator())->assertIssuer($issuer, $principal);
    }

    public function testPrincipalWithCredentialIdentityRefuses(): void
    {
        [$principal] = $this->fixture();
        $principal['operation_scope']['credential_reference'] = 'env://forbidden';
        $principal = $this->seal($principal);

        $this->expectExceptionMessage(
            'PBL100_LIVE_ADOPTION_DECISION_PRINCIPAL_INVALID',
        );
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
        $doc = $this->document(
            'docs/provider-binding-successor-live-adoption-batch-1-contracts.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-live-adoption-batch-1-complete.md',
        );

        foreach ([
            'BATCH_1_AUTHORITY_EMPTY_LIVE_ADOPTION_DECISION_PRINCIPAL_AND_ISSUER_CONTRACTS_COMPLETE',
            'The principal remains IDENTIFIED_NOT_ACTIVATED.',
            'The issuer remains authority-empty.',
            'No decision is produced and no authority is held.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Live Adoption Batch 2 single-use live-adoption authority issuance and durable custody contracts may next be considered.',
            'may define separately versioned contracts and pure validators only',
            'may not produce a decision, issue or consume authority, admit execution, adopt a successor or change binding state',
            'may not activate a principal or provider binding',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'may not start a provider effect',
            'may not authorize retry',
            'may not migrate a live command',
            'may not open Iron Gate or Lazaretto',
            'The provider binding remains BOUND_INACTIVE.',
            'The v3 execution admission remains NOT_IMPLEMENTED.',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function fixture(): array
    {
        $principal = $this->seal([
            'schema' => Principal::SCHEMA,
            'principal_id' => 'imperator-successor-live-adoption-principal.1',
            'instance_id' => 'instance.1',
            'office' => 'imperator',
            'seat' => 'provider-binding-successor-live-adoption-decision',
            'binding_id' => 'binding.1',
            'generation' => 1,
            'decision_scope' => Principal::DECISION_SCOPE,
            'source_principal_activation' => [
                'id' => 'principal-activation.1',
                'digest' => str_repeat('a', 64),
                'schema' => 'imperium.imperator.principal-activation/v1',
            ],
            'operation_scope' => [
                'operation' => 'provider.binding.successor.live-adoption',
                'target_id' => 'binding-successor.1',
            ],
            'replay_contention_root' => 'binding-reconciliation-root.1',
            'status' => Principal::STATUS,
            'decision_authority_held' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);

        $issuer = $this->seal([
            'schema' => Issuer::SCHEMA,
            'issuer_id' => 'provider-binding-successor-live-adoption-decision-issuer.1',
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
        return (string) preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(dirname(__DIR__, 3).'/'.$path),
        );
    }
}
