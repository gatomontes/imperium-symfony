# Proposed base-model policy — O0 v1

> Current selected-route preparation and remaining dependencies are in the
> [provider closure report](handoffs/provider-onboarding-o0-provider-report.md).
> Earlier UNSELECTED/unapproved choice language below is historical where the
> selected-decision record supersedes it; it does not reopen settled choices.

> The [closure-pass decision sheet](provider-onboarding-owner-decisions.md) proposes
> concrete thresholds/workload/limits for approval. They do not replace UNRESOLVED
> values until accepted and bound to exact public evidence. The algorithm below
> remains unchanged; its original no-provider-facts statement describes the first
> submission. Limited public interface references are recorded in the new sheet.

**UNIMPLEMENTED; REQUIRED OWNER VALUES UNRESOLVED.** This is a reproducible
algorithm, not a provider choice, price claim or assignment authorization.
The [prerequisite map](provider-onboarding-prerequisites.md) identifies missing
authority; the [shared contract](../contracts/provider-onboarding.md) defines
consent and recovery. This algorithm performs no model calls.

## Meaning and required input

Base means the least costly eligible cognition satisfying initial Augur
requirements under an explicitly frozen comparison workload. The result is a
proposed binding for `oracle.augur`, not an appointment or invocation grant.
Provider remains **UNSELECTED**. No current public provider facts were needed or
adopted during O0. Later evidence must identify official source URL, retrieval
time, content digest, effective interval and supported account scope.

The policy is versioned and immutable. Required fields cannot be guessed, taken
from test fixtures or defaulted from legacy DI. `UNRESOLVED` is a preparation
value and invalid for a runnable policy.

| Field | Required definition | O0 value |
| --- | --- | --- |
| identity | ID/version/digest, instance, accountable issuer, independently trusted authority reference, issue/expiry times and revocation source | UNRESOLVED |
| provider_scope | One provider, API-key adapter/version, exact catalogue scope and dispatch mapping, destinations, account scope and data constraints | UNSELECTED |
| augur_requirements | Exact approved Persona/Profile references, capability predicates, minimum context/output, input modalities, prohibited capabilities, result schema and any required tools | UNRESOLVED |
| workload | Versioned ordered scenarios, public prompt/template references, input-token upper bounds using evidenced tokenizer, maximum output/reasoning tokens, calls, concurrency, duration and cache treatment | UNRESOLVED |
| evidence_policy | Allowed official/supplied sources, minimum evidence, separate capability/catalogue/pricing/access maximum ages, effective intervals, admissibility and contradiction rules | UNRESOLVED |
| limits | Maximum comparison cost, per-call/total invocation limits, calls/tokens/milliseconds, local deadline and accepted remote semantics | UNRESOLVED; B1 unchanged |
| application_scope | Exact target role set, per-role allowed model/configuration set, expected assignment generations and permitted one-time application effect | UNRESOLVED |

Minimum semantic requirements are evidence-attributable comparison of the chosen
provider's models, following an unchanged rubric, reporting missing facts and
contradictions, and returning the required structured assessment. Before selection,
these must be explicit Profile-derived predicates with measurable thresholds.
O0 does not assume browsing, tool calling, a context size or reliability threshold.
Institutional qualification cannot be replaced by a capability checklist.

## Deterministic eligibility

Freeze policy, source snapshot, adapter description, configuration candidates and
evaluation time together. Use repository CanonicalJson rules for normalized
digests; retain original evidence too. Decimal quantities use exact integers or
rationals, never binary floating point.

Record PASS, FAIL or UNKNOWN with source references for every candidate:

1. Exact provider/model/version/configuration membership and adapter support.
   Preserve advertised ID and actual dispatch ID; require complete runtime mapping.
2. Every approved Augur predicate. Context must fit the input upper bound plus
   maximum output/reasoning and explicit policy reserve. Output ceilings and
   configuration must be supported. Unknown tokenizer/bounds are UNKNOWN.
3. Current scoped authenticated account-access evidence, admissibility, region,
   data and tool restrictions. Key presence or catalogue listing is not access.
4. Catalogue, capability, access and tariff observations within their separate
   maximum ages/effective intervals at evaluation time. Future observations,
   contradictory mandatory facts or missing provenance are UNKNOWN.
5. All billable workload components, evidenced units/rates and fixed/minimum fees,
   within the comparison ceiling. Unknown mandatory fees, tiers, conversion or
   reasoning-token billing are UNKNOWN.
6. Supported invocation bounds consistent with unchanged B1. A tariff estimate
   proves neither remote cancellation nor a guaranteed billing ceiling.

Eligibility requires all PASS. Retain excluded candidates and exact reasons.
An incomplete catalogue establishes cheapest only within its explicitly approved
bounded universe, never across all offerings. If policy requires complete coverage
and coverage is unknown, refuse. No candidate means `REFUSED / NO_ELIGIBLE_BASE`;
no fallback or paid probe is implied.

## Exact comparison cost

Comparison uses integer micro-USD. A non-USD tariff needs an explicit evidenced
conversion policy; never adopt an implicit exchange rate. For scenario s,
candidate m and each mutually exclusive billable meter j, retain quantity q,
rate numerator r, denominator d, units, currency and source.

`scenario_cost(s,m) = fixed_fees(s,m) + sum_j ceil(q(s,m,j) * r(m,j) / d(m,j))`

`comparison_cost(m) = sum_s calls(s) * scenario_cost(s,m)`

Meters cover uncached input, guaranteed cached input, visible output and separately
billed reasoning without double-counting. Use uncached input unless policy and
evidence guarantee cache applicability. Provider rounding/minimum/tier rules need
an exact conservative upper-cost formula with evidence; refuse unmodelled meters.
Speculative discounts, cache hits, promotions, credits or observed average use
cannot lower that cost. Workload purpose/call counts stay identical across
candidates; tokenizer-dependent quantities must be explained.

Filter by eligibility, then minimize integer comparison_cost. Exact ties use
bytewise ascending UTF-8 `(provider, model_id, model_version, configuration_digest)`.
IDs are case-sensitive; no locale sorting. Retain all tied rows. No hidden quality
weighting: every candidate must already satisfy minimum Augur requirements.
Later assessment has its own rubric/authority and cannot authorize this selection
retrospectively.

The retained explanation contains algorithm version, evaluation time, all input
digests, every candidate's predicates/evidence, per-meter arithmetic, exclusions,
eligible totals, tie-break and exact proposed binding. It is reproducible without
credentials or a model call. Changing workload/pricing produces a new explanation.
Application separately checks per-call/total limits; comparison cost is no grant.
O0 contains no fabricated tariff or winning model.

## Persistence and reassessment

Pin provider, API model ID, provider revision where available, configuration,
adapter version, policy and evidence. An unpinnable alias records
`revision_pin: unavailable`, its exact alias and observation time, and requires
explicit owner acceptance of that limitation. Do not claim stable underlying
revision or describe provider alias drift as owner-approved reassignment.

Initial consent may permit mechanical application of an attributable Augur result
only to the exact approved role/model/configuration set, once, at expected
generations and within expiry. Until the missing legitimate producer/application
consumer exists, output stays proposed. Augur cannot approve its own replacement;
that requires explicit operator change and genuine governed cutover.

Applied settings persist until explicit operator change. New catalogue/pricing,
credential rotation, an outage or a cheaper candidate does not replace them.
Unavailable, stale or newly ineligible models stop affected fresh work while
retaining assignment intent/history. Revalidation may block use, not erase or
replace the binding. A later exact prospective change leaves earlier unknown
calls and exposure attributable to their original policy and model.
