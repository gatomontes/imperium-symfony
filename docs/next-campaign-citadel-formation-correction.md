# Citadel session refusal and handoff recovery correction

## Current disposition — commissioning readiness preparation

Citadel Stages 1–3 and CF01/CF02 are accepted within the reviewed local/offline
scope. See [independent acceptance](citadel-formation-correction-acceptance.md).
Exact reviewed-tree integration: 645d53bdbb80d537ef0a7f226b8ad48f192ea1ef.
Pending-review and local-only statements below describe preserved earlier runs.

Current campaign: [Citadel operational readiness](next-campaign-citadel-operational-readiness.md).
Current runner: [local readiness prompt](handoffs/citadel-operational-readiness-ready.md).

R0: actual public prerequisites and producer/consumer map.
R1: read-only preflight, owner artifacts and supported dormant adapters.
R2: integrated offline rehearsal and exact owner commissioning runbook.
R3: committed-code tests, readiness disposition and independent-review package.

Flow: accepted formation mechanics -> verified public prerequisites and bounded
transport -> reviewed owner commissioning package -> separately authorized
commissioning -> first live bounded Castellan interview. Understanding still
precedes separate drafting approval; mission approval, child handoff and receiving
assessment remain distinct. This local campaign activates none of those effects.
Preserve earlier test packets, owner ceremonies and Delegate Steps 1–69.

## Historical campaign record


Status: LOCAL_CORRECTION_COMPLETE_PENDING_INDEPENDENT_REVIEW.
CF01 and CF02 are CLOSED_LOCAL; independent acceptance of Stages 1–3 remains pending.
This bounded continuation addresses two source-traced review findings. It does not
restart formation, revisit accepted policy, or commission the installed Citadel.
Runner: [local prompt](handoffs/citadel-formation-correction-ready.md).
Policy: [accepted decisions](citadel-mission-formation-decisions.md).

## Exact starting evidence

- Local candidate: e0386e75ce7619fbbeaac450af078d2d606df012.
- Tested tree: 54b32f189130d421ce7cd74a54b8e11e200ace60.
- Candidate branch: codex/citadel-formation-stages-1-3.
- Candidate worktree: E:/htdocs/imperium-citadel-stages.
- Candidate base / remote main at preparation: 35f4c3bbcb0a010a6c4b12a51bf13126c3a33ac1.
- Archive: citadel-formation-e0386e75ce76.zip.
- Archive SHA-256: 9f2f8196246edb9c11721ac7f6f890c7503ddd06ef695e14ccd4a9be372773da.

Review verified archive integrity and all 1,828 manifested candidate entries.
The supplied JUnit records 2,709 tests and 52,902 assertions, with no test
failures/errors/skips; four linked-worktree warnings remain qualified. Formation
focused proof reports 20 tests / 111 assertions. Synthetic signatures and cognition
provenance were independently checked. PHP was unavailable to the reviewer:
these test runs were inspected, not rerun, and the two findings below are
source-traced until C0 reproduces them locally.

## Findings and acceptance criteria

### CF01 — refused interview session can reopen indirectly

FormationCognition::control blocks REFUSED -> OPEN but permits
REFUSED -> DEFERRED -> OPEN with authentic control decisions, reviving the original
session grant. Make refusal terminal across every subsequent transition, including
previously signed or replayed OPEN decisions. Preserve immutable history and
aggregate exposure. New work requires a new legitimately granted session.
Never-refused DEFERRED sessions may resume under their unchanged valid grant.

Test direct and indirect reopening, old-control replay, remaining budget, and
zero provider/credential activity after refusal. Retain the valid defer/resume
positive case. Do not replace session grants with per-question approvals.

### CF02 — completed child effect cannot reconcile after approval expiry

CuriaFormationService::deliver validates current approval before reconciling an
already-published exact child receipt. If the child write completes and the process
stops before parent publication, crossing approval expiry strands
EFFECT_UNCERTAIN_IDENTITY_FENCED. expireUnused intentionally cannot release it.

Separate permission to cause a NEW child effect from recognition of an ALREADY
completed effect. Recover only the exact immutable child receipt bound to the
retained prepared handoff, reservation, source/target identities, original
authority and trusted historical provenance. Establish what the retained records
actually prove; receipt presence alone is insufficient. Missing or unverifiable
historical authority must remain fenced with a concrete diagnosis.

Recovery must not bypass expiry for new creation, backdate fresh authorization,
renew resources, recreate a child, repeat cognition, or manufacture acceptance.
Current expiry or later changes must not erase a verifiable historical effect;
record recovery as that historical fact without reviving authority. Preserve
revocation semantics and explicitly test the before-effect/after-effect boundary.

Use the real child publication path, interrupt after its durable receipt write
but before parent publication, advance the clock beyond approval expiry, then
reconcile once. Repeated/concurrent recovery must preserve one child, handoff and
occupancy. Absent, wrong-identity, mismatched or corrupt receipts stay fenced;
unexpired ordinary delivery and fresh expired-approval refusal still hold.
Do not simulate the central positive proof solely by manually inserting a receipt.

## Local sequence and flow

| Step | Work | Completion evidence |
| --- | --- | --- |
| C0 | Pin candidate and reproduce CF01/CF02 through production paths | Two failing regression traces attributed to exact candidate |
| C1 | Make session refusal terminal | Negative transition/replay tests and valid defer/resume |
| C2 | Reconcile an already completed child effect under its retained fence | Real publication/interruption/expiry recovery and adverse cases |
| C3 | Integrate, audit and package corrected candidate | Focused and full tests on committed code; fresh manifest and handoff |

Flow remains: durable Citadel intake -> bounded Castellan interview -> attributable
understanding -> separate drafting approval -> numbered proposal -> exact mission
approval -> reserved Curia constitution/handoff -> receiving Seneschal acceptance
or concrete gap -> Step 1 schema/reference validation without execution.
CF01 makes refused sessions terminal. CF02 lets verifiable completed effects be
reconciled without granting new effects. Unknown effects retain their identity fence.

## Previous-test disposition and closure

Preserve the original candidate branch/commits, archive, manifests and transcripts
byte-for-byte. Keep existing tests. Add meaningful regressions; change old tests
only where justified and record a changed-test map. Old passing results remain
historical evidence and do not establish closure of these findings.
Do not rerun owner ceremonies, create accounts, rebuild the installation, replay
completed missions, or reissue consumed authority. Use fresh disposable roots and
synthetic keys only for local tests.

Run the established focused formation tests and offline command/DI demonstration,
then php vendor/bin/phpunit tests on committed executable code with locked
dependencies. Record exact commands, PHP/environment, tested commit/tree, counts,
warnings and limitations. Resolve warnings only as necessary to establish test
validity; do not hide them. Verify no executable change after the tested commit.

Update the implementation guide, runtime contract if semantics need clarification,
current campaign, steps and flow. Return a sanitized correction report, changed-test
map, complete relevant source, reproduction/corrected evidence, offline demonstration,
allowlisted ZIP and SHA-256 manifest. If final documentation follows testing, record
its separate commit/tree and verify that its changes are documentation only.

Finish LOCAL_CORRECTION_COMPLETE_PENDING_INDEPENDENT_REVIEW only when both findings
have evidence-backed closure. Otherwise name the remaining finding precisely.
The GitHub publication of these instructions does not accept or merge the local
implementation. This local runner commits corrections locally and returns the
review package; corrected implementation integration follows review.

No live transport, credentials, real signing/commissioning, private payroll
transmission, installed-state mutation, bootstrap/Profile activation, deployment,
mission execution, branch deletion or force push is part of this correction.
Existing authority decisions, institutions and completed Delegate Steps 1–69 remain.

Nulla requies impiis.

## Recorded local C0–C3 completion

C0 reproduced both findings against the reviewed executable source. C1 terminal
refusal and C2 exact historical-effect recognition are implemented and committed
locally. C3 full-suite verification on `1a978ae42fbeaab55437768b88818fb40ba88676`
(tree `7ef164554789ca46c51dd00f0d846d2b8b82c967`) passed 2,729 tests and 53,069
assertions, with no failures/errors/skips and four unchanged historical warnings.
Focused and command/DI proof passed. Both finding dispositions are CLOSED_LOCAL.

See [the correction report](citadel-formation-correction-report.md),
[changed-test map](citadel-formation-correction-changed-tests.md) and
[review handoff](handoffs/citadel-formation-correction-review.md).
The ZIP/manifest identify the tested executable tree and separate documentation
review tree. Original evidence and worktrees remain preserved. No corrected-code
push/merge, live call, real key, commissioning, activation or execution occurred.
