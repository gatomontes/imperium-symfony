# Citadel formation: local implementation and review guide

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


The implementation covers durable intake and bounded interview, separately
authorized drafting and numbered mission review, and exact child-Curia formation
through attributable receiving acceptance and non-executing Step 1 validation.
The proof uses production commands and consumers in generated synthetic roots.
The default production transport refuses live cognition.

The reviewed candidate `e0386e75ce7619fbbeaac450af078d2d606df012` is preserved.
Its source-traced CF01/CF02 findings are addressed by the bounded local correction
on `codex/citadel-session-handoff-correction`; see the
[correction report](citadel-formation-correction-report.md) and
[changed-test map](citadel-formation-correction-changed-tests.md). Earlier passing
results remain historical evidence and do not establish correction closure.

The accepted decisions and historical Batch 0 artifacts are unchanged. Source
baseline is `35f4c3bbcb0a010a6c4b12a51bf13126c3a33ac1`, in a fresh worktree on
`codex/citadel-formation-stages-1-3`. The original source-review checkout and all
historical worktrees, packets and installation state are preserved. No push or
merge is part of this run. Exact tested identities and results belong in the
result packet's `verification.json`, rather than an inferred “latest main.”

## Implementation map

| Stage | Production boundary | Proof |
| --- | --- | --- |
| 1 | `CitadelIntakeCommand`, immutable `FormationJournal`, native institution resolution, signed Profile/appointment chain, authenticated resource-decision v2, atomic exposure/lease/claim and sealed response admission | Exact original bytes; sibling-process duplicate admission; understanding with dissent; bounded sessions; revocation, succession, changed intent, concurrent reservation and unknown recovery |
| 2 | Exact drafting request and Charter, derived Planning Authorization, complete Step 1 proposal schema, immutable numbered versions, authentic review | No proposal in interview response; no drafting before its decision; explicit investigation stop; prior objection and revision retained; exact version/line checks; fresh legacy entry refused |
| 3 | Generation-bound reservation, MasterMason child receipt, three exact appointments, complete evidence closure, target Seneschal assessment, child receiving receipt | No child before mission approval; duplicate and interrupted delivery reconcile one identity; changed registry reassesses without unchanged reapproval; accepted and concrete-gap returns; Step 1 validates without execution |

The [runtime contract](../contracts/citadel-formation-runtime.md) records the
Castellan retirement correction, institutional role mapping, signed authority
protocol, Charter/authorization mapping, historical boundary and limitations.
The [command schema](../contracts/citadel-formation-command.schema.json) defines
the ordinary operation envelope. The new runtime inventory supplements the
frozen transactional inventory; it does not rewrite historical coverage evidence.

## PowerShell reproduction

Run from the isolated implementation checkout with PHP 8.4 and locked dependencies.
The demonstration creates its own temporary root; it accepts no runtime path,
provider, key or transport override, and removes only that generated root.

```powershell
Set-Location E:\htdocs\imperium-citadel-stages
php --version
New-Item -ItemType Directory -Force var/citadel-formation-proof | Out-Null
php vendor/bin/phpunit tests/Imperium/Runtime/CitadelMissionFormationTest.php
php tools/prove-citadel-formation.php > var/citadel-formation-proof/offline-demo.json
php vendor/bin/phpunit tests
Get-FileHash var/citadel-formation-proof/offline-demo.json -Algorithm SHA256
```

The demo exports only synthetic public evidence, signatures, institution records,
session accounting, three fake calls, the full handoff, acceptance and Step 1
validation. Its ephemeral signing keys stay in memory. Synthetic byte tokens and
tariffs are not a statement about any real provider's pricing or limits.

The normal production commands, in an independently prepared runtime, are:

```powershell
php bin/console imperium:citadel:intake submission-identity request.txt
php bin/console imperium:citadel:formation operation.json
```

`request.txt` is preserved byte-for-byte. The first command neither assumes that
its caller is the Imperator nor grants spending permission. Ordinary formation
requests contain only `operation` and `arguments`; root, verifier, clock and
transport are not accepted as command options. The result contains generated
identities that subsequent operations reference. Do not invent internal IDs.

| Operation | Arguments |
| --- | --- |
| `personnel-authority-source` | `role` |
| `delegate-personnel-evidence` | `delegation`, `decision` |
| `record-personnel-evidence` | `envelope` |
| `appoint-castellan`, `appoint-locksmith` | `candidate`, `decision` |
| `revoke-decision` | `envelope`, `nonce` |
| `reply` | `intakeId`, `content`, `changedIntent`, `decision` |
| `drafting-request` | `intakeId`, `charter` |
| `authorization-source` | `intakeId`, `phase` |
| `grant` | `intakeId`, `phase`, `terms`, `decision` |
| `control-session` | `sessionId`, `disposition`, `decision` |
| `call`, `recover-response` | `sessionId`, `attemptId` |
| `present-mission` | `intakeId`, `version`, `appointments`, `expiresAt` |
| `review-mission` | `terms`, `disposition`, `lineDigests`, `rationale`, `decision` |
| `reserve-mission` | `reviewId` |
| `deliver-handoff`, `expire-unused-reservation`, `validate-step-one` | `intakeId` |
| `route-mission` | `missionId`, `decision` |

The `authorization-source` and `present-mission` outputs must be included exactly
in the subsequent signed terms. A mission-review signature covers
`{terms, line_digests, rationale}`. A candidate references `persona`, `suitability`,
`profile`, `examination`, `qualification`, and `profile_approval`. The Profile and
individual Senate findings are independently signed institutional evidence, not
owner-authored eligibility flags. See the demo for complete working synthetic
envelopes and the test fixture for external signer behavior.

## Recovery and migration

An unknown provider outcome consumes the reserved maximum and requires explicit
reconciliation. `recover-response` admits an existing exact sealed return if
current authority and lineage still permit it; it never transmits again.
Deferral preserves the session; resumption rechecks expiry and revocation.
An expired session needs a new exact decision, not a reset of its ledger.
Refusal is terminal across status changes and retained control history, including
stale signed controls. Legitimate never-refused resume preserves the remaining budget.

Retry delivery with the original intake identity. A prepared or uncertain
constitution retains its identity fence. It cannot be released merely because
time passed. Receiving receipt recovery uses the existing admitted assessment.
Changed intent after a formation effect needs a separate mission amendment;
it cannot rewrite the delivered original conversation.

The correction distinguishes already completed child publication from permission
to create a child now. Exact retained receipt bytes, the original journal fence,
signed authority and institutional publication provenance permit read-only
recognition after later expiry or revocation. Missing or unverifiable provenance
remains fenced. Older receipts are not retroactively stamped. Recognition neither
repeats cognition nor manufactures receiving acceptance. See the runtime contract
for the serialized revocation boundary and trusted-custody limitations.

Correction proof uses a fresh `var/citadel-correction-proof` directory. Run:

```powershell
php vendor/bin/phpunit tests/Imperium/Runtime/CitadelFormationCorrectionTest.php tests/Imperium/Runtime/CitadelMissionFormationTest.php
php tools/prove-citadel-correction.php
php tools/prove-citadel-formation.php
php vendor/bin/phpunit tests
python tools/package-citadel-correction.py
```

The packager requires successful verification metadata and a clean review tree.
It rejects executable changes after the tested commit and refuses to overwrite
an existing packet. The original formation packager and all earlier outputs remain.

Existing historical stores remain readable. A fresh deterministic plan or a
missing origin field cannot bypass the Citadel drafting gate. Explicit exact
historical inventory enrollment is a deployment migration act, absent from the
ordinary CLI. Historical synthetic fixtures use that actual validator. The old
production profile smoke driver is barred; its historical consumer-chain proof
now lives in test support, so it cannot supply fresh production authority.

## Later readiness, outside this run

Real public-trust enrollment and exact institutional incumbent lineage must be
reviewed in the intended installation. Placeholder Officers do not satisfy the
formation evidence adapter. A different governed successor schema needs its
specific adapter rather than an invented currentness assertion. Actual Persona
suitability, Senate judgment and Castellan competence are not proven by scripted
responses. The explicit present-material Charter path does not implement new
Office investigation commissions.

Any real transport must demonstrate enforceable pricing, limits, trustworthy
usage, disclosure and the applicable custody/external-boundary chain before it
can replace the refusing adapter. The packet neither activates those prerequisites
nor claims live readiness. The final local boundary is a received, attributable
synthetic handoff and schema/reference validation, with no mission execution.

## Correction verification result

`LOCAL_CORRECTION_COMPLETE_PENDING_INDEPENDENT_REVIEW` at executable commit
`1a978ae42fbeaab55437768b88818fb40ba88676`, tree `7ef164554789ca46c51dd00f0d846d2b8b82c967`.
Full suite: 2,729 tests / 53,069 assertions, no failures/errors/skips,
four unchanged historical worktree warnings. Focused checks, both offline
command/DI demonstrations and container lint passed. See the correction report
for exact commands and test mapping. `python tools/verify-citadel-correction-proof.py`
checks the exported public signatures and journal/receipt integrity.
Final documentation is a separate commit; the manifest records both trees.
