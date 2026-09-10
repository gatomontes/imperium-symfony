> Current onboarding status: [validated O1 offline selector and owner-authorized integration](provider-onboarding-o1-current-status.md).
> Earlier PHP-validation-pending and local-only instructions below are historical for this batch.
> Author-review provenance and all live deferrals remain explicit.

> Current continuation: owner selected the bounded [O1 offline selection core](next-campaign-provider-onboarding-o1-offline-core.md).
> O0 independent-review qualification remains; this selection grants no live authority.
> Earlier O1 deferral below is historical only for this explicitly selected first batch.

> Current selection decision: [medium capacity](provider-onboarding-medium-capacity-decision.md) supersedes the earlier
> unspecified target-assignment choice. All other approved P1–P9 values remain.

> Current owner decision: [P1–P9 approval](provider-onboarding-policy-approval.md) is recorded.
> Earlier pending-value language is historical for those exact choices. Genuine
> evidence and final O0 review remain open; O1–O5 and live work remain deferred.

> Current O0 correction: [report](handoffs/provider-onboarding-o0-correction-report.md) and [decision/evidence card](provider-onboarding-current-decision-evidence-card.md).
> The preceding provider/retry design is independently accepted as preparation with
> a validator qualification. Its correction now awaits review; final O0 freeze remains
> pending policy/evidence. Earlier status wording below is historical where superseded.
> Settled DeepSeek/API-key/FRESH/D2-A/count/dollar choices remain; O1–O5 and live work stay deferred.

> Current continuation: [selected-decision closure](handoffs/provider-onboarding-o0-selected-decisions-ready.md).

> Current O0 provider closure: [report](handoffs/provider-onboarding-o0-provider-report.md). Selected choices are retained;
> v1.3.1 and public templates await independent review and remaining evidence.
> O0 only: no runnable policy, live onboarding or O1–O5 selection.
> Owner selections: DeepSeek/API key, FRESH, D2-A, and three retries per assessment
> call, at most twelve cognition attempts and $1.20 total. O0 remains pending exact
> evidence/policy and review of the v1.3 retry amendment. O1–O5 remain deferred.

# Provider Onboarding and Bootstrap Cognition — O0 contract preparation

Status: **PROVIDER_ONBOARDING_O0_SELECTED**. The owner requested documentation
closeout and preparation to start onboarding in a new local chat. O0 only is
selected. O1–O5 implementation remains deferred. This selection itself changes
only documentation; it performs no onboarding or installed-state transition.

## Entry and accepted baseline

Start from main containing this selection and CY integration PR #780:
`79282773b0c1ca93aa17303d46e784b5415cad7e`, tree
`55b100f65ac30ed2d1ea92261de14bea8f09c831`. Record the actual selection-merge entry
commit/tree; this earlier CY merge is the executable baseline, not an assertion
that later documentation commits have the same tree.

[CY/FC acceptance](courtyard-fc-acceptance.md) records the completed independent
reviews and integration. Preserve their original packets, counts, scopes and
qualifications. Do not reopen CY, FC, CF01/CF02, IR01 or native correction campaigns.
The [original review and O0 rationale](reviews/courtyard-independent-review-and-revised-o0.md)
is retained byte-for-byte; this selection supersedes its O0 deferral only.

Use a new isolated worktree `E:\htdocs\imperium-provider-onboarding-o0`, branch
`codex/provider-onboarding-o0`. Do not switch, pull into or modify the installed
`E:\htdocs\imperium` checkout. No installed/private-state reads are prerequisites.

## Required reading

Read applicable AGENTS.md, this campaign and [the handoff](handoffs/provider-onboarding-o0-ready.md), then:

- `docs/courtyard-fc-acceptance.md` and both original reviews under `docs/reviews/`.
- `docs/courtyard-identity-decisions.md`, `docs/courtyard-identity-report.md`,
  `docs/courtyard-identity-runbook.md`, `contracts/courtyard-identity-compatibility.md`.
- `docs/delegate-mission-flow.md`, `docs/next-lifecycle-delegate-mission-route.md`,
  `docs/officer-taxonomy.md`, `imperium-doctrine.md`, `offices/courtyard/doctrine.md`.
- `docs/citadel-commissioning-owner-decisions.md`, `docs/citadel-commissioning-compatibility.md`,
  `docs/citadel-readiness-matrix.md`, `contracts/citadel-formation-runtime.md`,
  `contracts/citadel-formation-claim-custody.md`.
- `composer.json`, `composer.lock`, `config/services.yaml`, relevant `src/Command/`
  entrypoints, the exact production sources listed below, and their actual producers,
  persistence, trust/currentness consumers and meaningful tests.

If current public provider documentation is needed, use official primary sources
and record retrieval time and exact URL. Public reading grants no account access,
credential validation, provider catalogue API call, paid request or live activity.
Distinguish source inspection from tests rerun and supplied evidence inspected.

## Settled owner intent

The future operator experience is: onboarding chooses a supported provider/authentication route, obtains the applicable bounded owner policy, mechanically selects the base model, establishes Augur through legitimate existing authority, obtains a bounded assessment of that provider's bootstrap models, and applies permitted assignments. Those assignments persist until the operator explicitly changes them. No automatic fallback, reassignment or self-approved Augur replacement is allowed.

The base model means the least costly eligible model satisfying the initial Augur requirements under a defined comparison workload and evidenced pricing. It is not simply the lowest advertised token price. Catalogue, capability and pricing uncertainty must be explicit. Deterministic setup work needs no cognition.

## O0.1 — fix institutional scope before wiring

Retain Citadel as jurisdiction and Courtyard as reception/formation. Courtthane is the exact `courtyard.courtthane` LEGATE; Castellan oversight stays deferred. Oracle/Augur remains separate at `oracle.augur`. Seneschal belongs to the eventual mission Curia. Onboarding is an operator/infrastructure function; it does not establish a new Office or grant Courtthane bootstrap powers.

Reuse original Citadel IDs, trust/custody roots and retained wire/storage identities where the CY compatibility contract requires them. A Courtyard-facing command must not create a second provider budget or claim namespace. Do not translate signed Castellan evidence, clear reservations, reopen interviews or create a Curia to obtain permission for bootstrap.

## O0.2 — map the actual prerequisite graph

Inventory producers, stores, consumers, competence and exact authority for each transition: provider configuration; credential custody; public catalogue admission; base-model selection; model assignment; Persona/Profile qualification; Augur occupancy; bounded Augur invocation; recommendation; assignment application; later Courtthane/Locksmith qualification and formation invocation.

Classify each as supported, partial, absent, or explicitly deferred, with source evidence. Separate a fresh installation's legitimate founding path from resuming an existing installation. Do not assume a naming change, an existing signed file, a valid hash or an ACTIVE label proves current authority.

Trace at least these existing sources and their dependencies:

- `src/Imperium/Runtime/Imperator/FoundingAugurModelAssignmentService.php`
- `src/Imperium/Runtime/Conscription/AugurResidentActivationService.php`
- `src/Imperium/Runtime/Oracle/ModelRecommendationService.php`, plus actual catalogue, commission, assessment and selection/application consumers
- `src/Imperium/Runtime/Citadel/Formation/FormationInstitution.php`, `FormationPersonnel.php`, `FormationSessionAuthority.php`, `FormationPreparedOperation.php`
- `src/Imperium/Runtime/Clavium/FormationClaimCustodyBroker.php`
- `src/Imperium/Runtime/LaCortine/CredentialBroker.php` and `EnvironmentCredentialBroker.php`
- `src/Imperium/Runtime/Citadel/DeepSeekSymfonyPlatformAdapter.php`

The inspected founding assignment is explicitly provisional. Augur activation requires custody, approved Profile and Recruiter evidence; its result does not grant provider invocation, recommendation or model assignment authority. The recommendation service produces a pending Curia selection decision, not an automatically applied bootstrap assignment. O0 must determine how the operator-approved bootstrap policy legitimately reaches those transitions without inventing a provisional mission Curia. Do not assume a compatible producer exists simply because the consuming class exists.

The formation custody path currently has interview, drafting and receiving phases. Do not label Augur research as a Courtthane interview to reuse that authority. Identify the legitimate Augur invocation path and any missing integration explicitly. Do not reopen a consumed founding window or manufacture a new founding exception. Complete the independent contract work if a transition is blocked, then identify the exact missing decision or implementation.

## O0.3 — define provider and authentication capabilities

Define one adapter contract declaring supported authentication, exact permitted destinations, provider/model IDs, request serialization, response/usage evidence, limits and failure semantics. First implementation scope remains one provider's API-key route. No provider has been newly selected by this plan; the existing DeepSeek adapter is evidence of a legacy path, not an owner selection for onboarding.

Define OAuth as an explicit extension capability. Unsupported browser/device flows must be shown as unsupported, not implied by generic bearer-token support. Token acquisition/refresh remains infrastructure work behind credential custody. O0 uses source and public supplied evidence only; it does not authenticate, read live credentials or query provider accounts.

Configuration, authenticated access, model assignment and permission to invoke are distinct states. Record the source/adapter version, exact binding and separate owner authority needed to advance each. Keep credentials and refresh material out of prompts, public status, manifests and logs.

## O0.4 — make base-model selection reproducible

Specify minimum Augur capability requirements, eligible catalogue scope, account/access evidence, catalogue/pricing freshness, comparison workload, maximum acceptable cost, timeout semantics and stable tie-breaking. Produce an explanation from retained input facts. Refuse when no candidate qualifies or the required evidence is unknown. Base selection performs no model call itself. Any later catalogue fetch, credential test or paid evaluation must have its own applicable bounded authorization.

Distinguish a proposed binding from a persisted authorized assignment. Pin exact supported model identifiers and configuration; disclose any inability to pin the provider's underlying model revision. Provider-side alias changes must not silently be represented as operator-approved reassignment.

## O0.5 — define persistent consent and recovery

Define which initial bootstrap policy permits mechanical application of Augur's result and which deviations require an explicit operator decision. Specify exact provider, role, model, configuration, limits, source evidence and policy/version bindings. An authorization may cover the bounded sequence without repetitive approval prompts, but it cannot supply missing institutional authority.

Settings remain until explicit operator change. An unavailable or newly ineligible model stops the affected operation. Refreshing credentials must not change the provider/model binding or enlarge scope. Interruption/restart does not repeat a paid evaluation or consume authority again. Unknown outcomes retain exposure and cannot trigger an automatic retry or refund. Do not claim a local timeout guarantees remote cancellation, zero billing or completed delivery; retain B1 as unresolved where applicable.

## O0.6 — freeze the shared CLI/runtime interface

Define proposed command inputs, outputs, status codes and persisted transitions for onboard, status and resume before parallel implementation. Label proposed commands as unimplemented. Reuse the implemented `imperium:courtyard:intake`, `:formation`, and `:prepare` interfaces where relevant; keep their historical aliases and shared domain.

The CLI must distinguish configured, missing credential, authentication pending, base selected, missing Augur authority, assessment authorized, result pending, assignment applied, refused and outcome unknown. It should explain the next permissible action without printing secrets or suggesting broad activation as a remedy.

If later authorized for parallel work, assign provider/custody/runtime wiring to one agent and CLI/status/resume work to another in separate worktrees with explicit file ownership. Both consume this contract. One integration owner verifies the combined commit; neither agent changes authority semantics independently.

## O0.7 — deliverables and exit gate

Required repository deliverables for this O0 run:

1. `docs/provider-onboarding-prerequisites.md`: exact transition/source/authority matrix and unresolved prerequisites.
2. `contracts/provider-onboarding.md`: adapter/authentication, state, consent, selection and recovery contract shared by CLI and runtime.
3. `docs/provider-onboarding-base-model-policy.md`: eligibility, comparison cost, freshness, tie-break and persistence rules.
4. `docs/handoffs/provider-onboarding-o0-report.md`: actual baseline, decisions, evidence, implementation division and blockers.

Produce these outputs in this local O0 run. O0 is complete only when the interface and authority graph are concrete enough to implement without silently making owner policy or founding-authority decisions. If a decision is indispensable, finish the other useful work and present the exact unresolved transition. Stop after O0 at local commits for independent review. O1–O5 implementation and live activation are not authorized by this campaign.

The subsequent mission flow remains: Courtyard receives → authorized Courtthane interview → attributable understanding closes interview → separate exact drafting approval → numbered proposal → separate mission approval → legitimate constitution/appointments → handoff → Seneschal receiving assessment → separate execution gates.


## Local validation and review handoff

O0 produces documentation and contracts only; do not add runtime behavior, adapters,
new commands, dependencies, fixture appointments or provider calls. Do not invent
passing executable proof for a proposed interface. Verify repository-local links,
source paths/symbols, exact baseline identities, consistency with the accepted
compatibility contract and `git diff --check`. No full PHP rerun is required for
this documentation-only stage. If existing tests are inspected, label them as such.

Complete all independently useful O0 work before reporting a genuinely missing
owner decision. Use explicit UNSELECTED/UNRESOLVED fields instead of silently
choosing a provider, authentication scheme beyond the scoped API-key path, limits,
founding exception or authority bridge. Source existence alone is not acceptance.

Return the four deliverables above plus a compact review packet containing the
report, exact entry/final commit and tree, changed-path list, full documentation
diff, referenced source identities, verification commands/results and payload
SHA-256 manifest. Provide an outer archive hash separately. Record any post-check
changes. Commit locally and stop for independent review; no push or merge is part
of the local O0 runner. Never include private runtime material or credentials.

## Single-file delivery requirement

For this run and every subsequent Imperium handoff, collect all produced public
handoff files into one top-level `<campaign>-all-deliverables.zip`. Include every
report and independent review, created or revised document/contract/policy/decision
sheet, run instructions, and the review evidence package with its existing
checksum file. Preserve useful relative paths inside the ZIP and add a short
`README.md` listing the contents and the first file to read. The owner must not
have to collect files from multiple folders or upload separate documents manually.

For local runs, write the convenience ZIP at the isolated worktree root and print
its exact absolute path plus the one-file upload instruction. Keep the ZIP out of
Git. For chat-produced deliverables, provide one download link to the convenience
ZIP. Verify that the archive opens and contains the complete intended file set.
No SHA-256 or separate checksum is required for this convenience ZIP; existing
review-package hashes and manifests remain required and unchanged. Include only
public handoff artifacts, preserving the existing private-material exclusion.
Carry this requirement into every subsequent campaign and local launch prompt.

All operational flags remain false. `DEFER_ENROLLMENT` remains selected. B1 remote
cost/time/cancellation guarantees remain unresolved; O0 does not relax them.
After O0 review, select the bounded implementation work and division between
wiring and CLI agents explicitly. Do not launch either implementation agent during O0.

*Imperium via solitaria est.*
