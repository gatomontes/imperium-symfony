# Courtyard CY0–CY3 independent review and revised onboarding O0

## Verdict

**ACCEPTED within local/offline software scope.** No executable correction blocker was identified in the submitted Courtyard identity campaign. The accepted result implements the Courtyard reception function and exact Courtthane Seat while preserving the Citadel jurisdiction, shared custody and historical evidence boundaries. It does not establish live readiness or genuine installed qualification.

Provider Onboarding and Bootstrap Cognition remains **DEFERRED**. The revised O0 below is a planning handoff, not authorization to begin onboarding or perform live activity. No repository publication, PR, merge, installation change or activation was performed by this review.

## Verified identity and evidence

| Item | Independently checked identity |
| --- | --- |
| Submitted archive | `courtyard-identity-review-20260909.zip` |
| Archive SHA-256 | `1e64807009f563983ba97f592deb33e45af8563809f51f0f1fb93b73ae388557` |
| Entry / PR #779 preparation merge | `b1a8378668ecd9f4e4c1502411a2713111915535` |
| Entry tree | `eae698493f2f679774c7e91975e05bbaf4cae039` |
| Integrated FC baseline / PR #778 | `88cee012d5fae0dc8e18f25eebed167912e06230` |
| FC baseline tree | `3826740810f42c90e6d8b6e00686947003ab62c4` |
| Tested commit | `f524906663e6c508b849e00311802c3703ba9446` |
| Tested tree | `dd22aaf31a2bcc1285812de6eb25249a9659c4da` |
| Final accepted commit | `8f5e2052bf99b747f51320dfce2567d31d6fd0da` |
| Final accepted tree | `55b100f65ac30ed2d1ea92261de14bea8f09c831` |
| Submitted branch | `codex/courtyard-identity-mission-formation` |

The outer digest matches the separately supplied SHA-256. The exact packet contains 79 manifested payloads plus its manifest. Both complete source archives contain 3,163 files, with the one intentionally excluded tracked environment file explicitly identified. Archive path sets, modes, byte lengths, SHA-256 values and Git blob IDs match the actual 3,164-file commit trees when that recorded exclusion is included. The excluded blob's identity was also verified without disclosing its contents.

The bounded bundle verifies against the known entry commit. Imported Git objects confirm the advertised history and trees. The packet's final and post-test diffs match Git-generated binary diffs byte-for-byte. The separately uploaded report, packet report and final tracked report agree after line-ending normalization where applicable.

Commit `0f78fe9d0df9a4771eaa33b812a11fa9456529fd` introduced the compatibility contract, inventory and frozen fixtures before executable consumer changes. Commit `30a72308772effa7d40e6f413dfa1bf3e412ebe0` implemented the change. The tested commit then added only the residual audit. The final commit changes only `docs/courtyard-identity-report.md` and fixture-path casing in `contracts/courtyard-identity-compatibility.md`. There is no executable post-test change. Both supplied generated `config/reference.php` diffs contain only PHPDoc changes; the gate record reports restoration and clean final tracked status.

## Production paths and meaningful adverse evidence

| Boundary | Source and assessed proof |
| --- | --- |
| Canonical CLI and shared state | `src/Command/CitadelIntakeCommand.php`, `CitadelFormationCommand.php`, and `CitadelPreparationCommand.php` register Courtyard names with Citadel aliases. They retain the same production classes; no new journal, registry, root, lock or budget namespace is introduced. Actual Console resolution and separate-process alias contention are exercised. |
| Exact Courtthane qualification | `FormationPersonnel::appointCourtthane()` uses `courtyard.courtthane` and `APPOINT_COURTTHANE`. `candidate()` retains the institutional delegation, Garrison, Guildhall, immutable Profile, Senate findings/reconciliation, exact Profile approval and Conscription chain. `FormationInstitution` remains unchanged and requires the original nine institutional witnesses. |
| Currentness and tenure | `FormationPersonnel::currentCourtthane()` reconstructs exact appointment terms and the full candidate-derived binding, verifies the signed decision and occupied manifestation, and compares the result. `FormationOfficerAssemblyService` adds `officer_class: LEGATE` for the new Seat. Wrong Seat, holder, Profile, cognition, tenure, generation, scope, effect, replacement, expiry and revocation are tested at fresh consumers. |
| No authority translation | The old appointment and currentness APIs explicitly refuse with CY001. Old Profile approval or APPOINT_CASTELLAN evidence cannot appoint Courtthane. No future Castellan oversight authority is implemented. |
| Interview and drafting | `FormationCognition` and `FormationSessionAuthority` use current Courtthane authority. A fresh strict v2 drafting request includes the exact approval question in the source bound by the separate signed decision. Understanding closes interview grants; it creates no dossier or proposal. |
| Mission constitution | `CuriaFormationService` and `MasterMason/ChildCuriaFormationService` require the exact current Courtthane dossier holder for fresh work. Reception creates no Curia. Mission approval and legitimate child appointments precede publication. The receiving phase retains the mission's Seneschal and its own authority; acceptance and Step 1 grant no execution authority. |
| Historical recovery | `FormationCognition::recover()` recognizes an already admitted response before fresh authority validation, retaining v2 custody/envelope checks. Child reconciliation uses the existing retained publication frame, fence, signatures and receipt. Frozen pre-change evidence is used directly, without regeneration by successor code. Withholding a receipt in a disposable adverse copy does not allow a new child publication under an old appointment. |
| Custody and exposure | Existing formation lease, prepared operation and custody paths remain in place. Process tests race different aliases against a one-call budget and observe one issue/consume/dispatch. Abrupt exit after dispatch or actual envelope/child-receipt rename demonstrates retained exposure and bounded recovery; no alias supplies a retry, refund or second child. |
| Refusing defaults | Existing `UnavailableFormationTransport` and `UnavailableFormationWireAdapter` remain the production defaults. Retained tests load the actual service resource to inspect those aliases. Courtyard preparation uses no credential/provider dependency. |

The new tests add 27 cases and 180 assertions. Their coverage includes the full offline route, eight appointment substitutions, six binding substitutions, three currentness changes, original frozen evidence and separate-process contention/interruption. Existing CF01, CF02, IR01, native authority corrections, FC crash cases and coverage scanners remain in the supplied focused and full gates. The complete-route harness wires actual production services and Console commands with synthetic infrastructure; it is not an installed-application run. Actual default service configuration is covered separately.

## Verification performed here versus supplied results

**Executed by this reviewer:** archive and payload verification; bounded Git bundle import/verification; both source-tree/manifest comparisons; exact final/post-test diff comparisons; 11 protected historical/source-path comparisons; six Python helper tests; independent cryptographic and byte checks on both current public proofs; frozen compressed/per-file integrity checks.

Independent public-proof checking verified 106 unique synthetic signatures across the two current proofs. The custody check additionally verified both frame digests, request and wire bytes, exact operation/lease bindings, response identity, usage, retained admission and the reported one-use effect counts. Its substituted-wire negative control was rejected. The two frozen fixtures contain 102 and 64 public files respectively, all with matching byte digests; their final frames contain 73 and 33 unique verified signatures. These checks establish consistency of supplied synthetic evidence, not independently observed runtime effects or genuine institutional competence.

**Inspected, not rerun:** the supplied PHP gates. PHP is unavailable in this review environment.

| Supplied gate on the tested commit | Inspected result |
| --- | --- |
| Focused PHPUnit | 216 tests / 5,121 assertions; zero failures, errors, skips or warnings |
| Full PHPUnit | 2,956 tests / 54,448 assertions; zero failures, errors or skips; four documented warnings |
| Container lint | Exit 0 |
| Formation and custody proof producers | Exit 0 each |
| PHP public signature verifier | Exit 0; 106 unique signatures |

JUnit testcase and assertion totals independently reconcile to the report. Final command arrays, timestamps, native exits, runtime identity and autoload location are retained under `gates/final-20260909T170435235355Z`. The four raw warnings match the prior linked-worktree `.git/HEAD` reads in DeploymentCustodyCrashDemonstration:290, OperationalConstructionCrashDemonstration:399, TerminalRetirementCrashDemonstration:183 and UnknownProviderOutcomeCrashDemonstration:245. Historical formation evidence remains 2,729 tests / 53,069 assertions in its original scope; the new totals do not replace that record.

## Qualifications and documentation reconciliation

No executable correction campaign is justified by this review. One status discrepancy needs a current documentation addendum: this packet repeatedly calls FC independent acceptance pending. The completed `citadel-formation-custody-independent-review.md` already accepts final FC commit `daafb83f45978f1d203c9cba693bfaf96a1cd4ab`, tree `3826740810f42c90e6d8b6e00686947003ab62c4`, within offline scope. Preserve the submitted reports and manifests as historical evidence; record the acceptance in a new current pointer instead of rewriting them or rerunning FC solely for that wording.

Acceptance remains limited to trusted local storage/cooperating lock users and synthetic offline infrastructure. It proves neither administrator rollback resistance nor filesystem power-loss durability. It supplies no real provider billing/cancellation guarantee. No live adapter, provider selection, authentication workflow, native enrollment, actual Courtthane/Locksmith appointment, genuine formation competence, Oracle activation or mission execution is accepted by this result. `DEFER_ENROLLMENT` and the unresolved B1 remote contract remain unchanged.

The next integration gate is publication of the exact accepted source and PR/CI review. Publication and remote CI for this CY head were not verified in this review. Integrating source is separate from commissioning.

## Revised O0 — contracts and authority map after Courtyard

Status: **REVISED_PLANNING_ONLY_ONBOARDING_DEFERRED**. This section supersedes the earlier conceptual O0 outline. O1–O5 are not started. Before a future local run, resolve the then-current integration commit and tree, confirm this accepted CY source is included, and account for intervening changes. Do not call the submitted local commit a merged baseline.

### Settled owner intent

The future operator experience is: onboarding chooses a supported provider/authentication route, obtains the applicable bounded owner policy, mechanically selects the base model, establishes Augur through legitimate existing authority, obtains a bounded assessment of that provider's bootstrap models, and applies permitted assignments. Those assignments persist until the operator explicitly changes them. No automatic fallback, reassignment or self-approved Augur replacement is allowed.

The base model means the least costly eligible model satisfying the initial Augur requirements under a defined comparison workload and evidenced pricing. It is not simply the lowest advertised token price. Catalogue, capability and pricing uncertainty must be explicit. Deterministic setup work needs no cognition.

### O0.1 — fix institutional scope before wiring

Retain Citadel as jurisdiction and Courtyard as reception/formation. Courtthane is the exact `courtyard.courtthane` LEGATE; Castellan oversight stays deferred. Oracle/Augur remains separate at `oracle.augur`. Seneschal belongs to the eventual mission Curia. Onboarding is an operator/infrastructure function; it does not establish a new Office or grant Courtthane bootstrap powers.

Reuse original Citadel IDs, trust/custody roots and retained wire/storage identities where the CY compatibility contract requires them. A Courtyard-facing command must not create a second provider budget or claim namespace. Do not translate signed Castellan evidence, clear reservations, reopen interviews or create a Curia to obtain permission for bootstrap.

### O0.2 — map the actual prerequisite graph

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

### O0.3 — define provider and authentication capabilities

Define one adapter contract declaring supported authentication, exact permitted destinations, provider/model IDs, request serialization, response/usage evidence, limits and failure semantics. First implementation scope remains one provider's API-key route. No provider has been newly selected by this plan; the existing DeepSeek adapter is evidence of a legacy path, not an owner selection for onboarding.

Define OAuth as an explicit extension capability. Unsupported browser/device flows must be shown as unsupported, not implied by generic bearer-token support. Token acquisition/refresh remains infrastructure work behind credential custody. O0 uses source and public supplied evidence only; it does not authenticate, read live credentials or query provider accounts.

Configuration, authenticated access, model assignment and permission to invoke are distinct states. Record the source/adapter version, exact binding and separate owner authority needed to advance each. Keep credentials and refresh material out of prompts, public status, manifests and logs.

### O0.4 — make base-model selection reproducible

Specify minimum Augur capability requirements, eligible catalogue scope, account/access evidence, catalogue/pricing freshness, comparison workload, maximum acceptable cost, timeout semantics and stable tie-breaking. Produce an explanation from retained input facts. Refuse when no candidate qualifies or the required evidence is unknown. Base selection performs no model call itself. Any later catalogue fetch, credential test or paid evaluation must have its own applicable bounded authorization.

Distinguish a proposed binding from a persisted authorized assignment. Pin exact supported model identifiers and configuration; disclose any inability to pin the provider's underlying model revision. Provider-side alias changes must not silently be represented as operator-approved reassignment.

### O0.5 — define persistent consent and recovery

Define which initial bootstrap policy permits mechanical application of Augur's result and which deviations require an explicit operator decision. Specify exact provider, role, model, configuration, limits, source evidence and policy/version bindings. An authorization may cover the bounded sequence without repetitive approval prompts, but it cannot supply missing institutional authority.

Settings remain until explicit operator change. An unavailable or newly ineligible model stops the affected operation. Refreshing credentials must not change the provider/model binding or enlarge scope. Interruption/restart does not repeat a paid evaluation or consume authority again. Unknown outcomes retain exposure and cannot trigger an automatic retry or refund. Do not claim a local timeout guarantees remote cancellation, zero billing or completed delivery; retain B1 as unresolved where applicable.

### O0.6 — freeze the shared CLI/runtime interface

Define proposed command inputs, outputs, status codes and persisted transitions for onboard, status and resume before parallel implementation. Label proposed commands as unimplemented. Reuse the implemented `imperium:courtyard:intake`, `:formation`, and `:prepare` interfaces where relevant; keep their historical aliases and shared domain.

The CLI must distinguish configured, missing credential, authentication pending, base selected, missing Augur authority, assessment authorized, result pending, assignment applied, refused and outcome unknown. It should explain the next permissible action without printing secrets or suggesting broad activation as a remedy.

If later authorized for parallel work, assign provider/custody/runtime wiring to one agent and CLI/status/resume work to another in separate worktrees with explicit file ownership. Both consume this contract. One integration owner verifies the combined commit; neither agent changes authority semantics independently.

### O0.7 — deliverables and exit gate

Proposed repository deliverables for a future O0 run:

1. `docs/provider-onboarding-prerequisites.md`: exact transition/source/authority matrix and unresolved prerequisites.
2. `contracts/provider-onboarding.md`: adapter/authentication, state, consent, selection and recovery contract shared by CLI and runtime.
3. `docs/provider-onboarding-base-model-policy.md`: eligibility, comparison cost, freshness, tie-break and persistence rules.
4. `docs/handoffs/provider-onboarding-o0-report.md`: actual baseline, decisions, evidence, implementation division and blockers.

These are proposed outputs, not files already added to the repository. O0 is complete only when the interface and authority graph are concrete enough to implement without silently making owner policy or founding-authority decisions. If a decision is indispensable, finish the other useful work and present the exact unresolved transition. No implementation of O1–O5 or live activation is authorized by this planning revision.

The subsequent mission flow remains: Courtyard receives → authorized Courtthane interview → attributable understanding closes interview → separate exact drafting approval → numbered proposal → separate mission approval → legitimate constitution/appointments → handoff → Seneschal receiving assessment → separate execution gates.

## Next executable owner action

For the completed CY campaign, publish only the accepted branch after these PowerShell checks. Stop on any mismatch or unexpected local changes. This does not run the deferred onboarding campaign or modify the installed application.

```powershell
Set-Location 'E:\htdocs\imperium-courtyard-identity'
$cyHead = '8f5e2052bf99b747f51320dfce2567d31d6fd0da'
$cyTree = '55b100f65ac30ed2d1ea92261de14bea8f09c831'
$cyBranch = 'codex/courtyard-identity-mission-formation'
$branch = git branch --show-current
if ($LASTEXITCODE -ne 0 -or $branch -ne $cyBranch) { throw 'Unexpected branch.' }
$head = git rev-parse HEAD
if ($LASTEXITCODE -ne 0 -or $head -ne $cyHead) { throw 'HEAD differs from accepted source.' }
$tree = git rev-parse 'HEAD^{tree}'
if ($LASTEXITCODE -ne 0 -or $tree -ne $cyTree) { throw 'Reviewed tree mismatch.' }
$status = git status --porcelain
if ($LASTEXITCODE -ne 0 -or $status) { throw 'Worktree is not clean; preserve and inspect changes.' }
$origin = git remote get-url origin
if ($LASTEXITCODE -ne 0 -or $origin -notin @('https://github.com/gatomontes/imperium-symfony.git', 'git@github.com:gatomontes/imperium-symfony.git')) { throw 'Unexpected origin.' }
git push -u origin $cyBranch
if ($LASTEXITCODE -ne 0) { throw 'Push failed.' }
$remoteHead = git ls-remote --heads origin "refs/heads/$cyBranch"
if ($LASTEXITCODE -ne 0 -or -not $remoteHead -or ($remoteHead -split '\s+')[0] -ne $cyHead) { throw 'Remote HEAD mismatch.' }
```

Then review the exact PR head and CI before integration. Add current CY/FC acceptance pointers in a separate traceable documentation change, preserving the accepted source identity and original evidence. Launch onboarding only after its deferral is explicitly lifted.

*Ne passum quidem regrediemur.*
