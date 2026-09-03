# Native Inspection Snapshot Consistency — Batch 3 complete

`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_BATCH_3_COMPLETE`

Separate PHP processes now prove transition plus migration publication,
revocation, expiry, interrupted journal state, repeated stable inspection,
two-attempt churn refusal and inspector termination. The proof is recorded in
`docs/native-inspection-snapshot-consistency-separate-process-proof-v1.md`.

Batch 4 must prove real container and CLI wiring, unchanged output projections,
no checkpoint binding, zero credential/provider/callback access, zero semantic
or lock-file mutation, and preservation of the already-locked authorizing
pre-effect refusal.

Two stages remain: Batch 4 integration/no-effect proof and Batch 5 separate
terminal Blackquill audit.

Local PHPUnit command:

`php vendor/bin/phpunit tests/Imperium/Runtime/NativeInspectionSnapshotConsistencyBatch3Test.php`
