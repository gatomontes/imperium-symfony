<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\BaseSelection;

/** Pure cost/exclusion computation. No operational consumer or admission capability. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class BaseSelector
{
    public function propose(BaseInput $input): BaseProposal
    {
        $context = $input->context; $rows = []; $overflow = false; $eligible = [];
        $policyCurrent = $context['issued_at'] <= $context['evaluated_at'] && $context['evaluated_at'] < $context['expires_at'];
        foreach ($input->candidates as $candidate) {
            $reasons = []; $findings = []; $binding = $candidate['binding'];
            foreach ($candidate['predicates'] as $claim => $predicate) {
                $category = match ($claim) {
                    'identity_runtime_mapping', 'evidence_support' => 'catalogue',
                    'access', 'admissibility_data_constraints' => 'access',
                    'complete_tariff' => 'tariff', default => 'capability',
                };
                if ($predicate['disposition'] !== 'PASS') { $reasons[] = $predicate['disposition'].':PREDICATE:'.$claim; }
                $findings[$claim] = $this->support($predicate['evidence_refs'], $claim, $category, $binding, $input, $reasons);
            }
            // An adverse frozen observation cannot be hidden by omitting its ref from a PASS row.
            foreach ($input->evidence as $o) {
                if ($o['disposition'] !== 'PASS' && Observation::sameScope($o, $binding, $context)
                    && array_intersect($o['claim_keys'], array_keys($candidate['predicates'])) !== []) {
                    $reasons[] = $o['disposition'].':ADVERSE_FROZEN_OBSERVATION:'.Boundary::key($o['ref']);
                }
            }
            if (!$policyCurrent) { $reasons[] = 'UNKNOWN:POLICY_NOT_CURRENT'; }
            if ($candidate['contradictions'] !== []) { $reasons[] = 'UNKNOWN:CONTRADICTORY_CLAIMS'; }
            $bounds = $candidate['bounds'];
            if ($bounds['context_tokens'] === null) { $reasons[] = 'UNKNOWN:CONTEXT_BOUND'; }
            elseif ($bounds['context_tokens'] < 32768 || $bounds['context_tokens'] < 16384 + 4096 + 4096) { $reasons[] = 'FAIL:CONTEXT_BOUND'; }
            if ($bounds['max_generated_tokens'] === null) { $reasons[] = 'UNKNOWN:GENERATED_BOUND'; }
            elseif ($bounds['max_generated_tokens'] < 4096) { $reasons[] = 'FAIL:GENERATED_BOUND'; }
            foreach (['tokenizer_framing', 'invocation_bounds', 'adapter_support'] as $key) {
                if ($bounds[$key] !== 'PASS') { $reasons[] = $bounds[$key].':'.strtoupper($key); }
            }
            foreach (['generated_accounting' => 'TOTAL_INCLUDES_REASONING', 'access' => 'INVOKE', 'data_scope' => 'PUBLIC_ONLY'] as $key => $expected) {
                if ($bounds[$key] !== $expected) { $reasons[] = 'UNKNOWN:'.strtoupper($key); }
            }
            $costs = []; $baseline = 0; $maximum = 0; $rowOverflow = false; $completeCost = true;
            foreach ($candidate['tariffs'] as $group => $tariff) {
                $feeFindings = $this->support($tariff['fee_evidence_refs'], 'complete_tariff', 'tariff', $binding, $input, $reasons);
                $formulaKnown = $tariff['currency'] === 'USD' && $tariff['billing_model'] === 'FIXED_RATIONAL_CEIL'
                    && $tariff['fee_status'] === 'COMPLETE' && $tariff['fixed_fees_micro_usd'] !== null;
                if (!$formulaKnown) {
                    $reasons[] = 'UNKNOWN:UNMODELLED_BILLING:'.$group;
                }
                $kinds = array_column($tariff['meters'], 'kind'); sort($kinds, SORT_STRING);
                if ($kinds !== ['generated_total', 'uncached_input']) { $formulaKnown = false; $reasons[] = 'UNKNOWN:METER_COVERAGE:'.$group; }
                $meterRows = []; $sum = $tariff['fixed_fees_micro_usd'];
                foreach ($tariff['meters'] as $meter) {
                    $support = $this->support($meter['evidence_refs'], 'complete_tariff', 'tariff', $binding, $input, $reasons);
                    $expected = match ($meter['kind']) { 'uncached_input' => 16384, 'generated_total' => 4096, default => null };
                    if ($meter['quantity'] !== $expected || $meter['units'] !== 'tokens') { $formulaKnown = false; $reasons[] = 'UNKNOWN:UNSUPPORTED_METER:'.$group.':'.$meter['kind']; }
                    $rounded = null;
                    try {
                        $rounded = ExactCost::meter($meter['quantity'], $meter['numerator'], $meter['denominator']);
                        if ($sum !== null) { $sum = ExactCost::add($sum, $rounded); }
                    } catch (\OverflowException) { $rowOverflow = true; $sum = null; }
                    $meterRows[] = ['meter' => $meter, 'rounded_micro_usd' => $tariff['currency'] === 'USD' ? $rounded : null, 'source_findings' => $support];
                }
                // Still inspect all numeric operations for overflow, but never publish
                // a partial/unsupported billing formula as a complete USD cost.
                if (!$formulaKnown) { $sum = null; }
                if ($sum !== null) {
                    if ($sum > 100000) { $reasons[] = 'FAIL:ATTEMPT_COST_LIMIT:'.$group; }
                    try { $baseline = ExactCost::add($baseline, $sum); $maximum = ExactCost::add($maximum, ExactCost::multiply(4, $sum)); }
                    catch (\OverflowException) { $rowOverflow = true; }
                } else { $completeCost = false; }
                $costs[$group] = ['currency' => $tariff['currency'], 'billing_model' => $tariff['billing_model'],
                    'fixed_fees_micro_usd' => $tariff['fixed_fees_micro_usd'], 'fee_findings' => $feeFindings, 'meters' => $meterRows, 'attempt_micro_usd' => $sum];
            }
            if ($rowOverflow) { $overflow = true; $reasons[] = 'UNKNOWN:ARITHMETIC_OVERFLOW'; }
            if ($completeCost && !$rowOverflow) {
                if ($baseline > 300000) { $reasons[] = 'FAIL:BASELINE_COST_LIMIT'; }
                if ($maximum > 1200000) { $reasons[] = 'FAIL:TOTAL_COST_LIMIT'; }
            } else { $baseline = null; $maximum = null; }
            $reasons = array_values(array_unique($reasons)); sort($reasons, SORT_STRING);
            $row = ['binding' => $binding, 'predicate_findings' => $findings, 'reasons' => $reasons, 'costs' => $costs,
                'baseline_micro_usd' => $baseline, 'twelve_attempt_max_micro_usd' => $maximum, 'eligible_projection' => $reasons === []];
            $rows[] = $row;
            if ($reasons === []) { $eligible[] = $row; }
        }
        usort($rows, static fn (array $a, array $b): int => BaseInput::compare($a['binding'], $b['binding']));
        if ($overflow) { return new BaseProposal('ARITHMETIC_REFUSAL', $input, $rows, null, []); }
        if (!$policyCurrent) { return new BaseProposal('POLICY_TIME_REFUSED', $input, $rows, null, []); }
        if ($eligible === []) { return new BaseProposal('NO_ELIGIBLE_BASE', $input, $rows, null, []); }
        usort($eligible, static fn (array $a, array $b): int => ($a['baseline_micro_usd'] <=> $b['baseline_micro_usd']) ?: BaseInput::compare($a['binding'], $b['binding']));
        $ties = [];
        foreach ($eligible as $row) {
            if ($row['baseline_micro_usd'] === $eligible[0]['baseline_micro_usd']) { $ties[] = $row['binding']; }
        }
        return new BaseProposal('PROPOSED_BASE', $input, $rows, $eligible[0]['binding'], $ties);
    }

    private function support(array $refs, string $claim, string $category, array $binding, BaseInput $input, array &$reasons): array
    {
        $findings = [];
        if ($refs === []) { $reasons[] = 'UNKNOWN:MISSING_SUPPORT:'.$claim; }
        foreach ($refs as $ref) {
            $finding = Observation::finding($input->evidence[Boundary::key($ref)], $claim, $category, $binding, $input->context);
            $findings[] = $finding;
            if ($finding['disposition'] !== 'PASS') { $reasons[] = $finding['disposition'].':'.$claim.':'.$finding['reason']; }
        }
        return $findings;
    }
}
