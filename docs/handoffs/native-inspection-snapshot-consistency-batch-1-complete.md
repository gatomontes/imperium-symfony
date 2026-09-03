# Native Inspection Snapshot Consistency — Batch 1 complete

`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_BATCH_1_COMPLETE`
`OPTIMISTIC_WHOLE_READ_SET_WITH_BOUNDED_REFUSAL_CONTRACT`

The canonical contract is
`docs/native-inspection-snapshot-consistency-contract-v1.md`. It fixes the
declared read set, two-attempt bound, nested observation behavior, conservative
error mapping, unchanged result projections and single-host boundary.

Batch 2 is authorized to implement one internal read-only manifest/scope
component and route `interpret`, `forClaim`, `forJournal`, `read` and direct
reconstruction through it. It may expose an unbound constructor-only checkpoint
for proof. It may not add a production lock, runtime write, provider or
credential access, classification change, retry/recovery authority, Iron Gate or
Lazaretto action.

Four stages remain: Batch 2 implementation, Batch 3 separate-process
adversarial proof, Batch 4 container/CLI no-effect proof, and Batch 5 separate
terminal Blackquill audit from clean merged Batch 4 `main`.

Local PHPUnit command:

`php vendor/bin/phpunit tests/Imperium/Runtime/NativeInspectionSnapshotConsistencyBatch1Test.php`
