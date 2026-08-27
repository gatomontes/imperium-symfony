<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\DecisionIntegrity;

final class InstitutionalDecisionSurfaceValidator
{
    public function validate(array $surface, bool $requireDigest = true): void
    {
        $required = InstitutionalDecisionSurfaceContract::REQUIRED_FIELDS;
        if (!$requireDigest) {
            $required = array_values(array_diff($required, ['record_digest']));
        }
        DecisionIntegrityValidation::requireFields($surface, $required, 'DI100_DECISION_SURFACE_FIELD_REQUIRED');
        if (InstitutionalDecisionSurfaceContract::SCHEMA !== $surface['schema'] || true !== $surface['sealed']) {
            throw new \RuntimeException('DI101_DECISION_SURFACE_ENVELOPE_INVALID');
        }
        foreach (['surface_id', 'instance_id', 'proceeding_id', 'material_consequences', 'reversibility', 'requested_authority', 'material_facts_fingerprint'] as $field) {
            DecisionIntegrityValidation::requireText($surface[$field], 'DI102_DECISION_SURFACE_CONTENT_INVALID:'.$field);
        }
        if (1 !== preg_match('/^[a-f0-9]{64}$/', $surface['material_facts_fingerprint'])) {
            throw new \RuntimeException('DI102_DECISION_SURFACE_CONTENT_INVALID:material_facts_fingerprint');
        }
        $this->reference($surface['source_option_universe'], 'DI113_DECISION_SURFACE_SOURCE_INVALID');
        $this->reference($surface['source_presentation_directive'], 'DI113_DECISION_SURFACE_SOURCE_INVALID');
        $question = DecisionIntegrityValidation::requireText($surface['decision_question'], 'DI104_CONTEXT_FREE_DECISION_PROMPT');
        if (strlen($question) < 24 || 1 === preg_match('/^(?:(?:would you like to|should (?:we|i))\s+)?(?:proceed|approve|authorize|continue)(?:\s+with)?(?:\s+(?:this|the))?(?:\s+(?:matter|request|proposal|plan))?\??$/i', $question)) {
            throw new \RuntimeException('DI104_CONTEXT_FREE_DECISION_PROMPT');
        }
        $presentedAt = DecisionIntegrityValidation::requireUtcTime($surface['presented_at'], 'DI102_DECISION_SURFACE_CONTENT_INVALID:presented_at');
        $expiresAt = DecisionIntegrityValidation::requireUtcTime($surface['expires_at'], 'DI102_DECISION_SURFACE_CONTENT_INVALID:expires_at');
        if ($expiresAt <= $presentedAt) {
            throw new \RuntimeException('DI105_DECISION_SURFACE_EXPIRED');
        }
        $this->nested($surface['decision_owner'], InstitutionalDecisionSurfaceContract::REQUIRED_DECISION_OWNER_FIELDS, 'DI106_DECISION_OWNER_INVALID');
        $this->nested($surface['recommendation'], ['author', 'recommended_option_id', 'rationale'], 'DI107_RECOMMENDATION_INVALID');
        $options = DecisionIntegrityValidation::requireList($surface['options_presented'], 'DI108_PRESENTED_OPTIONS_INVALID');
        $optionIds = [];
        foreach ($options as $option) {
            $this->nested($option, InstitutionalDecisionSurfaceContract::REQUIRED_OPTION_FIELDS, 'DI108_PRESENTED_OPTIONS_INVALID');
            $id = DecisionIntegrityValidation::requireText($option['option_id'], 'DI108_PRESENTED_OPTIONS_INVALID');
            if (isset($optionIds[$id])) {
                throw new \RuntimeException('DI108_PRESENTED_OPTIONS_INVALID');
            }
            $optionIds[$id] = true;
            foreach (['plain_language_explanation', 'material_consequences', 'reversibility', 'authority_effect'] as $field) {
                DecisionIntegrityValidation::requireText($option[$field], 'DI108_PRESENTED_OPTIONS_INVALID');
            }
            DecisionIntegrityValidation::requireList($option['risks'], 'DI108_PRESENTED_OPTIONS_INVALID', true);
            DecisionIntegrityValidation::requireList($option['costs'], 'DI108_PRESENTED_OPTIONS_INVALID', true);
            DecisionIntegrityValidation::requireList($option['external_effects'], 'DI108_PRESENTED_OPTIONS_INVALID', true);
        }
        if (!isset($optionIds[$surface['recommendation']['recommended_option_id']])) {
            throw new \RuntimeException('DI107_RECOMMENDATION_INVALID');
        }
        foreach (['unavailable_options', 'prohibited_options', 'rejected_options', 'unexamined_options'] as $field) {
            foreach (DecisionIntegrityValidation::requireList($surface[$field], 'DI111_CLASSIFIED_OPTIONS_INVALID', true) as $option) {
                if (!is_array($option)) {
                    throw new \RuntimeException('DI111_CLASSIFIED_OPTIONS_INVALID');
                }
                DecisionIntegrityValidation::requireFields($option, InstitutionalDecisionSurfaceContract::REQUIRED_CLASSIFIED_OPTION_FIELDS, 'DI111_CLASSIFIED_OPTIONS_INVALID');
                $id = DecisionIntegrityValidation::requireText($option['option_id'], 'DI111_CLASSIFIED_OPTIONS_INVALID');
                if (isset($optionIds[$id])) {
                    throw new \RuntimeException('DI111_CLASSIFIED_OPTIONS_INVALID');
                }
                $optionIds[$id] = true;
                foreach (['plain_language_explanation', 'classification_reason', 'material_consequences', 'reversibility', 'authority_effect'] as $optionField) {
                    DecisionIntegrityValidation::requireText($option[$optionField], 'DI111_CLASSIFIED_OPTIONS_INVALID');
                }
                DecisionIntegrityValidation::requireList($option['risks'], 'DI111_CLASSIFIED_OPTIONS_INVALID', true);
                DecisionIntegrityValidation::requireList($option['evidence'], 'DI111_CLASSIFIED_OPTIONS_INVALID', true);
            }
        }
        foreach (['risks', 'authority_not_requested', 'limitations'] as $field) {
            DecisionIntegrityValidation::requireList($surface[$field], 'DI102_DECISION_SURFACE_CONTENT_INVALID:'.$field, true);
        }
        $allowed = DecisionIntegrityValidation::requireList($surface['allowed_dispositions'], 'DI109_ALLOWED_DISPOSITIONS_INVALID');
        if ([] !== array_diff($allowed, InstitutionalDecisionSurfaceContract::ALLOWED_DISPOSITIONS)) {
            throw new \RuntimeException('DI109_ALLOWED_DISPOSITIONS_INVALID');
        }
        foreach (DecisionIntegrityValidation::requireList($surface['evidence'], 'DI110_DECISION_SURFACE_EVIDENCE_INVALID') as $evidence) {
            if (!is_array($evidence)) {
                throw new \RuntimeException('DI110_DECISION_SURFACE_EVIDENCE_INVALID');
            }
            DecisionIntegrityValidation::validateEvidence($evidence, $presentedAt, InstitutionalDecisionSurfaceContract::REQUIRED_EVIDENCE_FIELDS, 'DI110_DECISION_SURFACE_EVIDENCE_INVALID');
        }
        $authorizationState = $surface['authorization_state'];
        if (!is_array($authorizationState)) {
            throw new \RuntimeException('DI112_AUTHORIZATION_STATE_INVALID');
        }
        DecisionIntegrityValidation::requireFields($authorizationState, InstitutionalDecisionSurfaceContract::REQUIRED_AUTHORIZATION_STATE_FIELDS, 'DI112_AUTHORIZATION_STATE_INVALID');
        if (true !== $authorizationState['decision_pending']
            || false !== $authorizationState['authority_granted']
            || false !== $authorizationState['decision_inferred']
            || InstitutionalDecisionSurfaceContract::NON_AUTHORIZING_SIGNALS !== $authorizationState['non_authorizing_signals']) {
            throw new \RuntimeException('DI112_AUTHORIZATION_STATE_INVALID');
        }
        if ($requireDigest) {
            DecisionIntegrityValidation::requireDigestIntegrity($surface, 'DI101_DECISION_SURFACE_ENVELOPE_INVALID');
        }
    }

    private function nested(mixed $value, array $fields, string $error): void
    {
        if (!is_array($value)) {
            throw new \RuntimeException($error);
        }
        DecisionIntegrityValidation::requireFields($value, $fields, $error);
        foreach ($fields as $field) {
            if (!is_bool($value[$field]) && !is_array($value[$field])) {
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
}
