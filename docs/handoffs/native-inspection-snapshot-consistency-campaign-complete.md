# Native Inspection Snapshot Consistency campaign complete

`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_CAMPAIGN_COMPLETE`
`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_TERMINAL_AUDIT_ACCEPTED`

The campaign is complete. Its terminal Blackquill audit is
`docs/native-inspection-snapshot-consistency-terminal-audit-v1.md`.

Delivered sequence:

1. Preparation Batch 0 inventoried callers, reads, publications, locks and races.
2. Batch 1 fixed the optimistic coherent-snapshot contract and unchanged public
   projections.
3. Batch 2 implemented one nesting-aware, two-attempt, read-only manifest
   boundary.
4. Batch 3 proved publication, migration, revocation, expiry, interruption,
   repetition, churn and inspector termination with sibling PHP processes.
5. Batch 4 proved real container/CLI wiring and zero credential/provider/callback
   effect.
6. Batch 5 independently pressure-tested the committed Batch 4 boundary and
   accepted it with explicit residual limits.

No further stage remains. No live state, provider, credential, capability,
external I/O, Iron Gate or Lazaretto action was authorized or performed.
Inspection remains non-authorizing and may become stale after return.

Verification completed at every gate:

- Preparation Batch 0: 5 tests / 499 assertions;
- Batch 1: 5 tests / 53 assertions;
- Batch 2: 16 tests / 68 assertions, plus 36 tests / 256 assertions of native and
  canonical-consumer regression;
- Batch 3: 17 tests / 72 assertions;
- Batch 4: 24 tests / 203 assertions;
- Batch 5 terminal gate: 6 tests / 105 assertions;
- affected predecessor/successor documentary gates: 15 tests / 743 assertions;
- final repository-wide run: 2,092 tests / 47,277 assertions.

The first full run found two stale documentary hashes and no runtime failure.
Their predecessor values remain pinned and their reviewed successor values are
now required by the terminal reading ledger.

Focused terminal PHPUnit command:

`php vendor/bin/phpunit tests/Imperium/Runtime/NativeInspectionSnapshotConsistencyBatch5TerminalAuditTest.php`

Repository-wide PHPUnit command (the repository has no default XML file):

`php vendor/bin/phpunit tests`
