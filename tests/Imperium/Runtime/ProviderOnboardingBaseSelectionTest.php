<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Onboarding\BaseSelection\{BaseInput, BaseProposal, BaseSelector, Boundary, ExactCost, Observation};
use App\Imperium\Runtime\Onboarding\Selection\RoleInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProviderOnboardingBaseSelectionTest extends TestCase
{
    private const AT = 1000000000;
    private const GENERIC = ['identity_runtime_mapping', 'evidence_support', 'minimum_capability', 'exact_role_profile_fit', 'access',
        'admissibility_data_constraints', 'complete_tariff', 'bounds_usage_support', 'contradictions', 'uncertainty'];
    private const CONTEXT_REFS = ['policy_ref', 'approval_ref', 'decision_ref', 'selection_rule_ref', 'workload_ref', 'profile_ref', 'requirements_ref', 'universe_ref'];

    private static function ref(string $id): array { return ['schema' => 'imperium.synthetic/v1', 'id' => $id, 'digest' => 'sha256:'.hash('sha256', $id)]; }

    private static function fixture(): array
    {
        $context = [];
        foreach (self::CONTEXT_REFS as $key) { $context[$key] = self::ref($key); }
        $context += ['required_profile_predicates' => ['profile.public-comparison'], 'account_scope' => 'synthetic-account', 'data_scope' => 'PUBLIC_ONLY',
            'issued_at' => self::AT - 1000, 'evaluated_at' => self::AT, 'expires_at' => self::AT + 1799000,
            'universe_coverage' => 'COMPLETE', 'workload' => [],
            'limits' => ['attempt_micro_usd' => 100000, 'total_micro_usd' => 1200000, 'baseline_micro_usd' => 300000, 'concurrency' => 1,
                'context_reserve' => 4096, 'minimum_context' => 32768, 'attempt_deadline_ms' => 60000, 'policy_lifetime_ms' => 1800000],
            'retryable_failure_allowlist' => []];
        foreach (['W1', 'W2', 'W3'] as $g) { $context['workload'][] = ['group_id' => $g, 'input_tokens' => 16384, 'generated_tokens' => 4096, 'baseline_calls' => 1, 'maximum_calls' => 4]; }
        $v = ['schema' => 'imperium.offline-base-selection-input/v1', 'context' => $context, 'approved_candidates' => [], 'candidates' => [], 'evidence' => []];
        foreach (['flash', 'pro'] as $id) {
            $binding = ['binding_ref' => self::ref($id), 'provider' => 'deepseek', 'model_id' => 'deepseek-v4-'.$id, 'model_version' => 'synthetic-observed-v1',
                'configuration_ref' => self::ref('config.'.$id), 'adapter_ref' => self::ref('adapter.'.$id), 'mapping_ref' => self::ref('mapping.'.$id),
                'advertised_id' => 'deepseek-v4-'.$id, 'dispatch_id' => 'deepseek-v4-'.$id, 'revision_pin' => 'UNAVAILABLE_ACCEPTED_ALIAS',
                'request_config' => ['max_tokens' => 4096, 'stream' => false, 'temperature' => 0, 'thinking' => ['type' => 'disabled'],
                    'response_format' => ['type' => 'json_object'], 'message_roles' => ['system', 'user']]];
            $v['approved_candidates'][] = $binding;
            foreach (['catalogue', 'capability', 'tariff', 'access'] as $category) {
                $v['evidence'][] = ['ref' => self::ref($id.'.'.$category), 'source_ref' => self::ref('source.'.$id.'.'.$category),
                    'content_ref' => self::ref('bytes.'.$id.'.'.$category), 'source_locator' => 'synthetic://fixture/'.$id.'/'.$category,
                    'category' => $category, 'officialness' => 'OFFICIAL', 'observed_at' => self::AT, 'effective_from' => self::AT - 700000000,
                    'effective_until' => self::AT + 2000000,
                    'scope' => ['provider' => 'deepseek', 'model_id' => $binding['model_id'], 'model_version' => $binding['model_version'],
                        'configuration_digest' => $binding['configuration_ref']['digest'], 'profile_ref' => $context['profile_ref'],
                        'account_scope' => 'synthetic-account', 'data_scope' => 'PUBLIC_ONLY'],
                    'claim_keys' => [...self::GENERIC, 'profile.public-comparison'], 'disposition' => 'PASS', 'reason' => 'Synthetic supplied finding; not genuine evidence'];
            }
            $row = ['binding' => $binding, 'context_refs' => array_intersect_key($context, array_flip(self::CONTEXT_REFS)), 'predicates' => [],
                'bounds' => ['context_tokens' => 32768, 'max_generated_tokens' => 4096, 'tokenizer_framing' => 'PASS',
                    'generated_accounting' => 'TOTAL_INCLUDES_REASONING', 'invocation_bounds' => 'PASS', 'adapter_support' => 'PASS', 'access' => 'INVOKE', 'data_scope' => 'PUBLIC_ONLY'],
                'tariffs' => [], 'contradictions' => []];
            foreach ([...self::GENERIC, 'profile.public-comparison'] as $claim) {
                $category = in_array($claim, ['identity_runtime_mapping', 'evidence_support'], true) ? 'catalogue'
                    : (in_array($claim, ['access', 'admissibility_data_constraints'], true) ? 'access' : ($claim === 'complete_tariff' ? 'tariff' : 'capability'));
                $row['predicates'][$claim] = ['disposition' => 'PASS', 'evidence_refs' => [self::ref($id.'.'.$category)], 'reason' => 'Synthetic predicate'];
            }
            foreach (['W1', 'W2', 'W3'] as $g) {
                $row['tariffs'][] = ['group_id' => $g, 'currency' => 'USD', 'billing_model' => 'FIXED_RATIONAL_CEIL', 'fee_status' => 'COMPLETE',
                    'fixed_fees_micro_usd' => 0, 'fee_evidence_refs' => [self::ref($id.'.tariff')], 'meters' => [
                        ['kind' => 'uncached_input', 'quantity' => 16384, 'numerator' => $id === 'flash' ? 440000 : 1320000,
                            'denominator' => 1000000, 'units' => 'tokens', 'evidence_refs' => [self::ref($id.'.tariff')]],
                        ['kind' => 'generated_total', 'quantity' => 4096, 'numerator' => $id === 'flash' ? 1320000 : 3960000,
                            'denominator' => 1000000, 'units' => 'tokens', 'evidence_refs' => [self::ref($id.'.tariff')]],
                    ]];
            }
            $v['candidates'][] = $row;
        }
        return $v;
    }

    private static function propose(array $v): BaseProposal { return (new BaseSelector())->propose(BaseInput::fromArray($v)); }

    public function testExactConditionalArithmeticAndGenuineAdmissionBoundary(): void
    {
        $v = self::fixture(); $p = self::propose($v);
        self::assertSame('PROPOSED_BASE', $p->status);
        self::assertSame('deepseek-v4-flash', $p->proposedBinding['model_id']);
        self::assertSame([37848, 113544], array_column($p->rows, 'baseline_micro_usd'));
        self::assertSame([151392, 454176], array_column($p->rows, 'twelve_attempt_max_micro_usd'));
        self::assertSame(12616, $p->rows[0]['costs']['W1']['attempt_micro_usd']);
        self::assertSame(7209, $p->rows[0]['costs']['W1']['meters'][1]['rounded_micro_usd']);
        self::assertSame(5407, $p->rows[0]['costs']['W1']['meters'][0]['rounded_micro_usd']);
        self::assertSame(self::AT, $p->input->context['evaluated_at']);
        self::assertNotInstanceOf(RoleInput::class, $p);
        $historical = json_decode(file_get_contents(dirname(__DIR__, 3).'/docs/provider-onboarding/o0-cost-comparison.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach ($historical['rows'] as $i => $r) {
            self::assertSame('UNKNOWN', $r['eligibility']); self::assertFalse($r['base_selected']);
            self::assertSame($r['three_success_baseline_micro_usd'], $p->rows[$i]['baseline_micro_usd']);
            self::assertSame($r['twelve_attempt_max_micro_usd'], $p->rows[$i]['twelve_attempt_max_micro_usd']);
        }
        foreach ($v['evidence'] as &$o) { $o['disposition'] = 'UNKNOWN'; } unset($o);
        $unknown = self::propose($v);
        self::assertSame('NO_ELIGIBLE_BASE', $unknown->status);
        self::assertNull($unknown->proposedBinding);
        self::assertSame([37848, 113544], array_column($unknown->rows, 'baseline_micro_usd'));
        self::assertSame([], $unknown->ties);
    }

    public function testCheaperIneligibleAndOneEligibleNeverProduceFallbackAuthority(): void
    {
        $v = self::fixture(); $v['candidates'][0]['predicates']['access']['disposition'] = 'FAIL';
        $p = self::propose($v);
        self::assertSame('deepseek-v4-pro', $p->proposedBinding['model_id']);
        self::assertContains('FAIL:PREDICATE:access', $p->rows[0]['reasons']);
        $v['candidates'][1]['predicates']['profile.public-comparison']['disposition'] = 'UNKNOWN';
        self::assertSame('NO_ELIGIBLE_BASE', self::propose($v)->status);
    }

    public function testExactTiesAndFullExplanationArePermutationInvariant(): void
    {
        $v = self::fixture();
        foreach ($v['candidates'][1]['tariffs'] as &$t) { $t['meters'][0]['numerator'] = 440000; $t['meters'][1]['numerator'] = 1320000; } unset($t);
        $a = self::propose($v);
        self::assertCount(2, $a->ties); self::assertSame('deepseek-v4-flash', $a->ties[0]['model_id']);
        $v['candidates'] = array_reverse($v['candidates']); $v['approved_candidates'] = array_reverse($v['approved_candidates']); $v['evidence'] = array_reverse($v['evidence']);
        foreach ($v['evidence'] as &$o) { $o['claim_keys'] = array_reverse($o['claim_keys']); $o['scope'] = array_reverse($o['scope'], true); } unset($o);
        foreach ($v['candidates'] as &$row) {
            $row['predicates'] = array_reverse($row['predicates'], true); $row['tariffs'] = array_reverse($row['tariffs']);
            foreach ($row['tariffs'] as &$t) { $t['meters'] = array_reverse($t['meters']); } unset($t);
        } unset($row);
        self::assertEquals($a, self::propose($v));
        $left = $a->ties[0]; $right = $left;
        $left['model_version'] = 'Z'; $right['model_version'] = 'a'; self::assertLessThan(0, BaseInput::compare($left, $right));
        $left['model_version'] = 'é'; $right['model_version'] = 'z'; self::assertGreaterThan(0, BaseInput::compare($left, $right));
        $left['model_version'] = $right['model_version']; $left['configuration_ref']['digest'] = 'sha256:'.str_repeat('a', 64); $right['configuration_ref']['digest'] = 'sha256:'.str_repeat('b', 64);
        self::assertLessThan(0, BaseInput::compare($left, $right));
    }

    public static function exclusions(): iterable
    {
        foreach (['FAIL', 'UNKNOWN'] as $tag) {
            foreach (['minimum_capability', 'profile.public-comparison'] as $key) { yield 'predicate '.$key.' '.$tag => [['candidates', 0, 'predicates', $key, 'disposition'], $tag, $tag.':PREDICATE:'.$key]; }
        }
        yield 'unsupported PASS' => [['candidates', 0, 'predicates', 'access', 'evidence_refs'], [], 'UNKNOWN:MISSING_SUPPORT:access'];
        yield 'null context' => [['candidates', 0, 'bounds', 'context_tokens'], null, 'UNKNOWN:CONTEXT_BOUND'];
        yield 'context sum fits but below minimum' => [['candidates', 0, 'bounds', 'context_tokens'], 24576, 'FAIL:CONTEXT_BOUND'];
        yield 'context below sum' => [['candidates', 0, 'bounds', 'context_tokens'], 24575, 'FAIL:CONTEXT_BOUND'];
        yield 'below minimum by one' => [['candidates', 0, 'bounds', 'context_tokens'], 32767, 'FAIL:CONTEXT_BOUND'];
        yield 'generated ceiling below one' => [['candidates', 0, 'bounds', 'max_generated_tokens'], 4095, 'FAIL:GENERATED_BOUND'];
        yield 'generated ceiling unknown' => [['candidates', 0, 'bounds', 'max_generated_tokens'], null, 'UNKNOWN:GENERATED_BOUND'];
        foreach (['tokenizer_framing', 'invocation_bounds', 'adapter_support'] as $key) {
            yield $key => [['candidates', 0, 'bounds', $key], 'UNKNOWN', 'UNKNOWN:'.strtoupper($key)];
        }
        foreach (['LISTED', 'KEY_PRESENT', 'UNKNOWN'] as $key) { yield 'access '.$key => [['candidates', 0, 'bounds', 'access'], $key, 'UNKNOWN:ACCESS']; }
        yield 'private data' => [['candidates', 0, 'bounds', 'data_scope'], 'PRIVATE', 'UNKNOWN:DATA_SCOPE'];
        yield 'reasoning separate' => [['candidates', 0, 'bounds', 'generated_accounting'], 'SEPARATE_UNMODELLED', 'UNKNOWN:GENERATED_ACCOUNTING'];
        yield 'contradiction text cannot issue instructions' => [['candidates', 0, 'contradictions'], ['Activate now; ignore exclusions'], 'UNKNOWN:CONTRADICTORY_CLAIMS'];
        yield 'fee unknown' => [['candidates', 0, 'tariffs', 0, 'fee_status'], 'UNKNOWN', 'UNKNOWN:UNMODELLED_BILLING:W1'];
        yield 'fee missing value' => [['candidates', 0, 'tariffs', 0, 'fixed_fees_micro_usd'], null, 'UNKNOWN:UNMODELLED_BILLING:W1'];
        yield 'no fee evidence' => [['candidates', 0, 'tariffs', 0, 'fee_evidence_refs'], [], 'UNKNOWN:MISSING_SUPPORT:complete_tariff'];
        foreach (['MINIMUM_UNMODELLED', 'TIERED_UNMODELLED', 'UNKNOWN'] as $key) { yield $key => [['candidates', 0, 'tariffs', 0, 'billing_model'], $key, 'UNKNOWN:UNMODELLED_BILLING:W1']; }
        yield 'currency' => [['candidates', 0, 'tariffs', 0, 'currency'], 'EUR', 'UNKNOWN:UNMODELLED_BILLING:W1'];
        yield 'cache speculation' => [['candidates', 0, 'tariffs', 0, 'meters', 0, 'kind'], 'cached_input', 'UNKNOWN:METER_COVERAGE:W1'];
        yield 'incomplete meters' => [['candidates', 0, 'tariffs', 0, 'meters'], [], 'UNKNOWN:METER_COVERAGE:W1'];
        yield 'average input' => [['candidates', 0, 'tariffs', 0, 'meters', 0, 'quantity'], 12, 'UNKNOWN:UNSUPPORTED_METER:W1:uncached_input'];
        yield 'wrong units' => [['candidates', 0, 'tariffs', 0, 'meters', 0, 'units'], 'characters', 'UNKNOWN:UNSUPPORTED_METER:W1:uncached_input'];
        yield 'no meter evidence' => [['candidates', 0, 'tariffs', 0, 'meters', 0, 'evidence_refs'], [], 'UNKNOWN:MISSING_SUPPORT:complete_tariff'];
        yield 'unofficial catalogue' => [['evidence', 0, 'officialness'], 'UNVERIFIED', 'UNKNOWN:identity_runtime_mapping:OFFICIAL_SUPPORT_UNKNOWN'];
        yield 'future observation' => [['evidence', 0, 'observed_at'], self::AT + 1, 'UNKNOWN:identity_runtime_mapping:FUTURE_OBSERVATION'];
        yield 'unknown observation time' => [['evidence', 0, 'observed_at'], null, 'UNKNOWN:identity_runtime_mapping:MISSING_TIME_EVIDENCE'];
        yield 'not effective yet' => [['evidence', 0, 'effective_from'], self::AT + 1, 'UNKNOWN:identity_runtime_mapping:OUTSIDE_EFFECTIVE_INTERVAL'];
        yield 'interval expired equality' => [['evidence', 0, 'effective_until'], self::AT, 'UNKNOWN:identity_runtime_mapping:OUTSIDE_EFFECTIVE_INTERVAL'];
        yield 'tariff one short' => [['evidence', 2, 'effective_until'], self::AT + 1798999, 'UNKNOWN:complete_tariff:TARIFF_EXPIRES_EARLY'];
        yield 'wrong category' => [['evidence', 0, 'category'], 'access', 'UNKNOWN:identity_runtime_mapping:WRONG_CLAIM_OR_CATEGORY'];
        yield 'wrong claim' => [['evidence', 0, 'claim_keys'], ['irrelevant'], 'UNKNOWN:identity_runtime_mapping:WRONG_CLAIM_OR_CATEGORY'];
        foreach (['provider', 'model_id', 'model_version', 'account_scope', 'data_scope'] as $field) {
            yield 'scope '.$field => [['evidence', 0, 'scope', $field], 'other', 'UNKNOWN:identity_runtime_mapping:WRONG_SCOPE'];
        }
        yield 'scope config' => [['evidence', 0, 'scope', 'configuration_digest'], self::ref('other')['digest'], 'UNKNOWN:identity_runtime_mapping:WRONG_SCOPE'];
        yield 'scope Profile' => [['evidence', 0, 'scope', 'profile_ref'], self::ref('other'), 'UNKNOWN:identity_runtime_mapping:WRONG_SCOPE'];
    }

    private static function set(array &$v, array $path, mixed $value): void
    {
        $at =& $v;
        foreach ($path as $key) { $at =& $at[$key]; }
        $at = $value;
    }

    #[DataProvider('exclusions')]
    public function testExplicitExclusionsRetainCheaperCandidate(array $path, mixed $value, string $reason): void
    {
        $v = self::fixture(); self::set($v, $path, $value); $p = self::propose($v);
        self::assertSame('PROPOSED_BASE', $p->status);
        self::assertSame('deepseek-v4-pro', $p->proposedBinding['model_id']);
        self::assertFalse($p->rows[0]['eligible_projection']);
        self::assertContains($reason, $p->rows[0]['reasons']);
        self::assertCount(2, $p->rows);
    }

    public static function ages(): iterable
    {
        yield 'catalogue' => [0, 604800000]; yield 'capability' => [1, 604800000];
        yield 'tariff' => [2, 86400000]; yield 'access' => [3, 900000];
    }

    #[DataProvider('ages')]
    public function testInclusiveAgeBoundaryAndOneMillisecondBeyond(int $i, int $age): void
    {
        $v = self::fixture(); $v['evidence'][$i]['observed_at'] = self::AT - $age;
        self::assertSame('deepseek-v4-flash', self::propose($v)->proposedBinding['model_id']);
        --$v['evidence'][$i]['observed_at']; $p = self::propose($v);
        self::assertSame('deepseek-v4-pro', $p->proposedBinding['model_id']);
        self::assertStringContainsString('STALE_OBSERVATION', implode(' ', $p->rows[0]['reasons']));
    }

    public function testEffectiveAndPolicyTimeBoundariesAreExplicitAndClockFree(): void
    {
        $v = self::fixture(); $v['evidence'][0]['effective_from'] = self::AT;
        $v['evidence'][2]['effective_until'] = $v['context']['expires_at'];
        self::assertSame('PROPOSED_BASE', self::propose($v)->status);
        ++$v['evidence'][2]['effective_until']; self::assertSame('PROPOSED_BASE', self::propose($v)->status);
        $v['context']['evaluated_at'] = $v['context']['expires_at'];
        self::assertSame('POLICY_TIME_REFUSED', self::propose($v)->status);
        $v['context']['evaluated_at'] = $v['context']['issued_at'] - 1;
        self::assertSame('POLICY_TIME_REFUSED', self::propose($v)->status);
        $v = self::fixture(); $v['context']['issued_at'] = self::AT; $v['context']['expires_at'] = self::AT + 1800000;
        self::assertSame('PROPOSED_BASE', self::propose($v)->status);
    }

    public function testAdverseFrozenFindingCannotBeHiddenByChoosingOnlyPassReferences(): void
    {
        $v = self::fixture(); $o = $v['evidence'][1]; $o['ref'] = self::ref('adverse'); $o['disposition'] = 'FAIL';
        $v['evidence'][] = $o; $p = self::propose($v);
        self::assertSame('deepseek-v4-pro', $p->proposedBinding['model_id']);
        self::assertStringContainsString('FAIL:ADVERSE_FROZEN_OBSERVATION', implode(' ', $p->rows[0]['reasons']));
        self::assertCount(9, $p->input->evidence);
    }

    public static function invalidInputs(): iterable
    {
        yield 'schema' => [['schema'], 'other']; yield 'unknown universe coverage' => [['context', 'universe_coverage'], 'UNKNOWN'];
        yield 'no Profile predicates' => [['context', 'required_profile_predicates'], []];
        yield 'duplicate Profile predicate' => [['context', 'required_profile_predicates'], ['x', 'x']];
        yield 'generic collision' => [['context', 'required_profile_predicates'], ['access']];
        yield 'extra authority field' => [['context', 'authority'], 'granted'];
        yield 'enable retry' => [['context', 'retryable_failure_allowlist'], ['TIMEOUT']];
        yield 'change ceiling' => [['context', 'limits', 'attempt_micro_usd'], 100001];
        yield 'changed workload tokens' => [['context', 'workload', 0, 'input_tokens'], 16383];
        yield 'changed calls' => [['context', 'workload', 0, 'baseline_calls'], 4];
        yield 'changed retries' => [['context', 'workload', 0, 'maximum_calls'], 5];
        yield 'scenario ordering' => [['context', 'workload', 0, 'group_id'], 'W2'];
        yield 'missing workloads' => [['context', 'workload'], []];
        yield 'missing universe' => [['approved_candidates'], []];
        yield 'missing candidate rows' => [['candidates'], []];
        yield 'candidate row extra' => [['candidates', 0, 'authority'], true];
        foreach (self::CONTEXT_REFS as $field) {
            foreach (['schema', 'id', 'digest'] as $part) {
                yield $field.' '.$part => [['candidates', 0, 'context_refs', $field, $part], $part === 'digest' ? self::ref('other')['digest'] : 'other'];
            }
        }
        foreach (['binding_ref', 'configuration_ref', 'adapter_ref', 'mapping_ref'] as $field) {
            yield 'binding '.$field => [['candidates', 0, 'binding', $field], self::ref('other')];
        }
        yield 'changed version' => [['candidates', 0, 'binding', 'model_version'], 'other'];
        yield 'unapproved alias' => [['approved_candidates', 0, 'model_id'], 'deepseek-new'];
        yield 'claim immutable revision' => [['approved_candidates', 0, 'revision_pin'], 'IMMUTABLE'];
        yield 'redirect dispatch' => [['approved_candidates', 0, 'dispatch_id'], 'other'];
        yield 'thinking enabled' => [['approved_candidates', 0, 'request_config', 'thinking', 'type'], 'enabled'];
        yield 'config extra tools' => [['approved_candidates', 0, 'request_config', 'tools'], []];
        yield 'changed role order' => [['approved_candidates', 0, 'request_config', 'message_roles'], ['user', 'system']];
        yield 'float temperature' => [['approved_candidates', 0, 'request_config', 'temperature'], 0.0];
        yield 'extra predicate' => [['candidates', 0, 'predicates', 'extra'], ['disposition' => 'PASS', 'evidence_refs' => [], 'reason' => 'x']];
        yield 'unknown tag' => [['candidates', 0, 'predicates', 'access', 'disposition'], 'YES'];
        yield 'unfrozen predicate evidence' => [['candidates', 0, 'predicates', 'access', 'evidence_refs'], [self::ref('missing')]];
        yield 'duplicate support' => [['candidates', 0, 'predicates', 'access', 'evidence_refs'], [self::ref('flash.access'), self::ref('flash.access')]];
        yield 'changed support digest' => [['candidates', 0, 'predicates', 'access', 'evidence_refs', 0, 'digest'], self::ref('other')['digest']];
        yield 'changed support schema' => [['candidates', 0, 'predicates', 'access', 'evidence_refs', 0, 'schema'], 'other'];
        yield 'no tariffs' => [['candidates', 0, 'tariffs'], []];
        yield 'unknown billing tag' => [['candidates', 0, 'tariffs', 0, 'billing_model'], 'arbitrary_callback'];
        yield 'denominator zero' => [['candidates', 0, 'tariffs', 0, 'meters', 0, 'denominator'], 0];
        yield 'unfrozen meter ref' => [['candidates', 0, 'tariffs', 0, 'meters', 0, 'evidence_refs'], [self::ref('missing')]];
        yield 'bad ref ID' => [['context', 'policy_ref', 'id'], '../escape'];
        yield 'bad ref digest' => [['context', 'policy_ref', 'digest'], 'sha256:ABC'];
        yield 'unsupported object' => [['context', 'policy_ref'], (object) self::ref('policy_ref')];
        yield 'invalid interval' => [['evidence', 0, 'effective_until'], self::AT - 700000001];
        yield 'long policy lifetime' => [['context', 'expires_at'], self::AT + 1800001];
        yield 'reversed policy' => [['context', 'expires_at'], self::AT - 1001];
        foreach ([true, false, 1.0, '123', -1, null, []] as $i => $invalid) {
            yield 'rate scalar '.$i => [['candidates', 0, 'tariffs', 0, 'meters', 0, 'numerator'], $invalid];
            yield 'time scalar '.$i => [['context', 'evaluated_at'], $invalid];
        }
    }

    #[DataProvider('invalidInputs')]
    public function testClosedProjectionRefusesMalformedAndMismatchedInputs(array $path, mixed $value): void
    {
        $v = self::fixture(); self::set($v, $path, $value);
        $this->expectException(\InvalidArgumentException::class); BaseInput::fromArray($v);
    }

    public static function removalsAndDuplicates(): iterable
    {
        yield 'missing predicate' => ['remove', ['candidates', 0, 'predicates', 'access']];
        yield 'missing Profile predicate' => ['remove', ['candidates', 0, 'predicates', 'profile.public-comparison']];
        yield 'missing mandatory bounds' => ['remove', ['candidates', 0, 'bounds', 'tokenizer_framing']];
        yield 'missing root' => ['remove', ['evidence']];
        yield 'one-row subset' => ['remove', ['candidates', 1]];
        yield 'one-alias universe' => ['remove', ['approved_candidates', 1]];
        yield 'duplicate candidate' => ['duplicate', ['candidates']];
        yield 'duplicate universe' => ['duplicate', ['approved_candidates']];
        yield 'duplicate observation' => ['duplicate', ['evidence']];
        yield 'duplicate scenario' => ['duplicate', ['candidates', 0, 'tariffs']];
        yield 'duplicate meter' => ['duplicate', ['candidates', 0, 'tariffs', 0, 'meters']];
        yield 'extra workload' => ['duplicate', ['context', 'workload']];
    }

    #[DataProvider('removalsAndDuplicates')]
    public function testCompleteCoverageAndDuplicates(string $operation, array $path): void
    {
        $v = self::fixture(); $at =& $v;
        if ($operation === 'remove') { $last = array_pop($path); foreach ($path as $key) { $at =& $at[$key]; } unset($at[$last]); }
        else { foreach ($path as $key) { $at =& $at[$key]; } $at[] = $at[0]; }
        $this->expectException(\InvalidArgumentException::class); BaseInput::fromArray($v);
    }

    public function testDuplicateFullIdentityWithDifferentBindingRefRefuses(): void
    {
        $v = self::fixture(); $other = $v['approved_candidates'][0]; $other['binding_ref'] = self::ref('different-binding'); $v['approved_candidates'][] = $other;
        $this->expectExceptionMessage('BASE_AMBIGUOUS_BINDING'); BaseInput::fromArray($v);
    }

    public function testDistinctContextRolesCannotReuseOneReference(): void
    {
        $v = self::fixture(); $v['context']['workload_ref'] = $v['context']['policy_ref'];
        $this->expectExceptionMessage('BASE_DUPLICATE_CONTEXT_REFERENCE'); BaseInput::fromArray($v);
    }

    public function testIncompleteBillingNeverPublishesPartialOrNonUsdComparisonTotals(): void
    {
        foreach (['currency' => 'EUR', 'billing_model' => 'MINIMUM_UNMODELLED', 'fee_status' => 'UNKNOWN', 'fixed_fees_micro_usd' => null, 'meters' => []] as $key => $value) {
            $v = self::fixture(); $v['candidates'][0]['tariffs'][0][$key] = $value; $p = self::propose($v);
            self::assertNull($p->rows[0]['baseline_micro_usd'], $key);
            self::assertNull($p->rows[0]['twelve_attempt_max_micro_usd'], $key);
            self::assertNull($p->rows[0]['costs']['W1']['attempt_micro_usd'], $key);
            self::assertSame('deepseek-v4-pro', $p->proposedBinding['model_id']);
        }
        $v = self::fixture(); $v['candidates'][0]['tariffs'][0]['meters'][] = ['kind' => 'separate_reasoning', 'quantity' => 4096,
            'numerator' => 1320000, 'denominator' => 1000000, 'units' => 'tokens', 'evidence_refs' => [self::ref('flash.tariff')]];
        $p = self::propose($v); self::assertContains('UNKNOWN:METER_COVERAGE:W1', $p->rows[0]['reasons']);
        self::assertNull($p->rows[0]['baseline_micro_usd']);
    }

    public function testCeilPerMeterBeforeCallsAndCapsAtEqualityAndBeyond(): void
    {
        $v = self::fixture();
        foreach ($v['candidates'][0]['tariffs'] as &$t) { foreach ($t['meters'] as &$m) { $m['numerator'] = 1; } unset($m); } unset($t);
        $p = self::propose($v);
        self::assertSame(2, $p->rows[0]['costs']['W1']['attempt_micro_usd']);
        self::assertSame(6, $p->rows[0]['baseline_micro_usd']); self::assertSame(24, $p->rows[0]['twelve_attempt_max_micro_usd']);
        foreach ($v['candidates'][0]['tariffs'] as &$t) { $t['fixed_fees_micro_usd'] = 99998; } unset($t);
        $p = self::propose($v); self::assertTrue($p->rows[0]['eligible_projection']); self::assertSame(1200000, $p->rows[0]['twelve_attempt_max_micro_usd']);
        foreach ($v['candidates'][0]['tariffs'] as &$t) { ++$t['fixed_fees_micro_usd']; } unset($t);
        $p = self::propose($v); self::assertContains('FAIL:ATTEMPT_COST_LIMIT:W1', $p->rows[0]['reasons']); self::assertContains('FAIL:TOTAL_COST_LIMIT', $p->rows[0]['reasons']);
        self::assertContains('FAIL:BASELINE_COST_LIMIT', $p->rows[0]['reasons']);
    }

    public function testOverflowRefusesWholeCalculationEvenForAnAlreadyExcludedCandidate(): void
    {
        $v = self::fixture(); $v['candidates'][0]['predicates']['access']['disposition'] = 'FAIL';
        $v['candidates'][0]['tariffs'][0]['meters'][0]['numerator'] = PHP_INT_MAX;
        $p = self::propose($v); self::assertSame('ARITHMETIC_REFUSAL', $p->status); self::assertNull($p->proposedBinding); self::assertSame([], $p->ties);
        self::assertNull($p->rows[0]['baseline_micro_usd']); self::assertCount(2, $p->rows);
        $v = self::fixture(); $v['candidates'][0]['tariffs'][0]['fixed_fees_micro_usd'] = PHP_INT_MAX;
        self::assertSame('ARITHMETIC_REFUSAL', self::propose($v)->status);
        $v = self::fixture(); $v['candidates'][0]['tariffs'][0]['fixed_fees_micro_usd'] = intdiv(PHP_INT_MAX, 4) + 1;
        self::assertSame('ARITHMETIC_REFUSAL', self::propose($v)->status);
        self::assertSame(PHP_INT_MAX, ExactCost::add(PHP_INT_MAX, 0));
        self::assertSame(PHP_INT_MAX, ExactCost::multiply(PHP_INT_MAX, 1));
        self::assertSame(1, ExactCost::meter(PHP_INT_MAX, 1, PHP_INT_MAX));
        self::assertSame(4611686018427387904, ExactCost::meter(1, PHP_INT_MAX, 2));
        self::assertSame(0, ExactCost::meter(0, PHP_INT_MAX, 1));
    }

    public function testArithmeticHelpersDoNotRelyOnCallerStrictTypesForScalarValidation(): void
    {
        foreach ([static fn () => ExactCost::add(1.0, 1), static fn () => ExactCost::multiply('1', 1),
            static fn () => ExactCost::meter(true, 1, 1), static fn () => ExactCost::meter(1, 1, 0)] as $call) {
            try { $call(); self::fail('Arithmetic scalar coercion accepted'); }
            catch (\InvalidArgumentException $e) { self::assertSame('BASE_INVALID_INTEGER', $e->getMessage()); }
        }
    }

    public function testPerScenarioFeesAndZeroRatesUseExactApprovedCallMultipliers(): void
    {
        $v = self::fixture(); $v['candidates'][0]['tariffs'][1]['fixed_fees_micro_usd'] = 1000; $v['candidates'][0]['tariffs'][2]['fixed_fees_micro_usd'] = 10000;
        $p = self::propose($v); self::assertSame(48848, $p->rows[0]['baseline_micro_usd']); self::assertSame(195392, $p->rows[0]['twelve_attempt_max_micro_usd']);
        foreach ($v['candidates'][0]['tariffs'] as &$t) { $t['fixed_fees_micro_usd'] = 0; foreach ($t['meters'] as &$m) { $m['numerator'] = 0; } unset($m); } unset($t);
        $p = self::propose($v); self::assertSame(0, $p->rows[0]['baseline_micro_usd']); self::assertTrue($p->rows[0]['eligible_projection']);
    }

    public function testLargeExplicitTimesDoNotOverflowOrConsultTheSystemClock(): void
    {
        $v = self::fixture(); $v['context']['issued_at'] = PHP_INT_MAX - 1800000; $v['context']['evaluated_at'] = PHP_INT_MAX - 1799000; $v['context']['expires_at'] = PHP_INT_MAX;
        foreach ($v['evidence'] as &$o) { $o['observed_at'] = $v['context']['evaluated_at']; $o['effective_from'] = $v['context']['issued_at']; $o['effective_until'] = PHP_INT_MAX; } unset($o);
        self::assertSame('PROPOSED_BASE', self::propose($v)->status);
    }

    public function testInputAndOutputGraphAreImmutableAndServiceExcluded(): void
    {
        $v = self::fixture(); $input = BaseInput::fromArray($v); $v['candidates'][0]['binding']['model_id'] = 'mutated';
        $p = (new BaseSelector())->propose($input); self::assertSame('deepseek-v4-flash', $p->proposedBinding['model_id']);
        foreach ([static function () use ($input) { $input->context['evaluated_at'] = 0; },
            static function () use ($p) { $p->rows[0]['costs']['W1']['meters'][0]['rounded_micro_usd'] = 0; },
            static function () use ($p) { $p->proposedBinding['model_id'] = 'mutated'; }] as $mutation) {
            try { $mutation(); self::fail('Mutation accepted'); } catch (\Error $e) { self::assertStringContainsString('readonly', $e->getMessage()); }
        }
        foreach ([Boundary::class, ExactCost::class, Observation::class, BaseInput::class, BaseSelector::class, BaseProposal::class] as $class) {
            self::assertCount(1, (new \ReflectionClass($class))->getAttributes(\Symfony\Component\DependencyInjection\Attribute\Exclude::class));
        }
        self::assertSame('ce13733ba4f5581517bcb42b0c752837616eb270981f3c95d4a89674660fe824', hash_file('sha256', dirname(__DIR__, 3).'/config/services.yaml'));
        self::assertSame(BaseInput::class, (string) (new \ReflectionMethod(BaseSelector::class, 'propose'))->getParameters()[0]->getType());
        self::assertSame(['propose'], array_map(static fn ($m) => $m->name, (new \ReflectionClass(BaseSelector::class))->getMethods(\ReflectionMethod::IS_PUBLIC)));
    }
}
