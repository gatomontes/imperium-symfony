# Provider Onboarding O0 — independent review

Verdict: **O0_REVIEWED_WITH_BLOCKERS — PREPARATION EVIDENCE VERIFIED; COMPLETION / IMPLEMENTATION EXIT GATE NOT MET.**

The four preparation documents are coherent and candid about missing authority. Their principal source findings are substantiated. They may be retained as reviewed preparation, but O0 must not be marked complete or implementation-ready under O0.7. This review authorizes no publication, merge, implementation, activation or live commissioning.

CY/FC remain **ACCEPTED within their existing local/offline software scopes and integrated**. No CY/FC corrective campaign is required by this review. `DEFER_ENROLLMENT`, unresolved B1, deferred O1–O5 and the five false operational flags remain intact.

## Reviewed identities

| Item | Verified identity |
| --- | --- |
| Campaign-selection entry | `6adc635ed9d89bbff7b472cc050de4c6e86d418c` |
| Entry tree | `99e348854b07d9ad95d7ebaed98f6f4bbb83155e` |
| Submitted candidate | `b8021914bbcfdcd990949ea332b308f5c042cbbe` |
| Candidate tree | `ec25528267f1b18bbe3b13356835d098e11cf63d` |
| CY integration ancestor | `79282773b0c1ca93aa17303d46e784b5415cad7e` |
| Review ZIP SHA-256 | `a0187557dd3def84334fa8b7e27332d8e42d589240c2bfa09af0b3c30bf41bed` |

The candidate has the stated entry as its parent. I independently fetched the repository into a disposable bare clone, verified CY ancestry and the incremental bundle prerequisite, imported the bundle locally, and compared the actual Git objects. The entry is also recorded by [GitHub as the O0 campaign-selection merge](https://github.com/gatomontes/imperium-symfony/commit/6adc635ed9d89bbff7b472cc050de4c6e86d418c).

The complete entry-to-candidate tree diff contains exactly four Markdown additions:

- `contracts/provider-onboarding.md`
- `docs/handoffs/provider-onboarding-o0-report.md`
- `docs/provider-onboarding-base-model-policy.md`
- `docs/provider-onboarding-prerequisites.md`

The actual diff and changed-path list match the packet byte-for-byte. No executable source, configuration, lockfile, CY/FC acceptance document or historical review changed in this candidate.

## Evidence attribution and post-check changes

**Checks I reran:**

- Outer archive SHA-256 against the separately supplied checksum; safe archive-path inspection before extraction.
- The inspected `verify-payload.py`: exact payload file set and hashes, source Git-blob identities, and raw commit/tree declarations passed.
- Bundle verification against independently fetched history; exact imported head/tree, entry/final raw commit bytes, CY ancestry, complete diff comparison and `git diff --check`.
- All 68 referenced source snapshots against both the entry and candidate Git blobs, including byte counts. All four new documents against candidate Git blobs and the four standalone attachments; standalone copies are byte-identical, with no newline normalization required.
- Required-source existence, all 54 recorded links and 23 method references; independently extracted links and fence balance in the four deliverables.
- Unchanged Composer lock parsing and all 82 recorded package/version/source-reference pins. No dependency installation occurred.
- Production-source searches at the exact entry for the founding-act, standing-profile-approval and Oracle-research-authorization schemas. Each exact schema occurs only in its consuming production class; no compatible producer was identified. This is a bounded source finding, not a proof about external systems.
- Supplied `git show` outputs against independently fetched source bytes.

**Evidence I inspected rather than reran:** the runner's 14 precommit and 235 final verification command records, all reporting exit 0; its original worktree status; the package-builder source; the four named PHP test sources; and historical CY/FC acceptance and review records. The final log records the candidate, bundle verification and a clean branch status. Its substantive source outputs agree with the independently fetched repository.

**Post-check conclusion:** no discrepancy or undeclared tracked change exists in the submitted candidate. Its source, diff and attachments match the committed result. The original Windows worktree's cleanliness and the assertion that subsequent activity was packaging-only remain supplied observations; I did not inspect that machine or any later unsubmitted changes. The ZIP does not provide independent proof that no activity occurred elsewhere.

No PHP service, application CLI, PHPUnit suite, credential probe, provider authentication or provider request was executed in this review. Historical CI counts are not O0 test results. Hashes establish integrity/content identity; I did not validate an owner signature on this package or treat Git authorship as institutional authorization.

## Actual authority graph

The graph is incomplete in concrete places, not merely awaiting a provider name.

| Transition | Independent source finding | Consequence |
| --- | --- | --- |
| Public inputs → base proposal | Canonical catalogue sealing calls the ledger, which already requires Augur stewardship. Access assertions require the legacy Locksmith binding; the supplied environment probe only detects a nonempty credential. | Pre-Augur public-evidence admission and scoped authenticated-access evidence need legitimate producers. Neither catalogue presence nor key presence satisfies the proposed account-access predicate. |
| Base proposal → founding assignment | `FoundingAugurModelAssignmentService::authorize` checks a supplied act's checksum/fields and writes `PROVISIONAL_FOUNDING_EXCEPTION`. It does not consult the operator-root operationalization seal. | Its `founding_assignment_authority_consumed` output is not proof of globally one-use founding authority. A trusted issuer and currentness/consumption bridge are missing. |
| Assignment → Augur occupancy | `AugurResidentActivationService` consumes Garrison custody, the exact standing-profile approval schema and Recruiter binding. It publishes stewardship/commission-acceptance authority, while invocation, assignment and execution authority remain false. | The missing standing-approval producer and genuine qualification chain cannot be replaced by fixture records or an ACTIVE label. |
| Fresh founding / existing installation | Root installation refuses after the operationalization seal and produces `imperium.operator-root-seat-occupancy/v1`, distinct from legacy Augur occupancy. | The fresh constitutional path exists, but its compatible integration is missing. Existing installations require governed prospective cutover; never reopen the founding window. |
| Augur occupancy → paid assessment | The configured governance resolver list contains no Oracle resolver. The generic invoker consumes native claims and uses fixed legacy DeepSeek configuration. Formation session authority supports interview, drafting and acceptance only. | No compatible operator-policy-to-bounded-Augur-invocation corridor is demonstrated. Courtthane interview authority cannot substitute for it. |
| Catalogue → evaluation candidates | Ledger `validateRecord` neither accepts nor emits `platform_service` / `runtime_model`; evaluation `freeze` requires them and excludes candidates without them as `RUNTIME_BINDING_INVALID`. | A real producer-to-consumer integration gap exists. `ModelRequirementCommissionFlowTest::snapshot` supplies these fields directly, so that test does not prove the production connection. This finding is source inspection, not a newly executed failing test. |
| Recommendation → persistent assignments | Oracle recommendation produces a pending Curia selection authority; `ModelSelectionPlanningDecisionService` produces a planning-only binding requiring dossier/Imperator review and grants no assignment authority. | There is no demonstrated bootstrap-policy application consumer. A provisional mission Curia would not cure the missing competence. |

Relevant source is pinned in the packet under `source/src/Imperium/Runtime/`, particularly the named Imperator, Conscription, Bootstrap, Oracle, Curia, Clavium and Formation services, plus `source/config/services.yaml`. The inspected tests construct acts, approvals, authorizations and catalogue records directly. They support local consumer behavior, not a genuine end-to-end onboarding claim.

## Provider/authentication contract

The proposed contract appropriately separates configuration, credential observation, authenticated access, base selection, Augur authority, assessment and assignment. It specifies one deployment-selected adapter, fixed destinations, prepared request identity, response/usage attribution, explicit uncertainty, and one shared custody/budget domain. Preview/status are pure; resume is evidence-only; unknown outcomes retain exposure without retry/refund.

The first scope is one provider's API-key route. **No provider is selected.** Legacy DeepSeek wiring is neither that selection nor conformance evidence: its adapter returns text and does not implement the proposed structured wire/usage contract. OAuth browser/device flows and token lifecycle remain unsupported/deferred. `env:`, `clavium://` and FC identifiers require an explicit mapping; the environment broker explicitly reports no cross-process custody support.

This is a useful interface draft, not a frozen implementable contract. Policy authority ingress and its issuer/trust/currentness bridge are explicitly unresolved under D2; the public owner-policy schema and provider-specific adapter capabilities still require closure before separate implementers can safely consume them.

## Base-model policy and persistent consent

The algorithm correctly defines base as the least costly **eligible** candidate under frozen workload/evidence, rather than the lowest advertised token price. It specifies exact arithmetic, conservative billable meters, PASS/FAIL/UNKNOWN eligibility, separate evidence ages, bounded catalogue coverage, stable UTF-8 tie-breaking and retained explanations. It grants no model call or assignment authority. No real tariff, model capability or winning candidate was asserted or verified.

Its required workload, measurable Augur requirements, evidence/access rules, budgets, time/call limits and assignment scope remain unresolved. Consequently it is reproducible as an algorithm specification, but cannot yet produce an authorized selection. Those values must not come from legacy defaults or tests.

The consent design follows the settled intent: one bounded initial decision may cover assessment and one exact mechanical application without repetitive prompts. Institutional and resource authority still must pass independently. Assignments persist until explicit operator change; outages, cheaper models, credential rotation and new catalogue evidence cannot replace them. Unknown outcomes retain original attribution/exposure. Alias revision limitations require explicit acceptance, and Augur cannot authorize its own replacement.

These are sound proposed rules. Persistence, atomic application, currentness and recovery are future obligations, not operational proofs furnished by O0.

## Blockers and next executable action

| Blocker | Closure required | Gate affected |
| --- | --- | --- |
| D2 — authority integration | Specify exact competent issuers, production artifacts, trust ingress, stores, currentness/revocation, consumption and consumers for pre-Augur evidence/access, fresh founding or existing cutover, standing approval, bounded assessment and persistent application. Include the catalogue runtime-binding bridge without rewriting historical records. | O0 authority/interface closure; implementation selection |
| D1 — provider | Owner chooses one provider and API-key route; assess that adapter's exact supported identifiers, destinations and evidence capabilities. | Provider-specific contract and implementation scope |
| D3 — policy | Present and approve concrete Augur thresholds, workload, evidence ages/access criteria, budgets/time/calls and exact role/model/configuration scope, including initial mechanical-application permission. | Executable policy and O0 closure |
| D4 — B1 | Preserve the unresolved remote cost/time/cancellation requirements; later establish acceptable evidence or obtain an explicit amendment with disclosed residual exposure. | Live adapter acceptance/commissioning; does not prevent further offline design |

**Next executable action: continue O0 with an authority-design and owner-decision closure pass, beginning with D2.** Have the executor prepare a concrete proposed bridge and decision sheet for review. The owner should decide competence, provider, scope and resource limits; the executor should supply the engineering proposal rather than ask the owner to invent classes, stores or protocols. Provider choice alone does not close D2.

Suggested next local instruction, for explicit selection rather than automatic execution by this review:

> Continue Provider Onboarding O0 from local candidate `b8021914bbcfdcd990949ea332b308f5c042cbbe`. Read this independent review and the four O0 deliverables. Prepare a documentation-only D2 authority integration proposal: exact issuer/act schema, trust ingress, store, consumer, currentness/revocation and one-use boundary for each missing transition. Separate fresh founding from governed existing cutover. Specify pre-Augur public evidence/access admission, legitimate standing-profile approval, bounded Augur assessment authority and atomic persistent assignment application. Resolve the catalogue runtime-binding interface in the proposal. Present concrete D1/D3 choices and policy fields for owner decision; mark proposals unapproved and retain D4/B1 as a live gate. Preserve CY/FC acceptance, original schemas and the shared custody/budget domain. No provisional Curia, Courtthane authority substitution, founding-window reset, runtime implementation, private-state access, provider call, activation, push or merge. Return the revised contract, exact remaining owner decisions and a new review packet.

O0 closure should be reviewed again after those decisions and interfaces are concrete. O1–O5 remain deferred until separately selected. No automatic activation or merge was performed.
