<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\DecisionIntegrity;

final class DefensibleDecisionRecordValidator
{
    public function validate(array $record, bool $requireDigest = true): void
    {
        $required = DefensibleDecisionRecordContract::REQUIRED_FIELDS;
        if (!$requireDigest) {
            $required = array_values(array_diff($required, ['record_digest']));
        }
        DecisionIntegrityValidation::requireFields($record, $required, 'DI120_DECISION_RECORD_FIELD_REQUIRED');
        if (DefensibleDecisionRecordContract::SCHEMA !== $record['schema'] || true !== $record['sealed']) {
            throw new \RuntimeException('DI121_DECISION_RECORD_ENVELOPE_INVALID');
        }
        foreach (['decision_record_id', 'instance_id', 'proceeding_id', 'rationale'] as $field) {
            DecisionIntegrityValidation::requireText($record[$field], 'DI122_DECISION_RECORD_CONTENT_INVALID:'.$field);
        }
        if (strlen(trim($record['rationale'])) < 20 || strtoupper(trim($record['rationale'])) === strtoupper((string) ($record['decision']['disposition'] ?? ''))) {
            throw new \RuntimeException('DI123_SUBSTANTIVE_RATIONALE_REQUIRED');
        }
        $decidedAt = DecisionIntegrityValidation::requireUtcTime($record['decided_at'], 'DI122_DECISION_RECORD_CONTENT_INVALID:decided_at');
        $expiresAt = DecisionIntegrityValidation::requireUtcTime($record['expires_at'], 'DI122_DECISION_RECORD_CONTENT_INVALID:expires_at');
        if ($expiresAt <= $decidedAt) {
            throw new \RuntimeException('DI122_DECISION_RECORD_CONTENT_INVALID:expires_at');
        }
        $this->nested($record['decision'], DefensibleDecisionRecordContract::REQUIRED_DECISION_FIELDS, 'DI124_DECISION_INVALID');
        $this->nested($record['decision_owner'], DefensibleDecisionRecordContract::REQUIRED_DECISION_OWNER_FIELDS, 'DI125_DECISION_OWNER_INVALID');
        foreach (['granted_authority', 'denied_authority', 'everything_remaining_unauthorized'] as $field) {
            DecisionIntegrityValidation::requireList($record['decision'][$field], 'DI124_DECISION_INVALID', 'granted_authority' === $field);
        }
        foreach (DecisionIntegrityValidation::requireList($record['source_requests'], 'DI122_DECISION_RECORD_CONTENT_INVALID:source_requests') as $reference) {
            $this->reference($reference, 'DI122_DECISION_RECORD_CONTENT_INVALID:source_requests');
        }
        foreach (DecisionIntegrityValidation::requireList($record['prior_decisions'], 'DI122_DECISION_RECORD_CONTENT_INVALID:prior_decisions', true) as $reference) {
            $this->reference($reference, 'DI122_DECISION_RECORD_CONTENT_INVALID:prior_decisions');
        }
        foreach (DecisionIntegrityValidation::requireList($record['underlying_proceeding_evidence'], 'DI122_DECISION_RECORD_CONTENT_INVALID:underlying_proceeding_evidence') as $reference) {
            $this->reference($reference, 'DI122_DECISION_RECORD_CONTENT_INVALID:underlying_proceeding_evidence');
        }
        DecisionIntegrityValidation::requireList($record['limitations'], 'DI122_DECISION_RECORD_CONTENT_INVALID:limitations', true);
        $lineage = DecisionIntegrityValidation::requireList($record['authority_lineage'], 'DI132_AUTHORITY_LINEAGE_INVALID', [] === $record['decision']['granted_authority']);
        $lineageAuthorities = [];
        foreach ($lineage as $authority) {
            $lineageAuthorities[] = $this->authorityLineage($authority, $record['decision_record_id'], $expiresAt);
        }
        $grantedAuthorities = $record['decision']['granted_authority'];
        sort($lineageAuthorities, SORT_STRING);
        sort($grantedAuthorities, SORT_STRING);
        if ($lineageAuthorities !== $grantedAuthorities) {
            throw new \RuntimeException('DI132_AUTHORITY_LINEAGE_INVALID');
        }
        $this->reference($record['source_decision_surface'], 'DI126_SOURCE_SURFACE_REFERENCE_INVALID');
        foreach (DecisionIntegrityValidation::requireList($record['options_considered'], 'DI127_OPTIONS_CONSIDERED_INVALID') as $option) {
            $this->nested($option, DefensibleDecisionRecordContract::REQUIRED_OPTION_FIELDS, 'DI127_OPTIONS_CONSIDERED_INVALID');
        }
        foreach (DecisionIntegrityValidation::requireList($record['risks'], 'DI128_RISK_RECORD_INVALID', true) as $risk) {
            $this->risk($risk);
        }
        foreach (DecisionIntegrityValidation::requireList($record['evidence_relied_on'], 'DI129_DECISION_EVIDENCE_INVALID') as $evidence) {
            if (!is_array($evidence)) {
                throw new \RuntimeException('DI129_DECISION_EVIDENCE_INVALID');
            }
            DecisionIntegrityValidation::validateEvidence($evidence, $decidedAt, DefensibleDecisionRecordContract::REQUIRED_EVIDENCE_FIELDS, 'DI129_DECISION_EVIDENCE_INVALID');
        }
        if (!is_array($record['supersession'])) {
            throw new \RuntimeException('DI130_SUPERSESSION_INVALID');
        }
        DecisionIntegrityValidation::requireFields($record['supersession'], ['supersedes', 'reason'], 'DI130_SUPERSESSION_INVALID');
        if (null !== $record['supersession']['supersedes']) {
            $this->reference($record['supersession']['supersedes'], 'DI130_SUPERSESSION_INVALID');
            DecisionIntegrityValidation::requireText($record['supersession']['reason'], 'DI130_SUPERSESSION_INVALID');
        }
        if ($requireDigest) {
            DecisionIntegrityValidation::requireDigestIntegrity($record, 'DI121_DECISION_RECORD_ENVELOPE_INVALID');
        }
    }

    private function risk(mixed $risk): void
    {
        if (!is_array($risk)) {
            throw new \RuntimeException('DI128_RISK_RECORD_INVALID');
        }
        DecisionIntegrityValidation::requireFields($risk, DefensibleDecisionRecordContract::REQUIRED_RISK_FIELDS, 'DI128_RISK_RECORD_INVALID');
        foreach (['identified_risk', 'proposed_treatment', 'applied_treatment', 'residual_risk', 'acceptance_disposition'] as $field) {
            DecisionIntegrityValidation::requireText($risk[$field], 'DI128_RISK_RECORD_INVALID');
        }
        if ('NONE' === strtoupper(trim($risk['residual_risk']))) {
            return;
        }
        if (!is_array($risk['residual_risk_owner'])) {
            throw new \RuntimeException('DI131_COMPETENT_RESIDUAL_RISK_OWNER_REQUIRED');
        }
        DecisionIntegrityValidation::requireFields($risk['residual_risk_owner'], DefensibleDecisionRecordContract::REQUIRED_RESIDUAL_RISK_OWNER_FIELDS, 'DI131_COMPETENT_RESIDUAL_RISK_OWNER_REQUIRED');
        if (true !== $risk['residual_risk_owner']['competent_authority'] || !in_array($risk['acceptance_disposition'], ['ACCEPTED', 'REFUSED', 'RETURNED_FOR_TREATMENT'], true)) {
            throw new \RuntimeException('DI131_COMPETENT_RESIDUAL_RISK_OWNER_REQUIRED');
        }
        foreach (['actor_id', 'office_or_seat', 'authority_basis'] as $field) {
            DecisionIntegrityValidation::requireText($risk['residual_risk_owner'][$field], 'DI131_COMPETENT_RESIDUAL_RISK_OWNER_REQUIRED');
        }
    }

    private function nested(mixed $value, array $fields, string $error): void
    {
        if (!is_array($value)) {
            throw new \RuntimeException($error);
        }
        DecisionIntegrityValidation::requireFields($value, $fields, $error);
        foreach ($fields as $field) {
            if (!is_array($value[$field])) {
                DecisionIntegrityValidation::requireText($value[$field], $error);
            }
        }
    }

    private function reference(mixed $value, string $error): void
    {
        if (!is_array($value)) {
            throw new \RuntimeException($error);
        }
        DecisionIntegrityValidation::requireFields($value, ['id', 'digest'], $error);
        DecisionIntegrityValidation::requireText($value['id'], $error);
        if (!is_string($value['digest']) || 1 !== preg_match('/^[a-f0-9]{64}$/', $value['digest'])) {
            throw new \RuntimeException($error);
        }
    }

    private function authorityLineage(mixed $authority, string $decisionRecordId, \DateTimeImmutable $recordExpiresAt): string
    {
        if (!is_array($authority)) {
            throw new \RuntimeException('DI132_AUTHORITY_LINEAGE_INVALID');
        }
        DecisionIntegrityValidation::requireFields($authority, DefensibleDecisionRecordContract::REQUIRED_AUTHORITY_LINEAGE_FIELDS, 'DI132_AUTHORITY_LINEAGE_INVALID');
        foreach (['authority', 'source', 'consumer', 'scope'] as $field) {
            DecisionIntegrityValidation::requireText($authority[$field], 'DI132_AUTHORITY_LINEAGE_INVALID');
        }
        DecisionIntegrityValidation::requireList($authority['limitations'], 'DI132_AUTHORITY_LINEAGE_INVALID', true);
        $expiresAt = DecisionIntegrityValidation::requireUtcTime($authority['expires_at'], 'DI132_AUTHORITY_LINEAGE_INVALID');
        if ($decisionRecordId !== $authority['source'] || $expiresAt > $recordExpiresAt || !is_bool($authority['continuing_authority'])) {
            throw new \RuntimeException('DI132_AUTHORITY_LINEAGE_INVALID');
        }

        return $authority['authority'];
    }
}
