<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\DelegateMissionCapabilityDemandService;

final class FormationPlan
{
    public const DISCLOSURES = ['material_facts', 'assumptions', 'unknowns', 'dependencies', 'personnel', 'tools_credentials_data', 'external_operations', 'cost_time_retention_limits', 'risks_contingencies_fallbacks', 'evidence_provenance_reporting', 'expiry_revocation_reauthorization'];

    public static function validate(mixed $response): void
    {
        if (!is_array($response) || !FormationJournal::keys($response, ['mission_plan', 'disclosures', 'formation_dependencies'])
            || !is_array($response['disclosures']) || !FormationJournal::keys($response['disclosures'], self::DISCLOSURES)
            || !is_string($response['formation_dependencies']) || '' === trim($response['formation_dependencies'])) {
            throw new \RuntimeException('CMF070_PROPOSAL_SCHEMA_INVALID');
        }
        // Same production Step 1 validator, before mission approval, no supplementary prose.
        DelegateMissionCapabilityDemandService::validateDelegateDemandPlan($response['mission_plan']);
        foreach ($response['disclosures'] as $values) {
            if (!is_array($values) || !array_is_list($values) || [] === $values) { throw new \RuntimeException('CMF070_PROPOSAL_SCHEMA_INVALID'); }
            foreach ($values as $value) {
                if (!is_string($value) || '' === trim($value)) { throw new \RuntimeException('CMF070_PROPOSAL_SCHEMA_INVALID'); }
            }
        }
    }

    public static function lines(array $response): array
    {
        self::validate($response);
        $lines = [];
        foreach ($response as $section => $values) {
            foreach (is_array($values) ? $values : [$values] as $key => $value) {
                $line = ['line_number' => count($lines) + 1, 'section' => $section.'.'.$key,
                    'text' => is_string($value) ? $value : CanonicalJson::encode($value)];
                $line['line_digest'] = FormationJournal::digest($line);
                $lines[] = $line;
            }
        }
        return $lines;
    }
}
