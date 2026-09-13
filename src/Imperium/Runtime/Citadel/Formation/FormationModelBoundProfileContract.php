<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

/** PPC2 specialization. Never alters or replaces the approved immutable bytes. */
final class FormationModelBoundProfileContract
{
    public const SCHEMA = 'imperium.formation-model-bound-profile-evidence/v1';
    public const TARGETS = ['courtyard.courtthane', 'clavium.locksmith'];

    public static function validate(array $profile, array $persona, string $seat): void
    {
        $binding = $profile['model_binding'] ?? null;
        $body = $profile;
        unset($body['content_digest']);
        if (!in_array($seat, self::TARGETS, true) || !is_array($binding)
            || ($profile['content_digest'] ?? null) !== 'sha256:'.FormationJournal::digest($body)
            || !FormationJournal::keys($binding, ['provider_model_version', 'authorization_id', 'source_line', 'configuration', 'constraints', 'fallbacks', 'access_assertion_required'])
            || !FormationJournal::keys($binding['source_line'] ?? [], ['line_number', 'line_digest'])
            || !is_int($binding['source_line']['line_number']) || $binding['source_line']['line_number'] < 1
            || !is_string($binding['source_line']['line_digest']) || !preg_match('/^[a-f0-9]{64}$/D', $binding['source_line']['line_digest'])
            || !is_array($binding['configuration']) || array_is_list($binding['configuration'])
            || $binding['access_assertion_required'] !== true) {
            throw new \RuntimeException('PPC201_MODEL_BOUND_PROFILE_INVALID');
        }
        foreach (['provider_model_version', 'authorization_id'] as $field) {
            if (!is_string($binding[$field]) || trim($binding[$field]) === '' || strlen($binding[$field]) > 512) {
                throw new \RuntimeException('PPC201_MODEL_BOUND_PROFILE_INVALID');
            }
        }
        foreach (['constraints', 'fallbacks'] as $field) {
            if (!is_array($binding[$field]) || !array_is_list($binding[$field]) || count($binding[$field]) > 64) {
                throw new \RuntimeException('PPC201_MODEL_BOUND_PROFILE_INVALID');
            }
            foreach ($binding[$field] as $value) {
                if (!is_string($value) || trim($value) === '' || strlen($value) > 4096) {
                    throw new \RuntimeException('PPC201_MODEL_BOUND_PROFILE_INVALID');
                }
            }
            if (count(array_unique($binding[$field])) !== count($binding[$field])) {
                throw new \RuntimeException('PPC201_MODEL_BOUND_PROFILE_INVALID');
            }
        }
        // Reuse the historical structural predicates only. The projection is
        // never stored, approved, returned or used as an evidence identity.
        unset($body['model_binding']);
        $body['content_digest'] = 'sha256:'.FormationJournal::digest($body);
        FormationProfileContract::validate($body, $persona, $seat);
    }
}
