# O1-B2 internal projection format

`BaseInput::fromArray()` accepts an already decoded PHP array, not a remote response.
All objects require exactly their declared keys; lists require consecutive indices.
Text and D2 references use the existing strict Shape/ResponseShape validators.
References are complete `schema`, `id`, `digest` objects, compared in full. No
reference resolver, authentication, genuine admission or ParsedResponse conversion
is implemented. Declared officialness, entitlement and supplied expected references
remain claims. In particular, matching a configuration digest does not authenticate
the supplied request body. Later admission must resolve and verify these originals.

All integers are actual nonnegative PHP integers on a 64-bit runtime. Floats,
numeric strings and booleans refuse; denominators must be positive. Supported
arithmetic, including every intermediate product, ends at PHP_INT_MAX. Overflow
refuses the complete calculation even when division could make a product fit.
Nullable values below mean unknown; omitting their keys is malformed input.

## Root and context

The root keys are `schema`, `context`, `approved_candidates`, `candidates`, `evidence`.
Schema is `imperium.offline-base-selection-input/v1`.

Context contains eight distinct references: `policy_ref`, `approval_ref`,
`decision_ref`, `selection_rule_ref`, `workload_ref`, `profile_ref`,
`requirements_ref`, `universe_ref`. Remaining keys are:

- `required_profile_predicates`: nonempty unique text list, disjoint from the ten
  existing `CandidateClaim::PREDICATES`; all are required on every candidate.
- `account_scope`: nonempty text; `data_scope`: `PUBLIC_ONLY`.
- `issued_at`, `evaluated_at`, `expires_at`: supplied epoch milliseconds.
  Expiry exceeds issue by at most 1,800,000 ms. Evaluation uses no clock.
- `universe_coverage`: `COMPLETE`; `retryable_failure_allowlist`: empty list.
- `workload`: exactly ordered W1, W2, W3 objects with `group_id`,
  `input_tokens=16384`, `generated_tokens=4096`, `baseline_calls=1`, `maximum_calls=4`.
- `limits`: exactly `attempt_micro_usd=100000`, `total_micro_usd=1200000`,
  `baseline_micro_usd=300000`, `concurrency=1`, `context_reserve=4096`,
  `minimum_context=32768`, `attempt_deadline_ms=60000`, `policy_lifetime_ms=1800000`.
  Baseline 300,000 is the derived three-attempt cap, not additional authorization.

## Binding and candidate

Each approved binding has `binding_ref`, `provider`, `model_id`, `model_version`,
`configuration_ref`, `adapter_ref`, `mapping_ref`, `advertised_id`, `dispatch_id`,
`revision_pin`, `request_config`. Provider is `deepseek`; model, advertised and
dispatch IDs agree and are `deepseek-v4-flash` or `deepseek-v4-pro`. Revision pin is
`UNAVAILABLE_ACCEPTED_ALIAS`. Version is supplied nonempty text. Request config is
exactly `max_tokens=4096`, `stream=false`, integer `temperature=0`,
`thinking={type: disabled}`, `response_format={type: json_object}`,
`message_roles=[system, user]`.

The finite approved universe must contain both aliases. It can contain multiple
explicitly approved binding/version/configuration tuples within those aliases;
the selector invents none. Duplicate binding refs or duplicate full identity tuples
refuse. Each candidate binding must match its approved binding exactly; omitted,
additional and duplicate candidates refuse the entire input.

Candidate keys are `binding`, `context_refs` (all eight matching context references),
`predicates`, `bounds`, `tariffs`, `contradictions` (sorted text list).
Each mandatory predicate has exactly `disposition` (`PASS`, `FAIL`, `UNKNOWN`),
`evidence_refs` (unique frozen reference list), `reason` (nonempty text).
PASS without support is retained and excluded, never promoted to eligibility.

Bounds keys are nullable `context_tokens`, nullable `max_generated_tokens`,
`tokenizer_framing`, `invocation_bounds`, `adapter_support` (each PASS/FAIL/UNKNOWN),
`generated_accounting` (TOTAL_INCLUDES_REASONING/UNKNOWN/SEPARATE_UNMODELLED),
`access` (INVOKE/LISTED/KEY_PRESENT/UNKNOWN), and `data_scope`
(PUBLIC_ONLY/PRIVATE/UNKNOWN). Only supported PASS, invocation entitlement, public
data and total generated accounting pass. Context must meet 32,768 and the
16,384 + 4,096 + 4,096 bound; generated capacity must meet 4,096.

## Tariffs and observations

Tariffs contain exactly one object per W1/W2/W3, with `group_id`, `currency`,
`billing_model` (FIXED_RATIONAL_CEIL/UNKNOWN/MINIMUM_UNMODELLED/TIERED_UNMODELLED),
`fee_status` (COMPLETE/UNKNOWN), nullable `fixed_fees_micro_usd`,
`fee_evidence_refs`, and `meters`. Each unique-kind meter has `kind`, `quantity`,
`numerator`, `denominator`, `units`, `evidence_refs`. Only USD, complete fees,
fixed rational billing and exactly `uncached_input` (16,384 tokens) plus
`generated_total` (4,096 tokens) form a supported comparison. Unsupported or
incomplete formulas retain findings but expose null complete cost totals.

Each meter costs ceil(quantity * numerator / denominator) micro-USD; round each
meter separately, add fixed fees per attempt, then sum the three scenario attempts
for baseline and four attempts per scenario for the twelve-attempt maximum.
No cache discount, reasoning double-count, currency conversion or unknown fee is
inferred. Supported supplied numeric formulas may retain conditional totals while
their evidence is unknown; those rows cannot win.

Evidence is a unique-ref list of observations with `ref`, `source_ref`, `content_ref`,
`source_locator`, `category` (catalogue/capability/tariff/access), `officialness`
(OFFICIAL/UNVERIFIED), nullable `observed_at`, `effective_from`, `effective_until`,
`scope`, `claim_keys`, `disposition`, `reason`. Scope keys are `provider`, `model_id`,
`model_version`, `configuration_digest`, `profile_ref`, `account_scope`, `data_scope`.
Scope must match exactly. Every support reference must resolve within this frozen
input. Source locator is provenance text, never fetched or interpreted as instructions.

Maximum age is 604,800,000 ms for catalogue/capability, 86,400,000 for tariff,
900,000 for access; equality passes. Future/missing times, wrong scope/category/
claim, unverified sources and effective interval violations produce UNKNOWN.
Effective intervals and policy currentness are half-open. Tariff effective expiry
must reach policy expiry. Identity/runtime mapping and evidence support use catalogue;
access and data admissibility use access; complete tariff uses tariff; all other
generic and Profile predicates use capability. Every referenced observation must
pass. Any same-scope adverse frozen observation intersecting mandatory claims also
excludes the row, even if its reference was omitted from a PASS predicate.

## Result and reproducibility

`BaseSelector::propose()` returns immutable `BaseProposal` with `status`, original
normalized immutable `input`, all `rows`, nullable `proposedBinding`, and `ties`.
Algorithm identifier is `least_cost_initial_augur_fixed_workload_v1`. Rows preserve
binding, predicate/source findings, sorted reasons, per-scenario/meter costs,
baseline, twelve-attempt maximum and `eligible_projection`. Original reasons,
sources, unknowns and contradictions remain in input. Arrays cannot mutate the
retained object state. No result is an appointment or admitted eligibility record.

Statuses are PROPOSED_BASE, NO_ELIGIBLE_BASE, POLICY_TIME_REFUSED and
ARITHMETIC_REFUSAL; arithmetic refusal takes precedence. Malformed input throws
InvalidArgumentException. Stable row reasons use PASS/FAIL/UNKNOWN dispositions
and explicit reason codes; callers must retain unknown codes rather than infer PASS.
Eligible rows minimize baseline; equal costs use bytewise ascending provider,
model ID, model version, configuration digest. All equal-cost eligible identities
are retained in tuple order. Class-level Exclude keeps all six helpers out of
service discovery. The synthetic fixture and adversarial cases in
`tests/Imperium/Runtime/ProviderOnboardingBaseSelectionTest.php` are executable format
examples, not evidence of real provider support, freshness or authority.
