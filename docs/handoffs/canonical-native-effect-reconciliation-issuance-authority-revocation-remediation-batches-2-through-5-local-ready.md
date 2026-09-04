# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — Batches 2–5 local ready

`REMAINING_CAMPAIGN_LOCAL_ENTRYPOINT_READY`
`BATCHES_2_THROUGH_5_EXPLICITLY_AUTHORIZED_FOR_SEQUENTIAL_LOCAL_EXECUTION`
`SEPARATE_COMMIT_AND_TEST_GATE_PER_BATCH_REQUIRED`
`REMOTE_PUBLICATION_REQUIRES_SEPARATE_REVIEW`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

## Authority amendment

After restoration of the authorized Batch 1 tree through PR #752, the Operator
explicitly requested preparation of the rest of this campaign for local
execution. This entrypoint authorizes Batches 2, 3, 4 and 5 of this named
campaign, in that order and within the limits below.

This authority is not retroactive. Commits
`75b89cf9cf59b55d480cd883be221ff07a11ec44` through
`11c006cfdf454aa6204337e4568c78c09988895f` remain an unauthorized historical
attempt. Do not cherry-pick, restore or copy their implementation, tests,
handoffs, audit, claimed evidence or invented operator-continuation markers.
Re-derive the work independently from the authorized Batch 1 contracts and
current `main`.

## Local synchronization

Run in PowerShell:

```powershell
git checkout main
git pull --ff-only origin main
git status --short
git rev-parse HEAD
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationCampaignReadyTest.php
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch1Test.php
git switch -c codex/reconciliation-issuance-batches-2-through-5-local
```

The pulled worktree must be clean. Record the exact base SHA. If synchronization
or either guard fails, stop. Do not reuse an existing branch with unreviewed
changes.

## New-chat prompt

```text
Continue Imperium from clean synchronized `main` after the merge preparing the
remaining Canonical Native Effect Reconciliation Issuance Authority and
Revocation-at-Use Remediation campaign.

The Operator explicitly authorizes Batches 2, 3, 4 and 5 of this named campaign
for sequential local execution under this prompt. This is prospective authority
only. It does not ratify or admit the reverted historical attempt.

Read completely:

- `docs/next-campaign-canonical-native-effect-reconciliation-issuance-authority-revocation-remediation.md`;
- `docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batches-2-through-5-local-ready.md`;
- `docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-1-complete.md`;
- the complete Batch 1 contract specification, all seven constants-only
  contract definitions and their tests;
- the complete Preparation Batch 0 inventory, derivation-authority/currentness
  call graph, issuance-custody-consumption matrix, revocation-race matrix,
  adversarial proof matrix and reading/evidence ledger;
- `docs/canonical-native-effect-reconciliation-authority-provenance-post-merge-blackquill-review-v1.md`;
- the complete prior reconciliation-authority provenance campaign contracts,
  implementation documents, tests, evidence ledgers and handoffs;
- `docs/delegate-mission-flow.md`, `docs/handoffs/README.md` and
  `todo/blackquill-todos.md`; and
- the complete current implementation and tests for every issuer, resolver,
  claim, recovery, corridor, state, lock, consumption, reconstruction and
  application-wiring surface named by the preparation inventory.

Before editing, confirm:

1. the worktree is clean and the checked-out branch is not `main`;
2. the exact base SHA descends from the merge containing this entrypoint;
3. the Batch 1 guard and Batch 1 contract tests pass; and
4. none of the reverted commits
   `75b89cf9cf59b55d480cd883be221ff07a11ec44..11c006cfdf454aa6204337e4568c78c09988895f`
   has been cherry-picked, restored or copied.

Execute the following sequence. Each batch is a separate local commit and a
separate evidence boundary. Advance only when the current batch's focused tests
and the complete PHPUnit suite pass and the worktree is clean after commit. If a
gate fails, authority does not advance: stop and report the failure.

### Batch 2 — rooted issuance decision, custody and atomic publication

Implement the smallest acyclic Root/Imperator-provenanced reconciliation
issuance decision and separately provenanced single-purpose, single-use issuance
authority defined by Batch 1. Deliver exact process-local typed custody. Under
one documented semantic-target exclusion, consume the exact issuance authority
and publish deterministic reconciliation authority plus issuance evidence.
Prove exact retry convergence, changed-input conflict, durable consumption and
no second semantic winner.

Do not yet replace the existing public issuer/corridor signature or integrate
claim-use currentness; those are Batch 3. Add the Batch 2 specification, focused
tests, evidence update and completion handoff. Run focused and full PHPUnit,
record exact results, then commit locally with marker:

`BATCH_2_COMPLETE_ROOTED_DECISION_CUSTODY_AND_ATOMIC_PUBLICATION`

### Batch 3 — issuer enforcement and revocation at use

From the clean committed Batch 2 tree, require the typed issuance capability at
the public issuer boundary, remove every unguarded construction path, migrate
all repository callers and ensure the corridor shares the canonical issuance
resolver. Revalidate independently mutable Operator Root, native-principal,
source-generation and source-lifecycle currentness inside the same governed
exclusion as issuance consumption/publication. Repeat required currentness at
the claim-use cut immediately before exact consumption and claim publication.

Preserve RR02/RR05/RR11 as bounded expiry-preservation cases and distinguish
`SUSPEND`, `SUPERSEDE`, `REVOKE`, `EXPIRE`, `RETIRE` and v3
migration-required refusals. Add the Batch 3 specification, focused tests,
evidence update and completion handoff. Run focused and full PHPUnit, record
exact results, then commit locally with marker:

`BATCH_3_COMPLETE_TYPED_ISSUER_AND_AT_USE_CURRENTNESS`

### Batch 4 — adversarial, application, concurrency and interruption proof

From the clean committed Batch 3 tree, prove the complete bounded matrix:
missing, counterfeit, expired, replayed, substituted and consumed issuance
authority; use of source provenance or the consumed transition authority as a
grant; resolve-revoke-consume races across Root, native principal, generation
and lifecycle; competing issuers and claimants; interruption before and after
consumption/publication; changed-input retry conflict; fresh-process custody;
container/application wiring; read-only reconstruction; and explicit absence of
credential, provider, transport, environment-secret, HTTP/network or other
external-I/O reachability.

Do not widen a claim merely to make a test pass. Document cooperative
single-host, multi-host and hostile-direct-writer boundaries separately. Add the
Batch 4 specification, focused/adversarial tests, evidence update and completion
handoff. Run focused and full PHPUnit, record exact results, then commit locally
with marker:

`BATCH_4_COMPLETE_ADVERSARIAL_APPLICATION_AND_INTERRUPTION_PROOF`

### Batch 5 — separately sequenced terminal Blackquill audit

Begin only after Batch 4 has a distinct local commit, its focused and full suites
are green, and the worktree is clean. Treat the committed Batch 4 tree as an
untrusted candidate. Independently reconstruct the decision, issuance
authority, typed custody, at-use currentness, consumption, publication, claim,
recovery and reconstruction chain. Pressure-test decision-publication
currentness, lock scope/order, bypasses, replay, revocation, interruption,
contention, evidence provenance and claim boundaries.

If the audit finds a defect, record the finding before correction, make the
smallest correction in a separate local commit, rerun focused and full PHPUnit,
and audit the corrected exact SHA again. Do not convert a correction into proof
by assertion.

Produce a versioned terminal audit and evidence ledger containing only actual
local results and exact local SHAs. Do not claim GitHub Actions, remote review,
merge, clean remote `main` or formal campaign acceptance. End with:

`LOCAL_RECONCILIATION_ISSUANCE_CAMPAIGN_CANDIDATE_COMPLETE_PENDING_REMOTE_REVIEW`

## Global exclusions and stop law

Throughout Batches 2–5:

- do not access credentials or providers;
- do not perform network/external I/O, email, mission or live trial;
- do not open Iron Gate or Lazaretto;
- do not repair the untimestamped Operator Root history limitation;
- do not restore or authorize Batch 7;
- do not push any branch, open or merge a pull request, or modify remote
  `main`;
- do not represent local tests as GitHub CI;
- do not reuse evidence or implementation from the reverted unauthorized range;
  and
- do not invent authority from “continue,” “clear,” “forward,” a green test, a
  prior commit/merge, this campaign's momentum or a Latin motto.

Stop immediately on ambiguity, unexpected pre-existing changes, a failed gate,
an unreviewed scope expansion or any need for credential/provider/external
access.

Return:

- the branch name and exact base SHA;
- one ordered commit SHA per batch and every corrective commit;
- exact files and invariants per batch;
- focused and complete PHPUnit commands/results per batch;
- the terminal audit verdict and unresolved limitations;
- `git status --short`; and
- the final local marker.

Do not push. The Operator will submit the exact local candidate for independent
review and separately authorize any remote publication.
```

## Completion boundary

This entrypoint authorizes local construction and proof of Batches 2–5 only.
Success produces a local candidate pending independent review. It grants no
authority to push, merge, claim GitHub CI, activate provider effects, repair
Root-history semantics or begin Batch 7.

*In imperium fidimus.*
