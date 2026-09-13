<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Console;

use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,StrictJson};
use App\Imperium\Runtime\Onboarding\Ledger\{CommandLedger,LedgerState,StepReadiness,CompletionResolver,AssessmentGroups,BudgetAssociation};
use App\Imperium\Runtime\Onboarding\Augur\Holder;
use App\Imperium\Runtime\Onboarding\Assignment\{ApplicationHistory,AssignmentRule};

/** Only retained public originals are observed. No adapter, credential or producer port. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class Projection
{
    public function __construct(private CommandLedger $ledger) {}

    public function status(string $sequence, ?array $operation = null): array
    {
        R::id($sequence);
        return $this->ledger->store->journal->inspectExisting(function(array $frame) use ($sequence,$operation): array {
            return StrictJson::within(function() use ($frame,$sequence,$operation): array {
                $s = $this->ledger->store->state($frame['state']);
                $seq = $s['sequences'][LedgerState::key('sequence',[$this->ledger->store->instance,$sequence])] ?? null;
                if ($seq === null) { throw new \RuntimeException('SEQUENCE_NOT_FOUND'); }
                $policy = $this->ledger->store->lookup($s,$seq['registration']['body']['policy_ref']);
                $out = $this->project($frame,$s,$policy,$sequence,$seq);
                if ($operation !== null) {
                    foreach (['command_id','mode','result_ref'] as $field) { $out[$field] = $operation[$field]; }
                    $out['effects']['new_effects_this_command'] = $operation['effects']['new_effects_this_command'];
                    if ($operation['status'] === 'OUTCOME_UNKNOWN' && $out['status'] !== 'REFUSED') {
                        $out['status'] = 'OUTCOME_UNKNOWN';
                        $out['reason_codes'] = ['OUTCOME_UNKNOWN'];
                        $out['next_action'] = self::next('RECOGNIZE_EVIDENCE',null,'Recognize retained evidence; do not dispatch again.');
                    }
                }
                $this->baseExplanation($s,$policy,$out);
                return $out;
            });
        });
    }

    public function preview(array $q): array
    {
        return $this->ledger->store->journal->inspectExisting(function(array $frame) use ($q): array {
            return StrictJson::within(function() use ($frame,$q): array {
                $store = $this->ledger->store;
                $s = $store->state($frame['state']);
                $head = ['generation'=>$frame['generation'],'digest'=>$frame['record_digest']];
                R::require($q['instance_id'] === $store->instance,'FOREIGN_RECORD');
                $key = LedgerState::key('command',[$store->instance,$q['sequence_id'],$q['command_id']]);
                if (isset($s['commands'][$key])) {
                    // Mode is part of command identity. A preview cannot impersonate an advance replay.
                    R::require(R::same($s['commands'][$key]['request'],$q),'COMMAND_CONFLICT');
                }
                R::require(R::same(CommandLedger::head($q['expected_head']),$head),'STALE_HEAD');
                $policy = $this->ledger->policy($s,$q['policy_ref']);
                $seq = $s['sequences'][LedgerState::key('sequence',[$store->instance,$q['sequence_id']])] ?? null;
                $out = $this->project($frame,$s,$policy,$q['sequence_id'],$seq);
                $out['command_id'] = $q['command_id']; $out['mode'] = 'preview';
                try {
                    R::require(isset($s['commands']) && count($s['commands']) < 4096,'LIMIT_EXCEEDED');
                    if ($seq === null) {
                        R::require($q['step_id'] === null && $q['predecessor_ref'] === null,'SEQUENCE_REGISTRATION_REQUIRED');
                        R::require(count($s['sequences']) < 256,'LIMIT_EXCEEDED');
                        foreach ($s['sequences'] as $prior) { R::require(!R::same($prior['registration']['body']['policy_ref'],R::reference($policy)),'SEQUENCE_ALREADY_REGISTERED'); }
                        foreach ($q['evidence_refs'] as $ref) { $store->checkSource($s,$ref); }
                        $budget = BudgetAssociation::resolve($store,$frame['state'],$policy);
                        foreach ($s['budget_bindings'] as $prior) { R::require($prior['record']['body']['budget_identity'] === $budget['identity'],'INDEPENDENT_BUDGET_PROOF_MISSING'); }
                        $out['next_action'] = self::next('REGISTER_SEQUENCE',null,'Submit one explicit advance to register this policy and budget.');
                    } else {
                        R::require(R::same($q['predecessor_ref'],$seq['head']),'STALE_PREDECESSOR');
                        R::require(R::same($seq['registration']['body']['policy_ref'],R::reference($policy)),'SEQUENCE_POLICY');
                        R::require(R::same($seq['registration']['body']['initial_evidence_refs'],$q['evidence_refs']),'FROZEN_EVIDENCE');
                        $previous = LedgerState::command($s,$seq['head']);
                        if ($previous['result']['step_id'] !== null) { LedgerState::step($s,$policy,$previous['result']['step_id']); }
                        R::require($q['step_id'] !== null,'STEP_REQUIRED');
                        R::require($s['source_fences'] === [],'OUTCOME_UNKNOWN');
                        $this->ready($s,$policy,$q['step_id']);
                        if (!in_array($out['status'],['REFUSED','OUTCOME_UNKNOWN'],true)) {
                            $out['next_action'] = self::next('ADVANCE_STEP',$q['step_id'],'Public prerequisites are present. An explicit advance rechecks current producer, custody and resource prerequisites.');
                            $this->proposal($s,$policy,$out);
                        }
                    }
                } catch (\Throwable $error) { $out = PublicResult::refusal($error,$out); }
                $this->baseExplanation($s,$policy,$out);
                return $out;
            });
        });
    }

    private function project(array $frame,array $s,array $policy,string $sequence,?array $seq): array
    {
        $store = $this->ledger->store;
        $out = PublicResult::empty($sequence,null,'status');
        $out['head'] = ['generation'=>$frame['generation'],'digest'=>'sha256:'.$frame['record_digest']];
        $out['sequence_head'] = $seq['head'] ?? null;
        $out['evidence_refs'] = [PublicResult::ref(R::reference($policy))];
        $facts = &$out['facts']; $effects = &$out['effects'];
        $configurations = array_column($policy['body']['candidate_bindings'],'configuration_ref');
        foreach ($configurations as $ref) { $store->lookup($s,$ref); }
        $facts['configuration'] = PublicResult::fact('configured',$configurations);
        $steps = [];
        foreach ($s['steps'] as $entry) {
            if ($entry['key'][1] !== $policy['record_digest']) { continue; }
            $steps[$entry['key'][2]] = $entry;
        }
        $accessPending = false; $assessmentPending = false; $unknown = false; $terminal = false;
        $effects['exposure'] = array_fill_keys(['calls','input_tokens','output_tokens','cost_microusd','milliseconds'],0);
        foreach ($s['claims'] as $claim) {
            if ($claim['record']['body']['sequence_ref']['sequence_id'] !== $sequence) { continue; }
            $ref = R::reference($claim['record']); $effects['claim_refs'][] = PublicResult::ref($ref);
            // A consumed custody checkpoint proves only a retained presence observation.
            if (count($claim['custody']) >= 3) { $facts['credential'] = PublicResult::fact('present',[R::reference($claim['custody'][2])]); }
            foreach ($claim['settled'] ?? $claim['maximum'] as $meter=>$amount) {
                R::require($effects['exposure'][$meter] <= PHP_INT_MAX-$amount,'EXPOSURE_OVERFLOW');
                $effects['exposure'][$meter] += $amount;
            }
            $access = $claim['record']['body']['authority_source']['kind'] === 'access';
            if ($claim['settled'] === null) {
                if (count($claim['custody']) < 4) { $unknown = true; }
                if ($access) { $accessPending = true; $facts['authentication'] = PublicResult::fact(count($claim['custody']) < 4?'unknown':'pending',[$ref]); }
                else { $assessmentPending = true; $facts['assessment'] = PublicResult::fact(count($claim['custody']) < 4?'unknown':'pending',[$ref]); }
            } elseif ($access) { $facts['authentication'] = PublicResult::fact('verified',[R::reference($claim['custody'][4])]); }
        }
        if (isset($steps['select-base']['completion'])) {
            $facts['base_selection'] = PublicResult::fact('selected',$steps['select-base']['completion']['body']['result_refs']);
        }
        foreach ($s['bindings'] as $entry) {
            if (!R::same($entry['record']['body']['policy_ref'],R::reference($policy))) { continue; }
            $ref = R::reference($entry['record']);
            try { Holder::current($store,$s,$ref); $facts['augur_authority'] = PublicResult::fact('current',[$ref]); }
            catch (\Throwable $error) { $facts['augur_authority'] = PublicResult::fact('stale',[$ref]); $out['reason_codes'][] = ReasonCodes::public($error); }
        }
        $outcomes = [];
        foreach ($s['attempt_outcomes'] as $outcome) {
            if ($outcome['sequence_ref']['policy_digest'] !== $policy['record_digest']) { continue; }
            $outcomes[] = ['id'=>$outcome['id'],'digest'=>$outcome['record_digest']];
            if ($outcome['classification'] === 'TERMINAL_FAILURE') { $terminal = true; }
        }
        $allGroups = true;
        foreach ($policy['body']['assessment_groups'] as $group) {
            try { AssessmentGroups::success($s,$policy,$group['group_id']); }
            catch (\Throwable) { $allGroups = false; }
        }
        if ($allGroups) { $facts['assessment'] = PublicResult::fact('retained',$outcomes); }
        foreach (ApplicationHistory::ordered($s) as $application) {
            if (!R::same($application['receipt']['body']['policy_ref'],R::reference($policy))) { continue; }
            $ref = R::reference($application['receipt']); $effects['assignment_receipt_refs'][] = PublicResult::ref($ref);
            $facts['assignment'] = PublicResult::fact('applied',[$ref]);
        }
        // Historical receipt facts survive expiry. Current usability remains separately checked.
        try { $store->checkSource($s,R::reference($policy)); }
        catch (\Throwable $error) { return PublicResult::refusal($error,$out); }
        if ($terminal) { return PublicResult::refusal(new \RuntimeException('FINAL_NONCONFORMING_RESULT'),$out); }
        if ($unknown) {
            $out['status'] = 'OUTCOME_UNKNOWN'; $out['reason_codes'] = ['OUTCOME_UNKNOWN'];
            $out['next_action'] = self::next('RECOGNIZE_EVIDENCE',null,'Recognize retained evidence; do not dispatch again.'); return $out;
        }
        if ($facts['augur_authority']['state'] === 'stale') {
            $out['status'] = 'MISSING_AUGUR_AUTHORITY';
            $out['next_action'] = self::next('RESOLVE_AUGUR_AUTHORITY',null,'Retained assignments remain historical; resolve stale Augur prerequisites through their owner.'); return $out;
        }
        if ($facts['assignment']['state'] === 'applied') {
            try { CompletionResolver::step($store,$s,$policy,'apply-assignments'); }
            catch (\Throwable $error) { return PublicResult::refusal($error,$out); }
            $out['status'] = 'ASSIGNMENT_APPLIED';
            $out['next_action'] = self::next('READ_SETTINGS',null,'The assignment receipt is retained. Current use requires the persistent settings owner to revalidate it.'); return $out;
        }
        $out['status'] = match (true) {
            $accessPending || $assessmentPending => 'RESULT_PENDING',
            $allGroups => 'RESULT_PENDING',
            $facts['augur_authority']['state'] === 'current' => 'ASSESSMENT_AUTHORIZED',
            $facts['base_selection']['state'] === 'selected' => 'MISSING_AUGUR_AUTHORITY',
            $facts['authentication']['state'] === 'verified' => 'AUTHENTICATION_PENDING',
            default => 'CONFIGURED',
        };
        if (!$allGroups && !$assessmentPending && $facts['augur_authority']['state'] === 'current') {
            // Authority is established only by a currently ready assessment slot.
            $out['status'] = 'MISSING_AUGUR_AUTHORITY';
        }
        if ($seq !== null && !$accessPending && !$assessmentPending) {
            foreach ($policy['body']['steps'] as $step) {
                if (isset($steps[$step['step_id']])) { continue; }
                try {
                    $this->ready($s,$policy,$step['step_id']);
                    $out['next_action'] = self::next('ADVANCE_STEP',$step['step_id'],'Submit one explicit advance; its owner rechecks dynamic prerequisites.');
                    if (in_array($step['run_condition']['kind'],['initial_assessment','retry_after_confirmed_failure'],true)) { $facts['assessment'] = PublicResult::fact('authorized',[R::reference($policy)]); $out['status'] = 'ASSESSMENT_AUTHORIZED'; }
                    break;
                } catch (\Throwable) { /* Only owner-validated candidates may be suggested. */ }
            }
        }
        if ($accessPending || $assessmentPending) {
            $out['next_action'] = self::next('RECOGNIZE_EVIDENCE',null,'A validated response is pending retained completion evidence; use evidence-only resume.');
        }
        if ($allGroups) { $this->proposal($s,$policy,$out); }
        return $out;
    }

    private function ready(array $s,array $policy,string $id): void
    {
        R::require($s['source_fences'] === [],'OUTCOME_UNKNOWN');
        $step = StepReadiness::ready($this->ledger->store,$s,$policy,$id);
        R::require(!isset($s['steps'][LedgerState::key('step',[$this->ledger->store->instance,$policy['record_digest'],$id])]),'STEP_ALREADY_CONSUMED');
        if ($step['effect_slot_id'] !== null) {
            [$authority,$slot,$facts] = $this->ledger->authority($s,$policy,$step);
            CompletionResolver::resolve($this->ledger->store,$s,$policy,$facts['obligations']);
            R::require(!isset($s['slots'][LedgerState::key('slot',[$this->ledger->store->instance,$policy['record_digest'],$slot['slot_id']])]),'SLOT_ALREADY_CONSUMED');
            $ak = $authority['kind'] === 'signed_act'
                ? [$this->ledger->store->instance,$facts['admission']['envelope']['payload']['trust_fingerprint'],$facts['admission']['envelope']['payload']['nonce']]
                : [$this->ledger->store->instance,$policy['record_digest'],$slot['slot_id']];
            foreach ($s['slots'] as $used) { R::require(!R::same($used['authority_key'],$ak),'AUTHORITY_ALREADY_CONSUMED'); }
        }
    }

    private function proposal(array $s,array $policy,array &$out): void
    {
        if ($out['facts']['assessment']['state'] !== 'retained' || $out['facts']['assignment']['state'] === 'applied') { return; }
        try {
            $derived = AssignmentRule::derive($s,$policy,AssignmentRule::slot($policy),fn(array $ref): array => $this->ledger->store->checkSource($s,$ref));
            $set = $derived['selected']['set'];
            $out['facts']['assignment'] = PublicResult::fact('proposed',[R::reference($set)]);
            $out['next_action']['explanation'] .= ' Proposed whole assignment set: '.json_encode($derived['selected']['assignments'],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES).'. No settings are changed by this observation.';
        } catch (\Throwable $error) {
            $out['facts']['assignment'] = PublicResult::fact('blocked');
            $out = PublicResult::refusal($error,$out);
        }
    }

    private static function next(string $code,?string $step,string $explanation): array
    { return ['code'=>$code,'step_id'=>$step,'explanation'=>$explanation]; }

    private function baseExplanation(array $s,array $policy,array &$out): void
    {
        if ($out['facts']['base_selection']['state'] !== 'selected') { return; }
        foreach ($policy['body']['candidate_bindings'] as $binding) {
            if (in_array(PublicResult::ref($binding['binding_ref']),$out['facts']['base_selection']['refs'],true)) {
                $out['next_action']['explanation'] .= ' Retained initial Augur base: '.json_encode($binding,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES).'.';
            }
        }
    }
}
