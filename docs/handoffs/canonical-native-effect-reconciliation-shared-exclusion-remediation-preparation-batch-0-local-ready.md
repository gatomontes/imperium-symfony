# Canonical Native Effect Reconciliation Shared-Exclusion Remediation — Preparation Batch 0 local ready

`PREPARATION_BATCH_0_LOCAL_ENTRYPOINT_READY`
`LOCK_AND_INTERLEAVING_INVENTORY_ONLY`
`PRODUCTION_CORRECTION_NOT_AUTHORIZED`
`REMOTE_PUBLICATION_NOT_AUTHORIZED`
`BATCH_1_NOT_AUTHORIZED`
`BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED`

## Local synchronization

Run in PowerShell:

```powershell
git checkout main
git pull --ff-only origin main
git status --short
git rev-parse HEAD
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectReconciliationSharedExclusionRemediationCampaignReadyTest.php
php vendor/bin/phpunit tests/Imperium/Runtime/CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationCampaignReadyTest.php
git switch -c codex/reconciliation-shared-exclusion-preparation-batch-0-local
```

The worktree must be clean and the base must descend from the merge publishing
this entrypoint. If a guard fails, stop. Do not reuse the quarantined campaign
branch or any branch containing its implementation.

## New-chat prompt

```text
Continue Imperium from clean synchronized `main` after the merge preparing
Canonical Native Effect Reconciliation Shared-Exclusion Remediation.

Read completely:

- `docs/canonical-native-effect-reconciliation-shared-exclusion-post-publication-blackquill-review-v1.md`;
- `docs/next-campaign-canonical-native-effect-reconciliation-shared-exclusion-remediation.md`;
- `docs/handoffs/canonical-native-effect-reconciliation-shared-exclusion-remediation-preparation-batch-0-local-ready.md`;
- `docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-1-complete.md`;
- the complete original Preparation Batch 0 inventory, call graph, custody and
  revocation-race matrices, adversarial matrix and reading ledger;
- the complete Batch 1 issuance/currentness contracts and tests;
- `docs/delegate-mission-flow.md`, `docs/handoffs/README.md` and
  `todo/blackquill-todos.md`; and
- the complete current accepted sources and tests for `NativeState`,
  `AtomicTransition`, `ImmutableRecordStore`, `AuthorityConsumptionStore`,
  `NativeAuthority`, `NativePrincipal`, `NativeRootActs`, reconciliation
  authority issuance/resolution/claim derivation, the corridor and every real
  Root/principal/generation/lifecycle writer.

Begin Preparation Batch 0 only — shared-exclusion lock and interleaving
inventory.

The range
`afcaf025d097db0b9adddac25a9083a8be2322a0..7d3b8818717fe74fb822a195689d8b3c51030862`
is quarantined history. You may inspect its diff and review findings to
understand the failure, but do not cherry-pick, restore, copy or treat its
implementation, tests, audit or evidence as accepted proof.

Produce:

1. a complete lock-identity inventory naming every exact scope string,
   normalized lock path derivation, acquisition order, reentrancy/nesting rule,
   protected store and writer;
2. an end-to-end call graph from source currentness resolution through decision
   publication, issuance-capability resolution/consumption, reconciliation-
   authority publication, claim currentness/consumption and claim publication;
3. a mutation matrix for Operator Root, native-principal activation/revocation,
   source-generation advance and source lifecycle
   `SUSPEND`/`SUPERSEDE`/`REVOKE`/`EXPIRE`/`RETIRE`/migration;
4. deterministic authority-empty race harnesses with controlled checkpoints for:
   - DP01: preview/currentness passes, real native/source mutation commits,
     stale decision publication is attempted;
   - IU01: issuance at-use currentness passes, real native/source mutation
     commits, capability consumption/publication is attempted; and
   - CU01: claim at-use currentness passes, real native/source mutation commits,
     capability consumption/claim publication is attempted;
5. focused tests proving the current accepted base either reproduces each race
   or explicitly lacks the operational surface needed to exercise it;
6. a lock-order/deadlock matrix distinguishing shared exclusion from
   target-wide serialization and prohibiting unsafe nested acquisition;
7. a versioned evidence ledger containing exact local commands/results, base
   SHA, changed files and explicit limitations; and
8. a Preparation Batch 0 completion handoff with the smallest proposed repair
   sequence and five remaining stages.

Use real native-state mutation writers in the harness wherever the accepted
base exposes them. A source-code string assertion is inventory evidence only,
not concurrency proof. Sequential “revoke then use” tests do not prove the
interleaving. The harness must control the validation-to-mutation-to-use order
without credentials, providers, external I/O or live runtime records.

Classify every relevant edge as exactly one of:

- `SHARED_EXCLUSION_PROVED`
- `DISJOINT_LOCK_RACE_REPRODUCED`
- `ORDERING_HAZARD`
- `EXISTS_SEQUENTIAL_ONLY`
- `DEFERRED_BOUNDARY`

Do not modify production issuer, resolver, capability, state, consumption,
corridor, container or provider behavior. Do not implement the correction. Do
not create or consume a real decision, authority, capability, reconciliation
authority, claim, receipt or runtime record outside isolated test fixtures.

Do not access credentials or providers; perform network/external I/O, email,
mission or live trial; open Iron Gate or Lazaretto; repair historical Root
semantics; or restore Batch 7. Do not push any branch. Do not open or merge a
pull request. Do not modify remote `main`.

Run the focused Preparation Batch 0 tests and complete PHPUnit locally. Report
exact commands and results. Do not claim GitHub CI.

Batch 1 is not authorized. Stop at:

`PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_SHARED_EXCLUSION_RACES_CLASSIFIED`

No shorthand continuation language, green test, prior merge, quarantined
candidate, “clear,” “forward” or Latin motto extends this authority.

Return the local branch, exact base SHA, exact files, classifications,
counterexample traces, focused/full PHPUnit results, `git status --short` and
the completion marker. Do not push.
```

## Completion boundary

A successful Preparation Batch 0 proves the race topology and repair boundary
without correcting production behavior. It leaves five stages and stops before
any runtime implementation or remote publication.

*In imperium fidimus.*
