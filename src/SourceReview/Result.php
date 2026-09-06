<?php
declare(strict_types=1);
namespace App\SourceReview;

final class Result
{
    public static function parse(string $response, array $proposal): array
    {
        try {
            if (strlen($response) > 262144) { throw new \RuntimeException(); }
            $r = json_decode($response, true, 32, JSON_THROW_ON_ERROR);
            Proposal::keys($r, ['disposition', 'finding', 'rationale']);
            if (!in_array($r['disposition'], ['FINDING', 'NO_ACTIONABLE_DEFECT_FOUND', 'INSUFFICIENT_INPUT'], true) || !is_string($r['rationale']) || trim($r['rationale']) === '') { throw new \RuntimeException(); }
            if ($r['disposition'] !== 'FINDING') {
                if ($r['finding'] !== null) { throw new \RuntimeException(); }
            } else {
                $f = $r['finding'];
                Proposal::keys($f, ['path', 'start_line', 'end_line', 'triggering_input', 'expected_behavior', 'actual_behavior', 'cause', 'impact', 'suggested_correction', 'regression_case']);
                foreach ($f as $key => $value) { if (!in_array($key, ['start_line', 'end_line'], true) && (!is_string($value) || trim($value) === '')) { throw new \RuntimeException(); } }
                $files = array_column($proposal['manifest'], null, 'path');
                if (!isset($files[$f['path']]) || !is_int($f['start_line']) || !is_int($f['end_line']) || $f['start_line'] < 1 || $f['end_line'] < $f['start_line'] || $f['end_line'] > $files[$f['path']]['lines']) { throw new \RuntimeException(); }
            }
            return [...$r, 'evidence_status' => 'STATIC_HYPOTHESIS_NOT_INDEPENDENTLY_VERIFIED', 'reproduction_executed' => false];
        } catch (\Throwable) { throw new \RuntimeException('SR_RESULT_INVALID'); }
    }
}
