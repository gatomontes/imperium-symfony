# Loco — native authority issuance and currentness protocol

## Purpose and boundary

Prepare and implement the next local campaign, **P0–P3: native authority issuance and currentness**. Start from main `031091dda39798429d2af79bca106ad2bfe983e8`, tree `986f4c12f7c7aa757ef17af58051730cf460ecb7`.

PR #773 integrated the accepted A0–A3 preparation/refusal implementation after GitHub CI passed. It established that the existing contracts lack a native issuer/currentness protocol. This campaign explicitly designs and implements that missing protocol, including a positive path using clearly synthetic, separately enrolled fixture trust. It must not claim that defining a protocol grants real institutional authority.

This file is the current local campaign instruction. The repository still records A0–A3 as its preceding campaign; update its current entry points and steps as part of this run while preserving the accepted historical records. No new GitHub campaign-preparation commit is asserted by this document.

Do not read installed private state or journals, enroll real trust, handle real private keys, install code, change actual occupants, perform institutional acts, invoke providers or execute missions. Do not reopen R0–R3/N0–N3/A0–A3 or repeat the exact Guildhall acceptance lookup. Real deployment, trust enrollment, factual owner attestations and institutional decisions require their own later explicit authorization.

## Pull source into a fresh worktree

Run in PowerShell. This fetches source and creates a separate checkout; it does not update the installed application.

```powershell
Set-Location E:\htdocs\imperium-citadel-authority
$priorHead = git rev-parse HEAD
if ($LASTEXITCODE -ne 0 -or $priorHead.Trim() -ne '7cee7f8c1b67154ae871ab1b2a9648d116ed48a1') { throw 'Preserve the changed source and report its identity.' }
$dirty = git status --porcelain --untracked-files=no
if ($LASTEXITCODE -ne 0 -or $dirty) { throw 'Preserve existing tracked changes.' }
git fetch origin main
if ($LASTEXITCODE -ne 0) { throw 'Fetch failed.' }
$entry = '031091dda39798429d2af79bca106ad2bfe983e8'
$remote = git rev-parse origin/main
if ($LASTEXITCODE -ne 0 -or $remote.Trim() -ne $entry) { throw 'Main differs from the reviewed entry; report it.' }
$protocolRoot = 'E:\htdocs\imperium-citadel-native-authority'
$protocolBranch = 'codex/citadel-native-authority-protocol'
if (Test-Path -LiteralPath $protocolRoot) { throw 'Destination exists; preserve it.' }
git show-ref --verify --quiet "refs/heads/$protocolBranch"
$lookupExit = $LASTEXITCODE
if ($lookupExit -eq 0) { throw 'Branch exists; preserve it.' }
if ($lookupExit -ne 1) { throw 'Branch lookup failed.' }
git worktree add -b $protocolBranch $protocolRoot $entry
if ($LASTEXITCODE -ne 0) { throw 'Worktree creation failed.' }
Set-Location $protocolRoot
git rev-parse HEAD
git rev-parse 'HEAD^{tree}'
git status --porcelain --untracked-files=no
```

Expected tree: `986f4c12f7c7aa757ef17af58051730cf460ecb7`. Fetch plus worktree creation is the pull/update procedure; no extra pull into the installed app is needed.

## Prompt — execute P0–P3 locally

Continue Imperium in the fresh native-authority worktree. Read applicable repository instructions, this entire file, and:

- `docs/citadel-authority-interface-decision.md`, `docs/citadel-authority-interface-source-map.md`, `docs/citadel-authority-interface-report.md`, `docs/citadel-authority-interface-runbook.md`, `docs/citadel-authority-interface-changed-tests.md`.
- `docs/next-campaign-citadel-recruiter-garrison-authority-interfaces.md`, `docs/citadel-native-lineage-acceptance.md`, the readiness runbook/matrix and current Delegate flow.
- `contracts/citadel-formation-runtime.md`, formation decisions/implementation and CF01/CF02 acceptance.
- The actual AuthorityInput, RecruiterEvidence, GarrisonAuthorityRequest, CitadelAuthorityCommand, StateStore/MasterMason/V0Activation, Garrison producer/consumer and formation signature/current/historical-witness source identified by those maps.
- Existing atomic transition, immutable record, authority-consumption and revocation/currentness primitives relevant to the new protocol. Mechanical reuse does not import their issuer competence.

### P0 — explicit native trust and issuer contract

Define a separate, deployment-owned native institutional trust domain. Reuse cryptographic and enrollment mechanics only where sound; do not inherit FormationSignatures' CITADEL_MISSION_FORMATION or ProtectedMission/planning competence. Incoming envelopes cannot enroll their own key. Even reuse of the same physical public key needs explicit domain/effect enrollment and independent fingerprint confirmation.

Specify an owner-controlled public trust enrollment prerequisite outside ordinary incoming-decision commands. Define exact, narrow effects for native roster/currentness acts and Garrison admission/custody authority revision. Bind instance, domain, enrolled fingerprint, issuer role/competence, validity, revocation, exact object digest and replay identity. Do not create a new Office or confer general operational authority through a convenience root.

Distinguish three claims in contracts and outputs: supplied historical evidence, an attributable owner/custodian attestation about that evidence, and an authorized prospective institutional act. An owner's signature does not prove that a historical producer executed or that an old qualification contains powers it never had. Preserve every provenance limitation.

Deliver a concrete owner policy/enrollment request with unknown identities null and exact proposed powers enumerated. Treat this as an unapproved real-installation proposal. The code must support its semantics and prove them in synthetic fixtures without requiring real keys or real enrollment now.

### P1 — authoritative roster revisions and currentness

Implement immutable, authenticated native roster acts and a current revision resolver with explicit genesis/adoption, supersession, retirement and revocation semantics as needed for the Recruiter/Garrison path. Initial adoption must bind exact retained evidence and the competent owner's explicit decision; it must never silently choose the latest T04 or largest generation. If historical provenance remains only attested, retain that qualification.

Bind each act to its exact predecessor/head digest, actor/Seat/instance, occupancy generation, effect and effective interval. Distinguish occupancy generation from authority and registry revisions. Reject gaps, competing heads, unrelated lineage, mismatched instance and ambiguous incumbency. A saved export may prove an observed revision; it cannot prove indefinite currentness.

Use a coherent resolver/transaction boundary shared by relevant currentness writers and consumers. An authenticated registry is not authoritative over unrelated writers unless they participate or are explicitly fenced. Define the supported cooperating-writer/deployment boundary and prove it. Keep existing private-state projections structural; do not relabel their self-computed digests as authenticated evidence.

### P2 — one legitimate Garrison authority revision and consumer path

Implement exact unsigned decision preparation, public signature assembly/verification and a dormant authenticated revision transition for the two existing narrow scopes: Persona-admission disposition and custody registration. Require separately enrolled fixture trust and a competent exact signed decision in every positive test. A boolean mock verifier or caller-selected public key is insufficient.

Validate and consume authority, compare current roster/authority revisions, check revocation and publish the immutable effect under one exclusion protocol. Exact replay returns the original effect; conflicting replay refuses. After interruption or expiry, recognize an authentic already completed effect without consuming new authority or repeating it. Unknown outcomes retain their fence.

Preserve original occupancy bytes and other independently existing powers. Introduce a distinct authority revision, not a second ACTIVE occupant. Close every direct admission/custody/inventory reader affected by the new effective representation; reject stale raw-record bypasses. Demonstrate the real command/DI and admission consumer positive path in disposable fixtures.

Keep formation-specific competence and institutional judgments separate. Garrison's capacity to render an admission is not itself a candidate admission. Recruiter incumbency is not formation delegation. Leave formation witness adapters refusing unless this campaign supplies and tests their entire required current and retained-publication provenance; do not weaken CF02's historical validation to accept a new format.

### P3 — decisive proof and owner commissioning package

Correct the accepted A0–A3 test qualification: its admission delivery omits `senate_confirmation_record_id` and `originating_guildhall_commission_id`, which independently cause GA87. Build a complete otherwise-valid synthetic delivery, establish the valid control, and vary each missing power and the occupant/request representation independently. Preserve the original report as history; do not claim the old test isolated these predicates.

Prove a positive enrolled-trust path and adverse wrong-domain/effect/key/instance/actor/generation/scope cases. Test modified evidence, forged/self-enrolled authority, expired/revoked decisions, stale roster/head/prior revision, duplicate initial adoption, competing revisions, exact/conflicting replay and absent currentness. Use separate processes for relevant currentness/revocation races and interruption points before/after durable consumption/effect. Demonstrate one effect and honest retained-evidence recovery, including expiry after completion.

Seed synthetic private sources, rejected inputs and errors with secret sentinels. Verify public output, failures, temporary files and proof artifacts exclude private material. Preserve public collector restrictions and default transport refusal. State filesystem, process, writer-cooperation and durability limitations precisely; no unperformed OS/network/power-loss guarantees.

Update current campaign pointers, steps and flow, contracts, source/consumer map, changed-test map, report and exact owner runbook. The owner package must identify actual implemented enrollment, preparation, detached-signature import, roster and revision operations, their prerequisites, footprint, expected exits and recovery. Label all real trust and institutional acts as future owner-only operations. Do not invent deployment commands or live identities.

Commit the final executable checkpoint, then run focused and complete offline PHP tests and relevant existing Python compatibility tests. Use existing offline dependencies; no package installation/update or real environment/credential access. Preserve 2,810 tests / 53,495 assertions and four historical warnings as the prior supplied baseline, not a target to force. Record failed runs, fixes, exact native exits, times, source identities and post-test changes. Never weaken inventory/tripwire predicates to obtain green results; classify new paths explicitly where required.

Return the complete review packet: report/design and unsigned owner policy request, source/consumer/test maps, exact runbook, positive/adverse/race/recovery proof, test stdout/JUnit/metadata, tested/final source archives and complete Git/blob/byte manifests, bounded history bundle with verified prerequisites, exact post-test diff, status/preservation evidence, whole-folder manifest and separate ZIP SHA-256. Distinguish synthetic proof from actual installation evidence and tests rerun from supplied history.

Success requires an implemented positive offline protocol with genuine cryptographic/effect checks under synthetic enrolled trust, plus refusing defaults outside that setup. An unresolved real owner identity is expected and must not prevent implementing the protocol. If a source-level or doctrinal contradiction prevents the proposed positive path, report that specific contradiction and the smallest required owner decision; do not declare another refusal-only campaign a completed positive protocol.

Stop at local commits for independent review. No future implementation publication/merge, real enrollment, private export, signing, installation change or institutional act is automatic. B1, other institutions, formation suitability/delegation, custody/trust and appointments remain separate commissioning requirements.

Settled flow stays: Citadel receives; Castellan interviews; understanding closes interview authority; separate drafting approval; separate mission approval; legitimate child-Curia constitution/handoff; receiving assessment grants no execution authority. Preserve CF01/CF02/IR01, all historical evidence and the separately authorized source-review capability. Unknown provider outcomes retain exposure without retry/refund; default transport refuses.

*Hoc est pretium solitudinis.*
