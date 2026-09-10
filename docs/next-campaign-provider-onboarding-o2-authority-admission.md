# O2-B0 — original authority admission

Status: SELECTED_FOR_NEXT_LOCAL_IMPLEMENTATION. This pass prepares/selects O2-B0;
it implements no runtime. O1 is complete within reviewed offline scope, through
PR #786 merge `b60f16a8af003408e84b68d1ac3462fb06a9479d`, tree
`34a51ba57463419dbf1eb8165756f00d46e121c1`. Six implementation batches remain
across O2–O5. No additional O1 batch is opened without a concrete defect.

## Entry and required reading

Start from source containing this preparation in a fresh isolated worktree on
`codex/provider-onboarding-o2-authority-admission`. Preserve installed/existing
worktrees. Record actual entry commit/tree; the O1 merge is this preparation's
entry, not automatically the later implementation's entry.

Read applicable AGENTS.md, the [admission contract](../contracts/provider-onboarding-authority-admission.md),
[roadmap](provider-onboarding-implementation-roadmap.md), [current status](provider-onboarding-o1-current-status.md),
[B2 source review](reviews/provider-onboarding-o1-b2-source-evidence-review.md),
[B2 integration identity](provider-onboarding/o1-b2-reviewed-integration.json),
[D2 authority proposal](provider-onboarding-authority-proposal.md),
[main contract](../contracts/provider-onboarding.md),
[F1/F2 continuation](../contracts/provider-onboarding-continuation.md),
[retry amendment](../contracts/provider-onboarding-retries.md),
[selected decisions](provider-onboarding-selected-decisions.md),
[P1–P9 approval](provider-onboarding-policy-approval.md),
[original approval record](provider-onboarding/o0-policy-approval.json),
[decision card](provider-onboarding-current-decision-evidence-card.md),
[public preparation](provider-onboarding/o0-public-preparation.json),
[workloads](provider-onboarding/o0-workloads.json),
[prerequisite map](provider-onboarding-prerequisites.md),
[O1 input boundary](provider-onboarding-base-selection-input.md),
[CY/FC acceptance](courtyard-fc-acceptance.md), and
[source-pin correction](provider-onboarding-o1-ci-correction.md).
Retained historical unselected/pending status does not reopen settled choices.

Inspect CanonicalJson, FormationJournal, FormationSignatures, NativeTrust, Clock,
AtomicTransition, the O1 strict decoder/types and relevant existing tests. Reuse
mechanics only after checking exact behavior; their existing domain competence
does not transfer. Inspect the incompatible legacy founding/standing-approval
producers named by D2 rather than disguising their outputs as genuine originals.

## Work and proof

Implement only the contract's bounded public-trust code, original act/object/policy
admission, trusted references, revocation and current resolution. Declare exact
internal interfaces, schema/bounds, store version and receipt semantics before
coding. Keep admission uniqueness separate from O2-B1 execution consumption.
New classes use Exclude and fixed-root construction with existing dependencies.
No CLI, automatic enrollment, provider calls or operational consumer.

Complete every adversarial case in the contract with real new producer-to-consumer
paths in temporary roots, including concurrency, interruption and restart evidence.
Synthetic cryptographic fixtures establish verifier behavior only. All missing
genuine trust/competence/source prerequisites must refuse through production code.
Do not ask for live acts merely to run offline tests. No parallel agents selected.

Run PHP lint, the new focused admission suites, all three O1 suites
`ProviderOnboardingOfflineSelectionTest`, `ProviderOnboardingResponseValidationTest`,
`ProviderOnboardingBaseSelectionTest`, relevant FormationJournal/native-trust
regressions, and the three exact source-pin suites named in the CI correction.
Run `python -B tools/check_provider_onboarding_spec.py` (all 86 checks). Use locked
PHP/Composer versions; this PHPUnit lock needs PHP >=8.4.1. Install with no scripts
or plugins when needed. Run meaningful subprocess contention/recovery tests on
the host platform and report skips/incomplete runs honestly. Full repository CI
is the later integration gate; B2 CI is not proof of new O2 runtime.

O2-B1 ledger/custody, O3 adapters/founding, O4 application and O5 CLI stay unselected.
DEFER_ENROLLMENT, B1 remote guarantees, empty retry allowlist, persistent operator
settings and five false live flags remain. FRESH checks do not consume the founding
window here. Existing-installation cutover and institutional-source adapters refuse.

## Exit

Working source plus required evidence must meet the contract before local completion.
Update only actual current disposition; preserve original policy and historical
reports. Commit locally and stop for source/integration review. No push, merge,
live effects or later implementation is implied.

Return one `provider-onboarding-o2-b0-all-deliverables.zip` with README, individual
report/instructions, every changed source/test/document, commands, public logs,
entry/tested/final identities, hashes, patch/bundle and checksummed inner review ZIP.
No outer checksum. Clearly distinguish author reports, freshly executed checks,
inherited CI and genuine commissioning gaps.
