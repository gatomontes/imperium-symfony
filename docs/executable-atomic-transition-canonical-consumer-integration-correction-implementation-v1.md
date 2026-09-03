# Canonical consumer correction implementation v1

## Authorization and boundaries

The operator instruction to complete the campaign authorizes Batches 1–4 after
Preparation Batch 0. Preparation-only language remains historical. The inventory
and reading ledger v1 remain the baseline, not assertions about later code.
No live rollout, credentials, provider invocation, authority consumption or retry
is authorized by this implementation record.

## Batch 1 — interpretation boundary

`NativeBindingReader::interpret` reads the original descriptor and native proof.
It distinguishes `BOUND_INACTIVE`, `COMMITTED_CURRENT`, `COMMITTED_NOT_CURRENT`,
`INCOMPLETE`, `CORRUPT` and `UNRELATED_OPERATION`. Every result is read-only,
denies provider effects and retry, and retains `UNKNOWN_REPLAY_PROHIBITED`.
The historical `read` API is retained for the native transition substrate.
Neither interpretation mutates the original `BOUND_INACTIVE` descriptor.

`forClaim` resolves a stored deterministic execution claim to exactly one stored
binding by instance, operation and source authorization id/digest. It retains
the separate transition root, execution id and message replay fingerprint;
neither identity replaces the other. Ambiguous/missing joins refuse. Directory
and claim snapshots detect changes during inspection. Hashes establish byte
consistency, never issuance competence or provenance.

`assertLegacy` requires the supplied descriptor to equal the stored sealed source
and requires independent native reconstruction to report absence. Pending state,
orphan retirement, corrupt or committed state cannot fall back to inactive.
The caller must retain all historical validation and serialize any subsequent
write against the native transition boundary. This is a read check, not a lock
or an execution grant. Batch 2 owns mandatory consumer reachability and races.

Focused PHPUnit: `CanonicalConsumerCorrectionBatch1Test` passed 13 tests / 59
assertions, including inherited native substrate regressions. It proves current,
expiry, pending, corrupt and unrelated interpretation, descriptor preservation,
and byte-for-byte no-write inspection in disposable state. Application proof and
terminal acceptance remain outstanding until Batches 3 and 4.

Full Batch 1 PHPUnit: **1979 tests, 45874 assertions**, passed (PHP 8.4.14 / PHPUnit 13.3.0).
