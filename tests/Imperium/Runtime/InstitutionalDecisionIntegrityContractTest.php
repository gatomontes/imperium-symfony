<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\DecisionIntegrity\DefensibleDecisionRecordContract;
use App\Imperium\Runtime\DecisionIntegrity\InstitutionalDecisionSurfaceContract;
use PHPUnit\Framework\TestCase;

final class InstitutionalDecisionIntegrityContractTest extends TestCase
{
    public function testContractsAreSeparatelyNamedAndVersioned(): void
    {
        self::assertSame('imperium.institutional-decision-surface/v1', InstitutionalDecisionSurfaceContract::SCHEMA);
        self::assertSame(1, InstitutionalDecisionSurfaceContract::VERSION);
        self::assertSame('imperium.decision-record/v1', DefensibleDecisionRecordContract::SCHEMA);
        self::assertSame(1, DefensibleDecisionRecordContract::VERSION);
        self::assertNotSame(InstitutionalDecisionSurfaceContract::SCHEMA, DefensibleDecisionRecordContract::SCHEMA);
    }

    public function testDecisionSurfaceBindsDisclosureAndRequestedAuthorityWithoutDeciding(): void
    {
        $required = InstitutionalDecisionSurfaceContract::REQUIRED_FIELDS;

        foreach (['options_presented', 'unavailable_options', 'prohibited_options', 'material_consequences', 'risks', 'reversibility', 'recommendation', 'evidence', 'decision_owner', 'requested_authority', 'authority_not_requested', 'limitations', 'expires_at', 'material_facts_fingerprint'] as $field) {
            self::assertContains($field, $required);
        }
        self::assertSame([
            'actor_id', 'office_or_seat', 'authority_basis', 'accountability_boundary',
        ], InstitutionalDecisionSurfaceContract::REQUIRED_DECISION_OWNER_FIELDS);
        self::assertContains('authority_effect', InstitutionalDecisionSurfaceContract::REQUIRED_OPTION_FIELDS);
        self::assertContains('AUTHORIZED', InstitutionalDecisionSurfaceContract::ALLOWED_DISPOSITIONS);
        self::assertContains('RETURNED_FOR_REVISION', InstitutionalDecisionSurfaceContract::ALLOWED_DISPOSITIONS);
        self::assertContains('MODIFICATION_REQUESTED', InstitutionalDecisionSurfaceContract::ALLOWED_DISPOSITIONS);
        self::assertContains('SILENCE', InstitutionalDecisionSurfaceContract::NON_AUTHORIZING_SIGNALS);
        self::assertContains('PRIOR_CONSENT', InstitutionalDecisionSurfaceContract::NON_AUTHORIZING_SIGNALS);
        self::assertNotContains(true, InstitutionalDecisionSurfaceContract::CONSTITUTIONAL_BOUNDARY);
    }

    public function testDecisionRecordBindsTheSevenDefensibilityElementsAndAuthorityRemainder(): void
    {
        $required = DefensibleDecisionRecordContract::REQUIRED_FIELDS;

        foreach (['decision', 'decision_owner', 'options_considered', 'risks', 'evidence_relied_on', 'rationale', 'decided_at'] as $field) {
            self::assertContains($field, $required);
        }
        foreach (['instance_id', 'proceeding_id', 'source_decision_surface', 'source_requests', 'prior_decisions', 'underlying_proceeding_evidence', 'limitations', 'expires_at', 'authority_lineage', 'supersession', 'record_digest'] as $field) {
            self::assertContains($field, $required);
        }
        self::assertSame([
            'disposition',
            'decided_scope',
            'granted_authority',
            'denied_authority',
            'resulting_state',
            'everything_remaining_unauthorized',
        ], DefensibleDecisionRecordContract::REQUIRED_DECISION_FIELDS);
        self::assertContains('residual_risk_owner', DefensibleDecisionRecordContract::REQUIRED_RISK_FIELDS);
        self::assertContains('acceptance_disposition', DefensibleDecisionRecordContract::REQUIRED_RISK_FIELDS);
    }

    public function testBothContractsRequireExactEvidenceIdentityAndPreserveExistingAuthorityOwners(): void
    {
        $evidence = ['artifact_id', 'record_digest', 'provenance', 'version', 'relevance', 'sealed', 'observed_at', 'expires_at'];

        self::assertSame($evidence, InstitutionalDecisionSurfaceContract::REQUIRED_EVIDENCE_FIELDS);
        self::assertSame($evidence, DefensibleDecisionRecordContract::REQUIRED_EVIDENCE_FIELDS);
        self::assertSame(['actor_id', 'office_or_seat', 'authority_basis', 'competent_authority'], DefensibleDecisionRecordContract::REQUIRED_RESIDUAL_RISK_OWNER_FIELDS);
        self::assertSame(['authority', 'source', 'consumer', 'scope', 'limitations', 'expires_at', 'continuing_authority'], DefensibleDecisionRecordContract::REQUIRED_AUTHORITY_LINEAGE_FIELDS);
        self::assertTrue(DefensibleDecisionRecordContract::AUTHORITY_BOUNDARY['summarizes_underlying_proceeding']);
        foreach (DefensibleDecisionRecordContract::AUTHORITY_BOUNDARY as $boundary => $value) {
            if ('summarizes_underlying_proceeding' === $boundary) {
                continue;
            }
            self::assertFalse($value, $boundary);
        }
    }
}
