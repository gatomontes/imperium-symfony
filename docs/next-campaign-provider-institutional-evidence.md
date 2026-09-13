# PPC1 — native institutional evidence producers

Disposition: **SELECTED_OFFLINE_IMPLEMENTATION** after the owner's “continue” following PPC0 review/integration. Implement the two internal evidence producers required by `ConstitutionEvidence` and `AssignmentEvidence`. This is one local implementation run. Account/access, base eligibility and cognition evidence remain separate unresolved provider-dependent work.

## Entry and finish line

Start from this preparation branch, descending from main `b56c4370f5baa6057dff28fe591bb05b633c6baf`, tree `6579297d73d2a171a875eaca13581fc21e680913`. PPC0 runtime [PR #809](https://github.com/gatomontes/imperium-symfony/pull/809) merged at `5e3c076dba3a147f1a1272641b40db740d7f393c`, tested tree `fcc17865be3eefbca0c43eb3c88a3c5acc075651`. Its complete Linux gate passed 3,712 tests / 63,983 assertions / four skips, all 609 files and 11 guards. Those are historical baseline results, not PPC1 validation.

PPC0 found no existing producer with the complete scope of either port. Repeating that inventory is not the deliverable. Implement the missing native production/evidence bridge under the already-defined competence and existing owners, and connect it to the dormant composition. Production defaults must continue to refuse when genuine inputs are absent. The two new verifiers must make real substantive checks and must pass through actual owner paths in offline tests; an extra registry of booleans or hashes does not satisfy this campaign.

An internal institutional act can create an institutional fact when its competent authority, exact scope and actual publication are proved. That differs from asserting an external provider fact: an operator signature cannot make a tokenizer, account entitlement or tariff true. Keep this distinction explicit in implementation and reports.

Read [PPC0 acceptance](reviews/provider-production-composition-review.md), [evidence matrix](provider-production-composition-evidence-matrix.md), [PPC0 custody contract](provider-production-custody.md), [handoff](handoffs/provider-institutional-evidence-ready.md), the frozen onboarding/continuation/assignment/cognition contracts and actual owners before edits.

## Two producers, one coherent owner path

| Work | Concrete requirement |
| --- | --- |
| Augur constitution | Produce and verify the exact authorized FRESH founding chain and charter/persona/Profile originals for `oracle.augur`, rooted in the existing Human Operator fresh-founding competence. Preserve the admitted finite candidate/Profile tuple, open window, root vacancy/identity and actual owner publication. |
| Profile/binding evidence | Produce and verify substantive Profile predicates and current exact binding/Profile generations for the O4 assignment tuples. Establish how actual approval, qualification, designation and supersession are represented by the competent existing owners; a seal alone is insufficient. |
| Composition | Supply the two fixed verifiers to PPC0's dormant `Composition`. Use the same assignment evidence for application and `PersistentSettings`; retain one coherent root/store/source identity. Keep the other three evidence ports refusing in production. |
| Offline proof | Demonstrate the real producer → original admission → verification → supported FRESH/assignment/resolution paths with disposable authority and mock provider dependencies. Reconstruct in a fresh process and refuse stale, conflicting or unauthorized evidence. |

### Constitutional provenance without circular founding

The continuation contract already identifies `CONSTITUTE_FOUNDING_AUGUR` as the Human Operator's existing FRESH founding competence. Trace its actual admission/consumption path, `FoundingRule`, `FreshProducer`, `Holder` and `OperatorRootOwnership`. Use the already-approved finite tuples; do not create a new issuer class or let a generic signed record authorize founding.

Define separately (a) approved exact artifacts and founding authority available before publication and (b) the actually published current holder and its resident-artifact bindings afterward. Do not require the future holder as a prerequisite for its own creation. Conversely, a prepared artifact package must not be reported as a completed constitution. Do not populate an occupied native installation as a shortcut around the FRESH vacancy fence. Preserve existing atomic publication, replay and recovery behavior.

An artifact match proves identity. The verifier must also establish the competent authority, permitted seat/root/instance/operator, complete approved artifact chain, validity and revocation, and the correct point in the founding lifecycle. Tests must reject a correctly hashed but unauthorized charter/Profile and an authorized artifact from another root or tuple.

### Current Profile/binding facts without stale snapshots

Trace `ProfileApprovalDecisionService`, `ModelBoundProfileApprovalDecisionService`, `ProfileModelBindingSealingService`, existing qualification/current-designation owners, and O4 `ApplicationHistory`, `AssignmentRule`, `PersistentSettings` and formation consumers. A sealed Profile explicitly marked non-current or awaiting activation cannot be promoted by renaming a status. Reuse authentic semantics where supported and implement only the missing native bridge within the existing competence.

For each affirmative predicate, identify the owner-created fact, exact original record and Profile version/content, approved role/model tuple, current generation and supersession/revocation rule. Substantive internal fit findings need the actual governed evidence and decision chain; model output, a generic PASS field or matching an expected fixture does not create them. External capability/access facts remain outside this producer's competence and must still be supplied by their own ports.

Currentness must be checked at the owner boundary where the assignment is applied or used. An immutable approval receipt or a snapshot captured when constructing the service cannot prove current generation later. Demonstrate fresh-process behavior and a competing supersession between proposal and application/use. Work within the existing frame/lock discipline: `AssignmentEvidence::verify()` must not acquire the journal lock, dispatch or substitute tuples. Do not add an ungoverned parallel “current” registry or hide an unsynchronized second-store read behind the port.

## Implementation boundaries

- Existing contracts, public CLI/request schemas, policy values and authority classes remain authoritative. Add typed source content/producer evidence within the existing source/admission model where compatible; no self-declared approval, new trust domain or frozen-schema weakening.
- Add the smallest owner integration needed for these two facts. Do not reopen native enrollment, standing personnel migration, existing-installation cutover, other Seats or general lifecycle redesign. A genuine contract incompatibility must be demonstrated precisely; do not weaken a historical provenance assertion to conceal it.
- Preserve DeepSeek/API key, FRESH, D2-A, approved P1–P9, medium target capacity, least-cost eligible base, persistent operator-controlled whole-set settings and the empty retry allowlist. The [public provider follow-up](provider-production-provider-followup.md) records observations for later reconciliation; it changes no alias or fee policy.
- Keep the default deployment reader and dormant composition. Preview/status must remain physically read-only and must not instantiate credential-bearing infrastructure. Evidence-only resume must never dispatch. Unknown effects retain exposure.
- Use only generated temporary roots, ephemeral fixture keys and exact mock HTTP. Actual internal producer code may execute in those fixtures; their resulting institutional facts are synthetic test facts, never claims about the installed Imperium.
- No real credentials, provider requests, installed-state inspection/copying, deployment, enrollment, appointment, activation or mission execution. Keep `DEFER_ENROLLMENT` and all five operational flags false. Windows is the local development/test host; Ubuntu deployment state is unobserved.

## Validation and review handoff

Write focused adverse tests for the new substantive boundaries: unknown/foreign authority, correct signature with wrong competence, wrong root/seat/tuple, arbitrary but correctly hashed artifacts, premature completion, stale/revoked approval, superseded Profile/binding generation, replay, crash recovery and concurrent currentness change. Exercise application and actual persistent resolution through the same verifier. Prove the untouched provider ports still refuse, and label every mock/synthetic oracle used to reach the internal paths.

Use actual producers/owners in positive tests; a hand-written expected-record verifier or renamed synthetic support class is not acceptance. Preserve original failure evidence and distinguish unsupported external predicates from missing internal implementation. If a frozen owner contract cannot express a necessary institutional fact, complete supported work and return the exact incompatibility and smallest proposed amendment. Do not silently widen scope or report both ports complete while they still always refuse.

Commit executable changes before final gates. Run meaningful focused tests and the unchanged complete eight-partition PHPUnit/source/coverage gate before integration, with all 11 guards. Record actual Windows versus Linux results and do not reintroduce the replaced 1,800-second serial gate. Do not change dependencies, CI, `config/services.yaml` or pinned shared-writer provenance merely to pass validation.

Return `docs/handoffs/provider-institutional-evidence-report.md`, an exact producer/competence/lifecycle matrix, changed-test map and future public-input runbook. Return the public-only source/evidence ZIP, complete file/mode/byte/hash manifest, tested/final commits and trees, exact implementation/post-test diffs, bounded Git bundle and SHA-256 verification instructions. Keep private installation material and real credentials out of source and packets. Stop at local commits for source review and fresh complete CI before implementation publication/integration.

Engineering success is **TWO_INTERNAL_EVIDENCE_PORTS_IMPLEMENTED_OFFLINE**, supported by actual owner paths and tests. Genuine installed readiness remains unclaimed; account/access, base and cognition sources still need separate resolution. PPC0's partial production disposition is not automatically closed. No next provider-policy amendment or live campaign is selected by PPC1.
