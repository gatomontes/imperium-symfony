# Protected mission authority — separately sequenced local audit

Audit began after committed Batch 4 `3cdc89b05e4faa6b353487755e409a58baac9bd3`.
Its full suite passed **2662 tests / 52496 assertions**, no skips, 7m05.242s.
Transcript: `var/protected-mission-evidence/batch-4-full.txt`.

## Audit corrections

- Production Windows startup now checks the actual Runtime SID, non-administrator token,
  administrator-owned fixed installation, code/state ACLs and reparse points before loading
  authoritative state. An installer is provided for human review; only its `-WhatIf` path ran.
  The real-account success branch and denied caller access remain unmeasured.
- Complete malformed journal headers refuse instead of being treated as interrupted tails.
  Truncation/seek errors refuse. Journal growth is capped at 64 MiB; state frames at 16 MB.
- Canonical authorization flags cannot be redigested into direct execution permission.
- Status reports currentness and next action separately from historical lifecycle/receipt.
- The contention test now always asserts the winning/losing outcome, making that branch's
  assertion count independent of scheduling. No refusal assertion was removed.

Focused audit: **16 tests / 221 assertions**, no skips, prior to the audit commit. An exact-commit
focused and full run follows this commit; terminal results and hashes are in the final evidence
ledger. No tests are claimed for later documentation commits unless explicitly recorded there.

## Source-bound audit scope

Read all new protected owner, trust, installation, ceremony, CLI, inspector and worker code;
canonical assembly/review/derivation and their tests; persistence and custody sources listed in
Batch 0; the preserved candidate's complete mission implementation and all campaign tests.
Also read the mission-planning references `contracts/la-cortine-boundary.md` and
`contracts/seneschal-suitability.md`, and existing inbound artifact/store shapes.
No internal cognition or external provider route was added. Raw Git content remains mechanical
test receipt evidence; it is not delivered to Curia as admitted cognitive evidence. Real Iron Gate,
Lazaretto, provider, credential and live-trial activation remain outside this campaign.

## Previous-test disposition, finalized

Every old method remains unchanged at `8df34679beab0ba8699a68fdd458570bf658c4c8`.
Historical **2661 / 52390** is preserved as reported local evidence, not rerun or relabeled.

| Old proof | Disposition | New proof |
|---|---|---|
| Batch0 entry inventory | INSUFFICIENT_PROOF | New Batch0 source/ref/threat inventory and exact-blob reproduction |
| Batch0 old mandatory real Operator gate | SUPERSEDED_WITH_REPLACEMENT | Test-only ceremony plus explicit no-real-trust/mission gates |
| Batch1 exact persisted lineage | PORT_WITH_JUSTIFICATION | Batch3 CLI round trip, actual canonical IDs and signature-bearing review |
| Batch1 fabricated actor/approval/plan | PORT_WITH_JUSTIFICATION | Batch1 identity/competence/bytes; Batch3 signature/path/plan refusals |
| Batch1 tamper/expiry/revoke/supersede | PORT_WITH_JUSTIFICATION | Batches2/4/5 currentness, real-clock use, rotation, amendment, redigested flags |
| Batch2 capability tuple | PORT_WITH_JUSTIFICATION | Owner-signed authorization/dossier/commission/mission/actor/target/state/expiry/nonce |
| Batch2 constructor reflection | INSUFFICIENT_PROOF | Actual unknown protocol/root/verifier/clock/key operations refuse; OS proof remains separate |
| Batch2 forged bindings | PORT_WITH_JUSTIFICATION | Batch2 altered tuple refuses and journal hash stays unchanged |
| Batch2 absent authorization/issuer | PORT_WITH_JUSTIFICATION | Batch1 absent bootstrap; Batch2/5 absent/inactive/forged authority leaves no issuer |
| Batch3 exact Git objects | PORT_WITH_JUSTIFICATION | Batch4 retains exact commit/tree/blob/bytes/SHA checks and adds whole-target hashes/bounds |
| Batch3 durability/restart | PORT_WITH_JUSTIFICATION | Batches2/4 replay, required state, fresh nonce terminal refusal and lost response |
| Batch3 independent contenders | PORT_WITH_JUSTIFICATION | Actual PHP processes; one winner; revoke/supersede order and restart |

No old dossier, synthetic trust/key, capability, approval, runtime record or timestamp was imported.
The original full transcript/private handoff was not located in the bounded searches documented
in Batch0. The controlling review and preserved Git artifacts are hashed historical evidence.
No missing artifact is invented. Old temporary directories remain untouched.

## Proof limits and release meaning

This is a local implementation-agent audit, not independent review, remote CI or deployment
acceptance. Same-user separate PHP processes establish protocol/state behavior only. Runtime,
deployment administrators, PHP/extension configuration and installed code are trusted. Arbitrary
code controlling the Runtime account can read its issuer secret and alter state; PHP visibility,
digests and filesystem modes are not represented as protection from that actor.

Production startup is Windows-only. Installation success is not exercised because real account/ACL
provisioning and real trust are prohibited. The runbook distinguishes tested PowerShell ceremony
commands from unexecuted human installation/account probes. No autonomous impersonation or service
launcher is supplied: a trusted Runtime operator runs the narrow stdio endpoint.

The offline reader is deliberately limited to local loose SHA-1 Git objects. It cannot read packs,
worktree indirection or symlink blobs. Local-volume placement is assumed. The worker cannot launch
Git config/hooks/lazy fetch; worker deadlines still depend on OS scheduling/termination. Journal
fsync is exercised, but hardware power loss is not. Partial-frame crash injection is a format-level
dying-process proof, not exhaustive instruction-level interruption coverage. Windows lacks pcntl;
existing platform-conditional assertions are not substitutes for other-platform proof.

At this point, the executable disposable ceremony and mission are demonstrated, while final full
audit remains pending. No real Operator, previous blocked mission, external provider, push, PR,
merge, main change or branch deletion is authorized by this document.
