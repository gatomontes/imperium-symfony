<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorCreationAuthorityContract as AuthorityV1;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorCreationAuthorityV2Contract as AuthorityV2;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionDecisionContract as DecisionV1;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionDecisionV2Contract as DecisionV2;
use PHPUnit\Framework\TestCase;

final class ProviderBindingSuccessorProductionAdoptionBatch1AContractTest extends TestCase
{
    public function testV2DecisionReplacesTheFutureAuthorityReferenceWithAnIssuanceTarget(): void
    {
        self::assertSame(DecisionV1::SCHEMA, DecisionV2::SUPERSEDES);
        self::assertNotContains('successor_creation_authority', DecisionV2::REQUIRED_FIELDS);
        self::assertContains(
            'successor_creation_authority_issuance_target',
            DecisionV2::REQUIRED_FIELDS,
        );
        self::assertNotContains('digest', DecisionV2::REQUIRED_ISSUANCE_TARGET_FIELDS);
        self::assertTrue(DecisionV2::ISSUANCE_TARGET_INVARIANTS['authority_single_use']);
        self::assertFalse(DecisionV2::ISSUANCE_TARGET_INVARIANTS['continuing_authority']);
    }

    public function testV2AuthorityCanReferenceTheAlreadySealedDecision(): void
    {
        self::assertSame(AuthorityV1::SCHEMA, AuthorityV2::SUPERSEDES);
        self::assertContains('source_decision', AuthorityV2::REQUIRED_FIELDS);
        self::assertContains('source_issuance_target', AuthorityV2::REQUIRED_FIELDS);
        self::assertSame(
            DecisionV2::REQUIRED_ISSUANCE_TARGET_FIELDS,
            AuthorityV2::REQUIRED_ISSUANCE_TARGET_FIELDS,
        );
        self::assertTrue(AuthorityV2::REQUIRED_INVARIANTS['authority_single_use']);
        self::assertFalse(AuthorityV2::REQUIRED_INVARIANTS['consumed']);
        self::assertFalse(AuthorityV2::REQUIRED_INVARIANTS['continuing_authority']);
    }

    public function testCorrectedContractsRemainAuthorityEmptyAndSecretFree(): void
    {
        foreach ([DecisionV2::NON_AUTHORITIES, AuthorityV2::NON_AUTHORITIES] as $posture) {
            foreach ($posture as $name => $value) {
                self::assertFalse($value, $name);
            }
        }

        $encoded = json_encode([
            DecisionV2::REQUIRED_FIELDS,
            DecisionV2::REQUIRED_ISSUANCE_TARGET_FIELDS,
            AuthorityV2::REQUIRED_FIELDS,
            AuthorityV2::REQUIRED_ISSUANCE_TARGET_FIELDS,
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

    public function testDocumentationAuthorizesV2OfflineValidationNextOnly(): void
    {
        $doc = $this->document(
            'docs/provider-binding-successor-production-adoption-batch-1a-contract-correction.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-successor-production-adoption-batch-1a-complete.md',
        );

        foreach ([
            'BATCH_1A_AUTHORITY_EMPTY_ACYCLIC_DECISION_AUTHORITY_CONTRACTS_COMPLETE',
            'It contains no not-yet-existing authority-record digest.',
            'seal the v2 production decision and issuance target',
            'seal the v2 authority from the decision and exact target',
            'atomically consume the authority and create the successor',
            'The provider binding remains BOUND_INACTIVE.',
        ] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }

        foreach ([
            'Only Provider Binding Successor Production Adoption Batch 2A pure fail-closed v2 validators and segregated immutable caller-supplied offline fixture stores may next be considered.',
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

    private function document(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3).'/'.$path);
        self::assertNotFalse($contents);

        return $contents;
    }
}
