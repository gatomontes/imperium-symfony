# Citadel formation: local implementation and review guide

Current identity update (CY0–CY3): [Courtyard runbook](courtyard-identity-runbook.md)
and [compatibility contract](../contracts/courtyard-identity-compatibility.md)
control fresh reception/formation. Courtthane requires the exact new Seat and
appointment; old Castellan evidence grants no successor or oversight authority.
Citadel jurisdiction, Seneschal's Curia mandate, B1 and DEFER_ENROLLMENT remain.
FC0–FC3 independent acceptance remains pending. The prior campaign entries and
examples below retain their historical attribution; use the linked current
runbook for implemented command names and future prerequisites.

## Current disposition — IR01 corrected locally, independent review pending

**IR01_CORRECTED_LOCAL_PENDING_INDEPENDENT_REVIEW_COMMISSIONING_BLOCKED**.
See the [IR01 correction report](citadel-ir01-correction-report.md) for source,
changed tests, exact identities, and fresh proof. UNDERSTOOD closes interview
use across the intake and existing grants; stale work and controls cannot revive
it. Original recovery and separately authorized drafting remain supported.
Signed reply remains the explicit discussion transition; completed grants stay closed.

Tested commit `41e7ef45c71b42a3d118cccfb435b240d7690466`, tree
`3e89cd506282486dca22fa3ff96539cd44846037`: focused 57 tests / 399 assertions;
full suite 2,746 tests / 53,196 assertions, no errors/failures/skips, four unchanged
historical warnings. Offline handoff and non-executing Step 1 passed.

Current steps: independent review of the corrected packet, then the runbook's
public installation/custody/authority evidence and B1 decision, then separately
authorized commissioning only when prerequisites are resolved. Default transport
still refuses. CF01/CF02 acceptance and all historical packets are preserved.

## Historical readiness disposition — preparation complete with explicit blockers

**READINESS_PREPARATION_COMPLETE_WITH_EXPLICIT_BLOCKERS**.
R0–R3 local work is complete; see the [terminal report](citadel-readiness-report.md),
[readiness matrix](citadel-readiness-matrix.md), [owner runbook](citadel-readiness-runbook.md)
and [changed-test map](citadel-readiness-changed-tests.md).

R0 traced actual producers/consumers and missing genuine public evidence.
R1 added read-only public witness export, public preflight, exact unsigned decision
preparation and public signature assembly. Unsupported live transport/credential
and unknown successor boundaries remain refusing; no authentic records were fabricated.
R2 rehearsed actual command/DI through distinct interview, drafting, mission
approval, child handoff, receiving acceptance and non-executing Step 1.
R3 passed 2,739 tests / 53,138 assertions on committed executable code, with zero
failures/errors/skips and four retained historical linked-worktree warnings.
Tested commit: `2313b69f8f06af5064df4a0e711834f8cf90d97d`;
tree: `da7dee9c6f467e005e123c33ffbec7f31daedcd2`.

Next: independent package review → owner public installation/custody/authority
exports and explicit B1 transport decision → separately authorized commissioning
only after the real prerequisites and supported adapter are established.
Default `UnavailableFormationTransport` refuses. No live interview, enrollment,
commissioning, real-key handling, installation change or mission execution occurred.
First owner action is the exact public evidence request in the runbook.

CF01/CF02 remain [accepted within reviewed scope](citadel-formation-correction-acceptance.md).
Prior reports, owner ceremonies, candidate branches and Delegate Steps 1–69 remain
preserved. Historical pending-era text below is not reopened by this campaign.

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
