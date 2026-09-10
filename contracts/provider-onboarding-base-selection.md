# O1-B2 — offline least-cost initial Augur proposal

Implementation specification; no O1-B2 runtime is supplied by this preparation.
The governing algorithm is the unchanged [base policy](../docs/provider-onboarding-base-model-policy.md).
The [P1–P9 approval](../docs/provider-onboarding-policy-approval.md), its
[original source-bound record](../docs/provider-onboarding/o0-policy-approval.json),
and [decision card](../docs/provider-onboarding-current-decision-evidence-card.md)
fix the owner values. The conventions below make that algorithm implementable;
they are prospective engineering definitions, not claims of prior signed authority.

## Scope and trust boundary

Compute a reproducible proposal for `oracle.augur`, with no filesystem, network,
clock, credential, record/ledger, appointment, assignment or invocation side effect.
Take evaluation time as an explicit argument. No provider call, catalogue refresh,
live evidence acquisition or persisted proposal is included. O1-B0's medium-capacity
selector still governs Courtthane and Locksmith; it is not the base comparator.

Inputs are immutable, closed internal projections of the exact policy/workload,
approved candidate universe, Augur Profile and frozen source observations. Their
construction checks syntax and consistency only. Supplied references, PASS labels,
source URLs and claimed account observations do not authenticate themselves.
The later O2 admission boundary must establish genuine originals, competence,
currentness, applicability and provenance before using these projections. A pure
proposal does not establish a genuine eligible base, found an Augur, or permit a call.
Provide no boolean or method named validated that grants those powers. Do not
accept ParsedResponse as proof of base fitness or turn its raw claims into authority.

## Required input contract

Use final immutable value types and explicit factory checks. Before implementing
the comparator, declare and test each object's exact field list, types and legal
tags in the focused source/test. Unknown or missing fields and scalar coercion
refuse. O1-B2 is an internal projection API, not another raw provider JSON decoder.
Preparation wrappers, unresolved descriptor refs and unsigned policy templates
must never be accepted as runnable institutional records.

The complete input must retain:

- Exact D2 `{schema,id,digest}` policy, workload, approved Augur Profile, requirements,
  approved candidate-universe and evidence refs. Refs use the O1-B1 ID/digest bounds;
  equality includes schema, ID and digest. Missing, duplicate or mismatched identities
  refuse. The approved universe is a complete list, not the caller's cheapest subset.
- Exact candidate binding, configuration and adapter/runtime mapping refs; provider,
  advertised model ID, dispatch ID, model version/observed label and configuration
  digest. Each approved identity occurs once, with no extras or conflicting binding
  identities. Model/version/configuration strings stay exact and case-sensitive.
- A complete per-candidate predicate ledger containing the ten P5 predicate keys
  from the decision card, exact PASS/FAIL/UNKNOWN dispositions, frozen supporting
  refs and retained reasons. Every additional exact Profile-required predicate is
  enumerated by the supplied requirements and covered once. Required PASS without
  support cannot make a candidate eligible. Do not manufacture Profile requirements.
- Observations with exact source/content refs, category (catalogue, capability,
  tariff or access), observed time, effective interval, supported claim identities
  and provider/model/configuration/account/data scope. At least one supplied official
  source is required for each mandatory factual claim. Officialness and truth still
  require actual source admission later; a URL suffix cannot prove either.
- Supported context/output limits, tokenizer/framing and total-generated accounting,
  invocation bounds and data/access scope, including explicit unknown findings.
  A key-present or models-listed observation cannot substitute invoke entitlement.
- The identical ordered W1/W2/W3 comparison scenarios and evidenced quantities,
  complete tariff meters/fees/rounding assumptions, comparison/per-attempt/aggregate
  ceilings, and explicit issued/evaluation/authorization-expiry times.

Preserve the exact originals bound by the approval record. The later
[medium-capacity amendment](provider-onboarding-assignment-selection.md) changes
target-role result interpretation; it does not change base minimization or silently
rebind original approval hashes. Identify both the selected workload/rule refs and
their original approval/decision lineage. A mutable current pathname is not that binding.

## Fixed approved values

Only DeepSeek's named `deepseek-v4-flash` and `deepseek-v4-pro` aliases are in the
selected universe; no automatic additions or fallback. Preserve the accepted named
alias limitation: an observed version label is not immutable request revision support.
Use exact supplied policy-bound versions/refs, never refresh or invent them from a name.
No actual winning model is fixed by this contract or the retained price examples.

The selected request configuration is disabled thinking, integer temperature 0,
`max_tokens: 4096`, non-streaming JSON-object response, system then user messages,
and no tools or the other omitted fields in P2. The eventual adapter is separate;
the presence of that configuration in a template does not prove adapter support.

Each scenario uses the approved conservative maximum of 16,384 input tokens,
including framing/Profiles/evidence/context, and 4,096 total generated billable
tokens. Reasoning is included without double-counting. Minimum context is 32,768
and reserve 4,096; the required input + generated + reserve sum is 24,576. Check
both the minimum context and the actual supported bound. Unknown tokenizer,
framing, output/accounting or invocation support prevents eligibility.

Compare the three-success baseline: one W1, one W2 and one W3. Also report the
twelve-attempt upper-cost case, four attempts per scenario. This is an exposure
calculation, not permission to use retries. The safe-retry allowlist remains empty.
Each attempt must fit 100,000 micro-USD and the twelve-attempt maximum must fit
1,200,000 micro-USD. Concurrency one, 60,000 ms local attempt deadline, 30-minute
policy lifetime and the separately authorized zero-fee access design remain fixed.
Access contributes no speculative cost allowance or extra comparison scenario.

## Freshness and deterministic exclusions

All supplied instants are nonnegative integer Unix milliseconds in one time basis.
Document arithmetic bounds and reject invalid intervals without overflow/coercion.
Use `issued_at <= evaluated_at < authorization_expires_at`. Effective intervals
are half-open `[effective_from, effective_until)`; age maxima are inclusive.
For each mandatory observation require `observed_at <= evaluated_at` and
`evaluated_at - observed_at <= category_max_age_ms`, plus effective coverage at
evaluation. Tariff evidence must also cover authorization through its expiry:
`effective_until >= authorization_expires_at`. Test each equality boundary.

Catalogue and capability maximum age is 604,800,000 ms; tariff 86,400,000 ms;
access 900,000 ms. Missing/future/stale/out-of-interval or wrong-scope evidence
cannot supply PASS. Keep those UNKNOWN findings distinct from a substantive FAIL.
Contradictory mandatory findings block eligibility, even if another supplied claim
says PASS. Frozen membership alone is insufficient. Later admission/application
rechecks currentness; this calculation cannot extend any original observation.

Eligibility requires every mandatory generic and exact Profile predicate PASS,
consistent supporting data, complete tariff treatment and cost within the approved
caps. Retain every excluded candidate, predicate result and deterministic reason.
Never silently drop an unknown expensive candidate to claim global cheapest.
If required universe coverage is missing/unknown, refuse the whole input. Complete
candidate coverage with no eligible rows returns `NO_ELIGIBLE_BASE`, not a fallback.

## Exact cost and deterministic ordering

For scenario s and candidate m, use the original base-policy formula:

```text
scenario_cost(s,m) = fixed_fees(s,m) + sum_j ceil(q(s,m,j) * r(m,j) / d(m,j))
baseline(m) = sum_s 1 * scenario_cost(s,m)
twelve_attempt_max(m) = sum_s 4 * scenario_cost(s,m)
```

Money is integer micro-USD. Quantities and rational rate numerators are nonnegative
integers, denominators strictly positive. Retain every meter's source, units,
quantity, numerator, denominator, rounded contribution and fixed fees. Round each
meter before summing, then multiply by calls; rounding only the final total is wrong.
Checked integer arithmetic or an existing exact arithmetic implementation is required;
never use float, approximate decimal conversion or a dependency/lock update. Define
the supported integer range explicitly. If an exact operation cannot be represented,
return a typed arithmetic refusal for the complete calculation; do not wrap, saturate,
silently coerce to float or exclude that row to choose another candidate.

Use conservative uncached input and total generated output under this selected
policy. No assumed cache hits, off-peak price, promotion, credit or average usage.
Do not count reasoning both inside generated output and as an extra meter. No
implicit USD conversion or zero fee: require explicit evidence for absent charges.
Unmodelled currency conversion, minimum fees, tier schedules, meter overlap,
rounding rules or account charges produce explicit unknown/exclusion or malformed
input refusal as appropriate; no arbitrary billing formula language is selected.
A supported conservative fixed/rational representation must be fully attributable.

Minimize baseline among eligible rows. Exact ties use bytewise UTF-8 ascending
`(provider, model_id, model_version, configuration_digest)`, preserving case.
Retain all tied candidates. If distinct binding refs duplicate that full identity,
refuse the ambiguity rather than inventing an additional tie-break. Candidate and
evidence input order cannot affect the result or deterministic explanation order.

The historical [conditional arithmetic](../docs/provider-onboarding/o0-cost-comparison.json)
is a regression oracle only: Flash 12,616 / 37,848 / 151,392 micro-USD for
attempt/baseline/twelve attempts; Pro 37,848 / 113,544 / 454,176. Its unresolved
facts must still produce no genuine eligible base. These retained numbers are not
a fresh price quote, evidence refresh, preferred provider-model decision or guarantee.

## Output and finish line

Return an immutable calculation result: proposed exact binding or typed refusal,
algorithm version, evaluation time, complete input identities, per-candidate
predicate/source findings and exclusions, per-meter arithmetic, baseline/maxima,
all ties and tie-break explanation. Selection success is a proposal only; it has
no effect authority, admission receipt, H-record constructor or automatic consumer.
Reason ordering and rows must be stable and recomputable without I/O. Malformed
input and no eligible candidate are distinct outcomes; retain unknowns honestly.

Implement in `src/Imperium/Runtime/Onboarding/BaseSelection/` with focused tests in
`tests/Imperium/Runtime/`. Every new class uses class-level Symfony Exclude. Keep
services.yaml, dependencies, B0/B1 implementation/tests, historical evidence and
original approved bytes unchanged. The [campaign](../docs/next-campaign-provider-onboarding-o1-base-selection.md)
defines the required adversarial proof and local review handoff.
