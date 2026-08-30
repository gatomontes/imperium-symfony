<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract as Successor;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionContract as Principal;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionContract as Decision;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract as Authorization;
use PHPUnit\Framework\TestCase;

final class PrincipalActivationDecisionAuthorityProvenanceRemediationBatch5ReadinessRefusalTest extends TestCase
{
    public function testCurrentPrincipalContractCannotRepresentRequiredSuccessorScope(): void
    {
        self::assertNotContains(
            'provider_executor_principal_activation_decision_authority',
            Principal::REQUIRED_AUTHORITY_SCOPE_FIELDS,
        );
        self::assertFalse(Principal::NON_AUTHORITIES['self_widens_scope']);
        self::assertContains('successor_principal', Successor::REQUIRED_FIELDS);
        self::assertFileDoesNotExist(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/'
                .'ImperatorRuntimePrincipalVersionV3Contract.php',
        );
    }

    public function testIssuanceAuthorizationDoesNotBindCompleteDecisionPayload(): void
    {
        foreach ([
            'actor',
            'scope',
            'disposition',
            'rationale',
            'limitations',
            'validity',
        ] as $requiredDecisionField) {
            self::assertContains(
                $requiredDecisionField,
                Decision::REQUIRED_FIELDS,
            );
            self::assertNotContains(
                $requiredDecisionField,
                Authorization::REQUIRED_FIELDS,
            );
        }

        self::assertContains('binding_id', Decision::REQUIRED_ACTOR_FIELDS);
        self::assertNotContains(
            'binding_id',
            Authorization::REQUIRED_PRINCIPAL_FIELDS,
        );
    }

    public function testDocumentationRefusesProductionAndAuthorizesContractsOnly(): void
    {
        $doc = $this->document(
            'docs/principal-activation-decision-authority-provenance-remediation-batch-5-production-refusal.md',
        );
        $handoff = $this->document(
            'docs/handoffs/principal-activation-decision-authority-provenance-remediation-batch-5-refused.md',
        );

        foreach ([
            'BATCH_5_PRODUCTION_REFUSED_SUCCESSOR_PRINCIPAL_AND_DECISION_LINEAGE_CONTRACTS_ABSENT',
            'exactly five fields',
            'placeholder schema name',
            'no canonical contract',
            'complete actor',
            'binding_id',
            'unauthorized decision maker',
            'No scope grant was consumed',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }

        foreach ([
            'Only remediation Batch 5A may next be considered',
            'authority-empty contracts',
            'exact successor Imperator principal schema',
            'exact decision-production envelope',
            'may not consume the Operator Root scope grant',
            'produce an activation decision',
            'Iron Gate',
            'Lazaretto',
            'approximately four batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
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
