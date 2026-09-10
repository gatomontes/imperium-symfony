> Current target-selection amendment: [medium-capacity rule](provider-onboarding-assignment-selection.md)
> fixes assessed-assignment terms and response-to-view derivation for v1.4.

# O0 bounded cognition retry amendment — v1.3.1 draft

**UNIMPLEMENTED.** Owner retry count and dollar ceiling are recorded in
[the decision record](../docs/provider-onboarding-selected-decisions.md).
This amendment extends the [v1.2 continuation draft](provider-onboarding-continuation.md)
with finite attempts. It preserves exact duplicate-command recognition, one-use
authority, shared budgets and unknown-outcome fencing. No signed historical
schema or executable source is changed.

## Fixed bounds and identity

Each of W1, W2 and W3 is one assessment group with attempts numbered 0 through 3:
one initial attempt and at most three retries. Maximum twelve cognition attempts,
concurrency one, $0.10 per attempt and $1.20 total. Success ends that group's
attempts immediately. Unused attempts are not dispatched or consumed. Exhausting
the fourth attempt without success terminates the group and blocks dependent work.
Every dispatched attempt counts, including a confirmed failed attempt. No hidden
SDK, HTTP-client or application retry is permitted within an attempt.

Every attempt has a separate immutable step ID, one-use effect slot, command ID,
claim, response evidence and reservation. An identical command ID/fingerprint
only recognizes the original result; it never consumes a retry allowance. A new
command ID alone is not retry authority. Step/slot identities are predeclared in
the original policy and remain global across sequences as F1/F2 requires.
Retries keep the same exact holder, provider, model, configuration, workload and
semantic assessment input. Attempt-specific request correlation is separately
bound. A changed model, prompt/rubric, permission, budget or expired policy requires
its actual new authorization, not a retry slot. Credential resolution follows the
original custody/currentness policy and supplies no scope expansion.

## Closed dependency rules for the proposed step graph

The proposed step object becomes exactly
`{step_id, action, effect_slot_id, depends_on, input_refs, run_condition}`.
This revises an unimplemented draft, not an installed schema. `run_condition`
is exactly one of:

- `{kind: after_success}`: existing F1 behavior; all dependencies succeeded.
- `{kind: initial_assessment, group_id}`: start attempt 0 once all prior required
  assessment groups and ordinary dependencies succeeded.
- `{kind: retry_after_confirmed_failure, group_id, prior_attempt_step_id}`:
  require the immediately preceding attempt's retained retryable failure finding,
  no success in this group, and all ordinary dependencies still valid.

The policy adds finite `assessment_groups`, each exactly
`{group_id, attempt_step_ids, requires_success_of, workload_ref}`. attempt_step_ids
contains four unique ordered steps with EXECUTE_ASSESSMENT and separate
AUTHORIZE_BOOTSTRAP_ASSESSMENT slots. Group dependencies form an acyclic graph.
For this scope W2 follows successful W1; W3 follows successful W2. The finite graph
resolves group success to the first retained valid successful attempt, so a later
group does not require every unused retry to succeed. Exact policy preparation
must reject duplicate slots, missing/extra attempts, cycles, cross-group prior
references and non-immediate retry predecessors. No arbitrary condition language.

## Attributable failure classification

A proposed immutable `imperium.bootstrap-attempt-outcome/v1` record has exactly
`{schema, id, instance_id, sequence_ref, group_id, attempt_step_id, claim_ref,
response_refs, classification, reason_code, evidence_refs, usage_ref,
retry_policy_ref, observed_at, record_digest}`. Classification is one of
`SUCCEEDED`, `CONFIRMED_RETRYABLE_FAILURE`, `TERMINAL_FAILURE`, `OUTCOME_UNKNOWN`.
usage_ref identifies retained settled usage or the original unsettled exposure
record; it never treats absent usage as zero. The record digest uses the established
canonical rules. No model-generated text
or caller assertion can classify a failure as safe to retry.

The fixed source-specific verifier must establish a final attributable outcome,
complete required usage/exposure evidence and an enumerated provider-supported
retry reason before publishing CONFIRMED_RETRYABLE_FAILURE. The provider appendix
must freeze the exact admitted response/provenance predicates and retryable reason
allowlist. Until supplied, the allowlist is empty and retries refuse. An HTTP
status, SDK exception or absence of a response alone establishes neither no effect
nor no billing. Local timeout remains uncertain unless sufficient original
completion evidence later resolves it under the accepted protocol.

Invalid/revoked/expired authority, unsupported configuration, failed policy checks
and exhausted budget are not retryable provider failures. They refuse or terminate
without consuming later retry slots. A lost/uncertain dispatch cannot be retried,
reissued, refunded or bypassed by a new sequence, command or policy ID. Evidence-only
resume may reconcile the original outcome; only a new authorized advance can then
consume an eligible unused retry slot. No automatic extra time or policy renewal.

## Shared budget, completion and application

Under the owning aggregate lock, revalidate the exact prior failure finding,
current authority/holder, unused retry slot and remaining shared budget. Reserve
that attempt's maximum exposure and consume its unique authority atomically before
external work. Never reuse or unconsume the failed attempt's claim/nonce/slot.
Settled actual usage and all unsettled maxima remain attributable and count
against the same budget. The retry allowance does not itself prove cancellation,
settlement, zero billing or release of an old reservation.

The final assessment selects one valid successful outcome per W-group and retains
all failed/unknown attempt lineage. Any required group without success blocks
assignment application. D2-A still permits at most one whole-set application;
there are no assignment retries that repeat an already committed application.
Receipt recognition and atomic application remain as in F1/F2.

For deterministic cost comparison, use the same approved W1/W2/W3 workload and
four-attempt ceiling for every candidate, with exact conservative meters. Show
both the three-successful-call baseline and twelve-attempt maximum. Do not claim
all candidates fit $0.10/$1.20 without retained tariffs and token evidence.

## Targeted validation required later

The next offline closure checks this amended graph and authority/resource bindings.
Implementation must prove immediate success skips retries; three confirmed safe
failures followed by success; fourth failure terminates; unknown outcome fences;
command replay does not retry; stale/revoked authority refuses; concurrent retries
cannot consume one slot twice; all failures count against the shared ceiling; and
resume never dispatches. This document contains no executed runtime proof.
B1, DEFER_ENROLLMENT and all five false operational flags remain unchanged.

## Closure of group-result and slot dependencies — v1.3.1

This author clarification has no independent acceptance. The prior v1.2 F1/F2
review does not cover it. The [public preparation template](../docs/provider-onboarding/o0-public-preparation.json)
is deliberately non-runnable; its unresolved-ref descriptors are not runtime refs.

Ordinary step `depends_on` and effect-slot `depends_on` contain successful step
IDs only. A retry predecessor is carried exclusively by run_condition, never by
those success lists. A slot belongs to exactly one effectful step, has the same
ordinary dependency list, and has no independent dispatch interface. Admission
checks BOTH the owning step's run_condition/group prerequisites and the slot's
terms/currentness. A slot cannot bypass its owning failed/unknown prerequisite.

For v1.3, `input_refs` is a finite ordered list of closed tagged selectors:
`{kind: public_ref, ref}` with a genuine D2 public ref, or
`{kind: group_success_result, group_id}`. A group selector can refer only to a
strict predecessor in assessment_groups.requires_success_of's transitive closure;
application may reference its explicitly required groups. Resolve it to the first
retained valid successful outcome in the named group's ordered attempt list.
No success means not ready; multiple success outcomes or an unresolved earlier
attempt mean integrity refusal. Bind that original outcome and resulting content
digests before admission. At attempt 0, freeze the complete group's resolved
semantic input bytes/digest, holder and configuration; retries compare against
that frozen digest. Do not resolve a newer catalogue, result, Profile or prompt.

The assessed_assignment_set terms rule is exactly
`{kind, permitted_tuple_set_ref, result_group_ids, required_predicates_ref,
expected_assignments_ref, selection_rule_ref}`. It replaces v1.2's single result_step_id in this draft.
result_group_ids is exactly [W1,W2,W3] in this selected scope. Each resolves as
above; the deterministic assessment view is derived from all three selected
outcomes and retains every attempted failure/unknown in lineage. It is derived
local evidence, not a thirteenth cognition call. Its digest is the application
receipt's result_ref. Application has the ordinary prerequisites plus this terms
rule; it cannot run merely because the last attempted step terminated. Required
predicates and complete Courtthane/Locksmith tuple set must pass, the expected
assignment generations must still match, and one whole-set slot is consumed once.
A failed or unknown group never constitutes successful application input.

The response verifier, rather than model text, establishes successful transport,
complete attributable usage and schema/rubric validity. A schema-valid assessment
reporting no eligible candidates can complete its group but cannot pass the
application predicates. Timeout/transport loss/missing required usage remains
OUTCOME_UNKNOWN. A final attributable nonconforming result is terminal unless an
exact admitted retry predicate proves otherwise. Terminal status never invents
zero charge: preserve unsettled exposure independently of progression status.
The [DeepSeek appendix](../docs/provider-onboarding-deepseek-appendix.md) freezes
an EMPTY safe-retry allowlist for this evidence snapshot. Four reserved slots
are a ceiling, not an assertion that a retry is currently admissible.

Claims/outcomes/commands stay separate. Record outcome and usage resolution
atomically with completion evidence under the same aggregate lock. Do not publish
a success view while response attribution, usage or lineage remains disputed.
No generic FC claim schema or historic unknown-outcome boundary is amended.

The D2 typed subtree additionally retains attempt_outcomes, group_inputs and
assessment_views under the same journal/lock. Outcomes use the exact schema above.
Group-input records use common header H with body exactly
`{policy_ref, group_id, holder_ref, configuration_ref, workload_ref,
resolved_input_refs, semantic_input_digest}`. The first admitted attempt freezes
this record atomically; all later attempts reference and compare it. Assessment
views use H with body exactly `{policy_ref, group_outcome_refs, lineage_refs,
predicate_results, permitted_assignment_set_ref, selection_rule_ref, selection_evidence}`. group_outcome_refs is in
[W1,W2,W3] order; lineage_refs includes every actually attempted outcome in declared
group/attempt order, without fabricating receipts for skipped slots. All refs are
genuine D2 refs. Derived views may expose a proposed tuple set only after exact
predicate validation; a missing permitted set prevents application. H excludes its
own digest and contains no self-referential commit/receipt field. These schemas
are proposals requiring implementation and independent review.
