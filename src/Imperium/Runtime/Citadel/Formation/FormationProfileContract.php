<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

/** Formation's officer-only specialization of contracts/profile-artifact.md.
 * It never upgrades an examination artifact or activates a Profile.
 */
final class FormationProfileContract
{
    public static function validate(array $profile, array $persona, string $seat): void
    {
        if (!FormationJournal::keys($persona, ['persona_id', 'persona_version', 'persona_digest', 'admission_state', 'evidence_record'])
            || !preg_match('/^sha256:[a-f0-9]{64}$/D', $persona['persona_digest'] ?? '')
            || !FormationJournal::keys($profile['transformation'] ?? [], ['case_id', 'specification_version', 'alchemist_disposition_id'])
            || !FormationJournal::keys($profile['qualification_contract'] ?? [], ['contract_id', 'criteria'])) {
            throw new \RuntimeException('CMF124_FORMATION_PROFILE_INVALID');
        }
        foreach (['persona_id', 'persona_version', 'evidence_record'] as $field) {
            if (!is_string($persona[$field] ?? null) || trim($persona[$field]) === '') { throw new \RuntimeException('CMF124_FORMATION_PROFILE_INVALID'); }
        }
        $body = $profile;
        unset($body['content_digest']);
        $required = ['contract_version', 'profile_id', 'profile_version', 'artifact_class', 'source_persona',
            'steward', 'target', 'transformation', 'cognitive_payload', 'qualification_contract', 'lineage', 'digest_spec', 'content_digest'];
        if (array_diff($required, array_keys($profile)) !== [] || array_diff(array_keys($profile), [...$required, 'limitations']) !== []
            || ($profile['contract_version'] ?? null) !== '1.0.0' || ($profile['artifact_class'] ?? null) !== 'officer'
            || ($profile['content_digest'] ?? null) !== 'sha256:'.FormationJournal::digest($body)
            || ($profile['source_persona'] ?? null) !== $persona
            || ($persona['admission_state'] ?? null) !== 'admitted'
            || ($profile['target'] ?? null) !== ['kind' => 'seat', 'id' => $seat]
            || ($profile['steward'] ?? null) !== ['kind' => 'office', 'id' => 'laboratorium']
            || ($profile['digest_spec'] ?? null) !== ['algorithm' => 'sha256', 'canonicalization' => 'rfc8785', 'omitted_fields' => ['content_digest']]
            || ($profile['lineage']['derived_from'] ?? null) !== $persona['persona_digest']) {
            throw new \RuntimeException('CMF124_FORMATION_PROFILE_INVALID');
        }
        foreach ([$profile['profile_id'], $profile['profile_version'], $profile['transformation']['case_id'] ?? null,
            $profile['transformation']['specification_version'] ?? null, $profile['transformation']['alchemist_disposition_id'] ?? null,
            $profile['qualification_contract']['contract_id'] ?? null, $profile['cognitive_payload']['instructions'] ?? null] as $value) {
            if (!is_string($value) || trim($value) === '') { throw new \RuntimeException('CMF124_FORMATION_PROFILE_INVALID'); }
        }
        $criteria = $profile['qualification_contract']['criteria'] ?? null;
        if (!is_array($criteria) || $criteria === []) { throw new \RuntimeException('CMF124_FORMATION_PROFILE_INVALID'); }
        foreach ($criteria as $criterion) {
            if (!is_string($criterion) || trim($criterion) === '') { throw new \RuntimeException('CMF124_FORMATION_PROFILE_INVALID'); }
        }
    }
}
