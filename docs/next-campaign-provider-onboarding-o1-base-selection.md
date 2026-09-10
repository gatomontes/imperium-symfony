# O1-B2 — offline least-cost initial Augur proposal

Status: SELECTED_FOR_NEXT_LOCAL_IMPLEMENTATION. This commit prepares/selects the
bounded batch under the owner's instruction to follow the reviewed O1-B1 packet.
It does not implement B2. O1-B0 and O1-B1 remain completed/integrated. One O1
implementation batch and seven across O1–O5 remain; live commissioning is separate.

Preparation entry is merged O1-B1 `cc9a1882e88de55412d1428e2a01e5ac85bb92c5`,
tree `d8d3a385b50008c5ae56d3f6a2f384bbd93fd230`. The next implementation must
start from source containing this preparation and record its actual commit/tree;
do not mislabel the O1-B1 merge as the later preparation entry. Use a fresh isolated
worktree on `codex/provider-onboarding-o1-base-selection`. Preserve any existing
branch/worktree and the installed checkout. The local launch also supports the
unpublished local preparation branch; publishing/merging is a separate step.

## Required sources

Read applicable AGENTS.md, the [roadmap](provider-onboarding-implementation-roadmap.md),
[B2 implementation contract](../contracts/provider-onboarding-base-selection.md),
[original base policy](provider-onboarding-base-model-policy.md),
[approved values](provider-onboarding-current-decision-evidence-card.md),
[approval record](provider-onboarding/o0-policy-approval.json),
[policy approval explanation](provider-onboarding-policy-approval.md),
[selected decisions](provider-onboarding-selected-decisions.md),
[public preparation](provider-onboarding/o0-public-preparation.json),
[workloads](provider-onboarding/o0-workloads.json),
[conditional cost oracle](provider-onboarding/o0-cost-comparison.json),
[retained provider appendix](provider-onboarding-deepseek-appendix.md),
[authority proposal](provider-onboarding-authority-proposal.md),
[continuation](../contracts/provider-onboarding-continuation.md),
[retry](../contracts/provider-onboarding-retries.md),
[target selection](../contracts/provider-onboarding-assignment-selection.md),
[response boundary](../contracts/provider-onboarding-response-validation.md),
[CI source-pin correction](provider-onboarding-o1-ci-correction.md),
[B1 review](reviews/provider-onboarding-o1-b1-source-evidence-review.md),
[B1 integration identity](provider-onboarding/o1-b1-reviewed-integration.json) and
[CY/FC acceptance](courtyard-fc-acceptance.md). Inspect the existing B0/B1 value
types and tests and available exact arithmetic helpers before creating new ones.
Do not refresh provider facts; frozen public material is historical evidence.

## Work and required tests

1. Declare strict immutable internal input/result shapes for the B2 contract.
   Validate exact complete universe/Profile/workload/source identity and predicate
   coverage, without inventing genuine admission or supported adapter facts.
2. Implement freshness/effective interval and scoped evidence consistency checks,
   explicit unknown/exclusion reasons, exact conservative per-meter cost, baseline
   and twelve-attempt ceilings, eligible minimization and the fixed bytewise tie-break.
3. Return a reproducible proposal/explanation or typed refusal. Preserve all rows,
   unknowns and tied identities. No operational consumer, automatic conversion from
   ParsedResponse, authority record, persistence, model call or new dependency.
4. Test zero/one/two eligible candidates, cheaper-but-ineligible, all-unknown historical
   tariff rows, exact ties and permutation invariance; duplicate/missing/extra universe,
   predicates and refs; changed schema/digest/version/config/Profile/workload; invalid
   types, float/boolean/string coercion; unsupported alias/config and revision claims;
   missing official support, wrong scope, access-listing-only and contradictory claims.
5. Test each age maximum at equality and one millisecond beyond, future observations,
   effective-from/effective-until equality, tariff expiry shorter/equal/longer than
   authorization expiry, expired policy and deterministic supplied evaluation time.
6. Test 16,384 + 4,096 + 4,096 bounds, 32,768 minimum context, missing framing/total
   generated accounting, reasoning double-counting, speculative cache discounts,
   missing fees, unsupported currency/meter/minimum/tier rules, zero denominator,
   negative values, exact/just-over caps, ceil-per-meter versus ceil-total, call
   multiplication, and integer overflow with an explicit complete-calculation refusal.
   Reproduce the retained conditional numbers without promoting their unknown eligibility.
7. Prove immutable output, Exclude metadata, unchanged pinned services/history and no
   authority or effect consumer. State exactly which evidence is synthetic, supplied,
   structurally checked and still requires genuine later admission.

Run PHP lint, new B2 tests, ProviderOnboardingOfflineSelectionTest,
ProviderOnboardingResponseValidationTest, and the three source-pin regressions in
the CI correction, plus all 86 existing Python specification checks. Verify the
actual locked PHP/Composer versions (PHP >=8.4.1 for this PHPUnit lock); install
the lock without scripts/plugins if needed. Full repository CI is the integration
gate. Record incomplete/failed local runs and platform skips honestly. Never use
prior B1 CI as proof of B2 runtime. Do not weaken tests or rewrite historical pins.

No parallel agents are selected for this bounded batch. O2 authority admission,
O2 ledger/custody, O3 adapters/founding, O4 application and O5 CLI stay outside it.
Keep B1 remote guarantees unresolved, retry allowlist empty, persistent settings
operator-controlled, DEFER_ENROLLMENT and all five false live flags unchanged.

## Exit and delivery

Finish B2 only when bounded working source and its required tests satisfy this
contract. Update only the actual roadmap disposition, retain original evidence,
and report exact entry/tested/final source identities, commands/results, exclusions,
limitations and post-check changes. Commit locally and stop for source/integration
review; do not publish, merge, activate or run a later stage as an implied effect.

Return ONE `provider-onboarding-o1-b2-all-deliverables.zip` containing README,
reports/instructions, every changed source/test/contract, source and payload hashes,
patch/bundle and all public evidence, plus a checksummed inner review ZIP. No outer
hash; supply individual report/instructions too. Exclude private/installed state.
