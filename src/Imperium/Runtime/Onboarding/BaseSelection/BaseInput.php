<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\BaseSelection;

use App\Imperium\Runtime\Onboarding\ResponseValidation\CandidateClaim;
use App\Imperium\Runtime\Onboarding\Selection\Shape;

/** Immutable internal expectations and claims; deliberately not a policy/admission record. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class BaseInput
{
    public const REFS = ['policy_ref', 'approval_ref', 'decision_ref', 'selection_rule_ref', 'workload_ref', 'profile_ref', 'requirements_ref', 'universe_ref'];
    public const LIMITS = ['attempt_micro_usd' => 100000, 'total_micro_usd' => 1200000, 'baseline_micro_usd' => 300000,
        'concurrency' => 1, 'context_reserve' => 4096, 'minimum_context' => 32768, 'attempt_deadline_ms' => 60000, 'policy_lifetime_ms' => 1800000];
    public const BINDING = ['binding_ref', 'provider', 'model_id', 'model_version', 'configuration_ref', 'adapter_ref', 'mapping_ref',
        'advertised_id', 'dispatch_id', 'revision_pin', 'request_config'];
    public const BOUNDS = ['context_tokens', 'max_generated_tokens', 'tokenizer_framing', 'generated_accounting', 'invocation_bounds',
        'adapter_support', 'access', 'data_scope'];

    private function __construct(public array $context, public array $approvedCandidates, public array $candidates, public array $evidence) {}

    public static function fromArray(mixed $raw): self
    {
        $v = Shape::object($raw, ['schema', 'context', 'approved_candidates', 'candidates', 'evidence']);
        if ($v['schema'] !== 'imperium.offline-base-selection-input/v1') { throw new \InvalidArgumentException('BASE_INVALID_SCHEMA'); }
        $c = Shape::object($v['context'], [...self::REFS, 'required_profile_predicates', 'account_scope', 'data_scope',
            'issued_at', 'evaluated_at', 'expires_at', 'universe_coverage', 'workload', 'limits', 'retryable_failure_allowlist']);
        $context = [];
        $contextIdentities = [];
        foreach (self::REFS as $key) {
            $context[$key] = Boundary::ref($c[$key]); $identity = Boundary::key($context[$key]);
            if (isset($contextIdentities[$identity])) { throw new \InvalidArgumentException('BASE_DUPLICATE_CONTEXT_REFERENCE'); }
            $contextIdentities[$identity] = true;
        }
        $context['required_profile_predicates'] = Boundary::texts($c['required_profile_predicates'], true, true);
        foreach ($context['required_profile_predicates'] as $key) {
            if (in_array($key, CandidateClaim::PREDICATES, true)) { throw new \InvalidArgumentException('BASE_AMBIGUOUS_PROFILE_PREDICATE'); }
        }
        $context['account_scope'] = Shape::text($c['account_scope']);
        $context['data_scope'] = Boundary::tag($c['data_scope'], ['PUBLIC_ONLY']);
        foreach (['issued_at', 'evaluated_at', 'expires_at'] as $key) { $context[$key] = Boundary::integer($c[$key]); }
        if ($context['expires_at'] <= $context['issued_at'] || $context['expires_at'] - $context['issued_at'] > 1800000) {
            throw new \InvalidArgumentException('BASE_INVALID_POLICY_INTERVAL');
        }
        $context['universe_coverage'] = Boundary::tag($c['universe_coverage'], ['COMPLETE']);
        $limits = Shape::object($c['limits'], array_keys(self::LIMITS));
        foreach (self::LIMITS as $key => $expected) {
            if ($limits[$key] !== $expected) { throw new \InvalidArgumentException('BASE_POLICY_LIMIT_MISMATCH'); }
        }
        $context['limits'] = self::LIMITS;
        if ($c['retryable_failure_allowlist'] !== []) { throw new \InvalidArgumentException('BASE_RETRY_POLICY_MISMATCH'); }
        $context['retryable_failure_allowlist'] = [];
        $context['workload'] = [];
        foreach (Shape::list($c['workload']) as $i => $rawScenario) {
            $s = Shape::object($rawScenario, ['group_id', 'input_tokens', 'generated_tokens', 'baseline_calls', 'maximum_calls']);
            $expected = ['group_id' => ['W1', 'W2', 'W3'][$i] ?? null, 'input_tokens' => 16384, 'generated_tokens' => 4096, 'baseline_calls' => 1, 'maximum_calls' => 4];
            foreach ($expected as $key => $value) {
                if ($s[$key] !== $value || $expected['group_id'] === null) { throw new \InvalidArgumentException('BASE_WORKLOAD_MISMATCH'); }
            }
            $context['workload'][] = $expected;
        }
        if (count($context['workload']) !== 3) { throw new \InvalidArgumentException('BASE_WORKLOAD_MISMATCH'); }

        $approved = []; $identities = []; $models = [];
        foreach (Shape::list($v['approved_candidates'], true) as $rawBinding) {
            $binding = self::binding($rawBinding); $key = Boundary::key($binding['binding_ref']);
            $identity = json_encode(self::identity($binding), JSON_THROW_ON_ERROR);
            if (isset($approved[$key]) || isset($identities[$identity])) { throw new \InvalidArgumentException('BASE_AMBIGUOUS_BINDING'); }
            $approved[$key] = $binding; $identities[$identity] = true; $models[$binding['model_id']] = true;
        }
        // A complete finite approved universe can include several approved configurations of the two aliases.
        $modelIds = array_keys($models); sort($modelIds, SORT_STRING);
        if ($modelIds !== ['deepseek-v4-flash', 'deepseek-v4-pro']) { throw new \InvalidArgumentException('BASE_INCOMPLETE_UNIVERSE'); }
        ksort($approved, SORT_STRING);
        $evidence = [];
        foreach (Shape::list($v['evidence']) as $rawEvidence) {
            $o = Observation::fromArray($rawEvidence); $key = Boundary::key($o['ref']);
            if (isset($evidence[$key])) { throw new \InvalidArgumentException('BASE_DUPLICATE_OBSERVATION'); }
            $evidence[$key] = $o;
        }
        ksort($evidence, SORT_STRING);
        $candidates = [];
        foreach (Shape::list($v['candidates']) as $rawCandidate) {
            $row = self::candidate($rawCandidate, $context, $evidence); $key = Boundary::key($row['binding']['binding_ref']);
            if (isset($candidates[$key]) || !isset($approved[$key]) || $row['binding'] !== $approved[$key]) {
                throw new \InvalidArgumentException('BASE_CANDIDATE_IDENTITY_MISMATCH');
            }
            $candidates[$key] = $row;
        }
        ksort($candidates, SORT_STRING);
        if (array_keys($candidates) !== array_keys($approved)) { throw new \InvalidArgumentException('BASE_INCOMPLETE_CANDIDATES'); }
        return new self($context, $approved, $candidates, $evidence);
    }

    private static function binding(mixed $raw): array
    {
        $v = Shape::object($raw, self::BINDING); $b = [];
        foreach (self::BINDING as $key) {
            if (str_ends_with($key, '_ref')) { $b[$key] = Boundary::ref($v[$key]); }
            elseif ($key !== 'request_config') { $b[$key] = Shape::text($v[$key]); }
        }
        if ($b['provider'] !== 'deepseek' || !in_array($b['model_id'], ['deepseek-v4-flash', 'deepseek-v4-pro'], true)
            || $b['advertised_id'] !== $b['model_id'] || $b['dispatch_id'] !== $b['model_id'] || $b['revision_pin'] !== 'UNAVAILABLE_ACCEPTED_ALIAS') {
            throw new \InvalidArgumentException('BASE_UNSUPPORTED_BINDING');
        }
        $config = Shape::object($v['request_config'], ['max_tokens', 'stream', 'temperature', 'thinking', 'response_format', 'message_roles']);
        $thinking = Shape::object($config['thinking'], ['type']); $format = Shape::object($config['response_format'], ['type']);
        if ($config['max_tokens'] !== 4096 || $config['stream'] !== false || $config['temperature'] !== 0 || $thinking['type'] !== 'disabled'
            || $format['type'] !== 'json_object' || $config['message_roles'] !== ['system', 'user']) {
            throw new \InvalidArgumentException('BASE_UNSUPPORTED_CONFIGURATION');
        }
        $b['request_config'] = ['max_tokens' => 4096, 'stream' => false, 'temperature' => 0,
            'thinking' => ['type' => 'disabled'], 'response_format' => ['type' => 'json_object'], 'message_roles' => ['system', 'user']];
        return $b;
    }

    private static function candidate(mixed $raw, array $context, array $evidence): array
    {
        $v = Shape::object($raw, ['binding', 'context_refs', 'predicates', 'bounds', 'tariffs', 'contradictions']);
        $refs = Shape::object($v['context_refs'], self::REFS); $contextRefs = [];
        foreach (self::REFS as $key) {
            $contextRefs[$key] = Boundary::ref($refs[$key]);
            if ($contextRefs[$key] !== $context[$key]) { throw new \InvalidArgumentException('BASE_CONTEXT_MISMATCH'); }
        }
        $predicates = [];
        foreach (Shape::object($v['predicates'], [...CandidateClaim::PREDICATES, ...$context['required_profile_predicates']]) as $key => $rawClaim) {
            $p = Shape::object($rawClaim, ['disposition', 'evidence_refs', 'reason']);
            $support = Boundary::refs($p['evidence_refs']); Boundary::frozen($support, $evidence);
            $predicates[$key] = ['disposition' => Boundary::tag($p['disposition'], ['PASS', 'FAIL', 'UNKNOWN']), 'evidence_refs' => $support, 'reason' => Shape::text($p['reason'])];
        }
        ksort($predicates, SORT_STRING);
        $rawBounds = Shape::object($v['bounds'], self::BOUNDS); $bounds = [];
        foreach (['context_tokens', 'max_generated_tokens'] as $key) { $bounds[$key] = Boundary::nullableInteger($rawBounds[$key]); }
        foreach (['tokenizer_framing', 'invocation_bounds', 'adapter_support'] as $key) { $bounds[$key] = Boundary::tag($rawBounds[$key], ['PASS', 'FAIL', 'UNKNOWN']); }
        $bounds['generated_accounting'] = Boundary::tag($rawBounds['generated_accounting'], ['TOTAL_INCLUDES_REASONING', 'UNKNOWN', 'SEPARATE_UNMODELLED']);
        $bounds['access'] = Boundary::tag($rawBounds['access'], ['INVOKE', 'LISTED', 'KEY_PRESENT', 'UNKNOWN']);
        $bounds['data_scope'] = Boundary::tag($rawBounds['data_scope'], ['PUBLIC_ONLY', 'PRIVATE', 'UNKNOWN']);
        $tariffs = [];
        foreach (Shape::list($v['tariffs']) as $rawTariff) {
            $t = Shape::object($rawTariff, ['group_id', 'currency', 'billing_model', 'fee_status', 'fixed_fees_micro_usd', 'fee_evidence_refs', 'meters']);
            $group = Boundary::tag($t['group_id'], ['W1', 'W2', 'W3']);
            if (isset($tariffs[$group])) { throw new \InvalidArgumentException('BASE_DUPLICATE_TARIFF_SCENARIO'); }
            $feeRefs = Boundary::refs($t['fee_evidence_refs']); Boundary::frozen($feeRefs, $evidence); $meters = [];
            foreach (Shape::list($t['meters']) as $rawMeter) {
                $m = Shape::object($rawMeter, ['kind', 'quantity', 'numerator', 'denominator', 'units', 'evidence_refs']);
                $kind = Shape::text($m['kind']);
                if (isset($meters[$kind])) { throw new \InvalidArgumentException('BASE_DUPLICATE_METER'); }
                $support = Boundary::refs($m['evidence_refs']); Boundary::frozen($support, $evidence);
                $meters[$kind] = ['kind' => $kind, 'quantity' => Boundary::integer($m['quantity']), 'numerator' => Boundary::integer($m['numerator']),
                    'denominator' => Boundary::integer($m['denominator'], true), 'units' => Shape::text($m['units']), 'evidence_refs' => $support];
            }
            ksort($meters, SORT_STRING);
            $tariffs[$group] = ['group_id' => $group, 'currency' => Shape::text($t['currency']),
                'billing_model' => Boundary::tag($t['billing_model'], ['FIXED_RATIONAL_CEIL', 'UNKNOWN', 'MINIMUM_UNMODELLED', 'TIERED_UNMODELLED']),
                'fee_status' => Boundary::tag($t['fee_status'], ['COMPLETE', 'UNKNOWN']), 'fixed_fees_micro_usd' => Boundary::nullableInteger($t['fixed_fees_micro_usd']),
                'fee_evidence_refs' => $feeRefs, 'meters' => array_values($meters)];
        }
        ksort($tariffs, SORT_STRING);
        if (array_keys($tariffs) !== ['W1', 'W2', 'W3']) { throw new \InvalidArgumentException('BASE_INCOMPLETE_TARIFF_SCENARIOS'); }
        return ['binding' => self::binding($v['binding']), 'context_refs' => $contextRefs, 'predicates' => $predicates,
            'bounds' => $bounds, 'tariffs' => $tariffs, 'contradictions' => Boundary::texts($v['contradictions'])];
    }

    public static function identity(array $binding): array
    {
        return [$binding['provider'], $binding['model_id'], $binding['model_version'], $binding['configuration_ref']['digest']];
    }

    public static function compare(array $a, array $b): int
    {
        $left = self::identity($a); $right = self::identity($b);
        foreach ($left as $i => $text) { $cmp = strcmp($text, $right[$i]); if ($cmp !== 0) { return $cmp; } }
        return 0;
    }
}
