# O0 target assignment selection — v1.4

UNIMPLEMENTED CONTRACT. This amendment resolves A1 using the owner's instruction:
“If many options fit... choose the medium capacity.” It governs Courtthane and
formation Locksmith settings only. The initial Augur base still uses the existing
least-cost-eligible base policy. No provider facts, capacity ranking or live
assignment are established by this document.

The [decision record](../docs/provider-onboarding-medium-capacity-decision.md)
distinguishes the owner instruction from the deterministic conventions used below.
This amendment supersedes the earlier unspecified selection inside
assessed_assignment_set and the recommendation to refuse merely because several
fitting sets exist. Historical review findings/approvals remain unchanged.

## Capacity and fixed selection rule

Capacity means Augur's comparative assessment of capability for the exact target
Profile, using only the frozen admitted evidence and its declared criteria. It is
not inferred from price, provider marketing names, context-window size alone or
unverified parameter counts. Courtthane and Locksmith can have different orders.
There is no claim that Flash or Pro occupies a particular tier before assessment.

The policy binds an immutable selection_rule_ref to H record schema
`imperium.bootstrap-assignment-selection-rule/v1`. Its body is exactly the
runtime_body in [the rule preparation](../docs/provider-onboarding/o0-assignment-selection-rule.json).
The fields/values are fixed for this version; unknown algorithms/keys refuse.
This public template is not an admitted H record. Future policy admission resolves
the exact original rule and digest, not the newest file at a mutable path.

For each target independently:

1. Validate all W1/W2/W3 responses, selected successful outcomes, original evidence,
   exact candidate/Profile identities and mandatory predicates as before. Candidate
   row order grants no preference. Build the fitting set only from candidates
   passing every required role predicate and the required provider findings.
2. Intersect that set with the exact permitted tuples for this role/Profile. Missing,
   stale or contradictory mandatory evidence still refuses; it cannot supply a
   fitting candidate. No candidate permits NO_ELIGIBLE_ASSIGNMENT. One permits
   that sole candidate; comparative capacity is irrelevant when no choice remains.
3. For multiple candidates, require a complete evidence-attributable capacity order
   from that role's retained Augur assessment. Unknown/incomparable capacity yields
   CAPACITY_ORDER_UNKNOWN. Malformed, duplicated, missing or extra ranked candidate
   references yield INVALID_CAPACITY_ORDER. Do not silently drop an unranked fitting
   option and choose from what remains.
4. Filter the validated ascending capacity tiers to the permitted fitting candidates,
   removing empty tiers. With n nonempty tiers, select zero-based tier
   `(n - 1) // 2`: the middle tier, or the lower middle for an even count. Equal-capacity
   alternatives occupy one tier; adding another equivalent configuration does not
   give that tier more weight.
5. Within the selected tier, choose the bytewise ascending UTF-8 tuple
   `(provider, model_id, model_version, configuration_digest, profile_digest,
   binding_digest)`. All fields are exact bound identities. This resolves equal
   capacity mechanically; it is not a cheapest-model rule or new quality judgment.

The selected pair must itself be a complete permitted whole assignment set. If
policy expresses coupled restrictions and the independently selected pair is not
permitted, refuse SELECTED_ASSIGNMENT_SET_NOT_PERMITTED; do not search for a
second-best pair or enlarge scope. For independent per-role permissions, require
both exact selected tuples to be members. No partial assignment is applied.

Zero, unknown, invalid or impermissible results write no assignment and consume no
application slot. Normal immutable command-refusal receipts may be retained under
F1. Recognition of an already committed application precedes these fresh selection
checks and never recomputes or reapplies the assignment.

## Closed role-response extension and interpretation

W1 stays unchanged. W2/W3 use raw response schema
`imperium.bootstrap-role-assessment-response/v2`, with their previous required
fields plus capacity_order. This response is untrusted provider output, not the
stored H assessment record or an act. capacity_order is exactly one of:

- `{kind: ranked, tiers}`; tiers is a nonempty ascending list of exactly
  `{binding_refs, rationale, evidence_refs}`. Every tier has nonempty, unique exact
  binding refs; each FIT candidate occurs once across all tiers, none outside the
  FIT set occurs. Rationale is nonempty and refs identify original frozen evidence
  supporting the role-specific comparative assessment, including why ties or
  relative tier placement are warranted. No free-form authority or new source URL.
- `{kind: unknown, reason, evidence_refs}`; reason is nonempty, refs belong to frozen
  input (and may be empty when evidence is absent). Unknown is permitted for a
  schema-valid report but cannot resolve a choice among multiple fitting candidates.

If a ranked order is supplied, validate its full FIT-set coverage before filtering
permitted tuples, even if filtering leaves one option. A report with no FIT
candidates uses unknown with its reason; it cannot invent an empty ranked tier.
Both typed structure and response attribution/evidence/rubric validation must pass;
model-provided numeric labels or asserted authority cannot replace those checks.

BootstrapAssessmentAdmission retains raw response bytes and their exact schema,
claim, commission, holder and source refs. Its selected outcome points to that
validated response. The local assessment-view derivation reads W1 findings plus
W2/W3 rows and capacity_order, filters exact permitted tuples, and performs the
fixed rule. It adds no cognition call. The H assessment-view body becomes exactly
`{policy_ref, group_outcome_refs, lineage_refs, predicate_results,
permitted_assignment_set_ref, selection_rule_ref, selection_evidence}`.
selection_evidence is an ordered [Courtthane, Locksmith] list of exactly
`{role, response_ref, eligible_binding_refs, capacity_order_ref,
selected_tier_index, selected_binding_ref}`. capacity_order_ref identifies the
original retained response (the capacity_order field is fixed by this schema).
selected_tier_index is null only for the sole-eligible-candidate case; otherwise
it indexes the filtered nonempty tiers. All bindings/refs are genuine D2 refs.
The permitted_assignment_set_ref identifies the exact selected complete set,
not every allowed combination. Predicate results and original rank evidence stay
recomputable from immutable sources; the view's digest remains receipt result_ref.

assessed_assignment_set terms become exactly
`{kind, permitted_tuple_set_ref, result_group_ids, required_predicates_ref,
expected_assignments_ref, selection_rule_ref}`. Revalidate rule, original outcomes,
current authority, exact Profiles, generations and shared constraints under the
same aggregate lock before consuming one slot and atomically committing the pair.
No field on the Augur response can substitute an assignment effect or nominate
Augur's replacement. There is no alternate selection callback.

## Retry, approval and verification boundary

Each group's expanded prompt/schema/rule inputs freeze before its first attempt;
retries cannot rerank against newer evidence or a changed Profile. A resumed result
uses its original rule, not a revised policy. All existing twelve-attempt/$1.20
limits and the empty safe-retry allowlist remain. No additional cognition call is
introduced by ranking in the existing W2/W3 response or local selection.

The workload text is versioned for this amendment; prior P1–P9 approvals remain
historically bound to the original bytes. The new decision binds the revised rule
and workload as the implementation of the owner's medium-capacity instruction.
Exact genuine Profiles, ranking evidence, current heads and authority remain
requirements at their respective stages; no live ceremony is performed in O0.

The offline checker exercises zero/one/multiple candidates, odd/even middle tiers,
same-tier ties, permutation invariance, filtered tiers, incomplete/duplicate orders
and unknown capacity. These examples are abstract specification checks, not
production, concurrency, provider-capacity or safe-retry proof. CY/FC acceptance,
DEFER_ENROLLMENT, unresolved B1 and all five false flags remain. O1–O5 stay deferred.
