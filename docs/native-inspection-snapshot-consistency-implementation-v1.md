# Native Inspection Snapshot Consistency implementation v1

`NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_BATCH_2_COMPLETE`

Batch 2 implements the Batch 1 contract in
`NativeInspectionSnapshot`. The component recursively manifests all declared
claim, issuance, effect-journal, native, source, trust and legacy bases without
writing or locking. Missing bases and directories are represented, `.lock`
files are excluded, and every other regular file is hashed. Aliases, unsupported
entries and capture failures refuse conservatively.

`NativeBindingReader::interpret()`, `forClaim()`, `forJournal()` and `read()`,
plus direct `NativeReconstructor::reconstruct()`, now enter the shared boundary.
A process-local state-identity scope makes their nested call graph one outer
attempt. The scope is always cleared in `finally`.

There are exactly two possible attempts. A changed manifest discards the entire
derivation. Continued instability maps to the pre-existing `INCOMPLETE` /
`UNKNOWN_REPLAY_PROHIBITED` outcomes appropriate to each public projection.
Stable pre-existing validation errors and all public result key orders remain
unchanged.

The optional constructor checkpoint is unbound proof instrumentation. Production
code supplies none. The implementation contains no `AtomicTransition`, `flock`,
write, repair, credential, provider, admission, Iron Gate or Lazaretto action.

`BOUND_INACTIVE`, historical v3 `NOT_IMPLEMENTED`, the bounded pre-effect
consumer acceptance and every false effect/retry/authority field remain intact.
