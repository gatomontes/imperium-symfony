# Provider Effect Principal and Binding Activation Resumption Batch 3 reconstruction

## Result

RESUMPTION_BATCH_3_READ_ONLY_AGGREGATE_RECONSTRUCTION_PROOF_COMPLETE

Batch 3 adds read-only aggregate reconstruction over already validated,
caller-supplied offline fixtures.

## Reconstruction proof

The reconstructor reads the immutable resolution admission before the immutable
activation input and validates each existing artifact before proceeding. It
classifies complete, absent, corrupt, expired, revoked and changed-evidence
chains exactly as:

- `READY_OFFLINE_ACTIVATION_INPUT`;
- `INCOMPLETE`;
- `CONFLICTED`; or
- `REFUSED`.

A complete chain returns exact references for the provenance production,
activation decision, principal attestation, provider-assurance admission,
execution boundary, resolution admission and activation input. It also retains
the exact activation target, unconsumed authority identity and shared
replay/contention root.

The result includes a deterministic proof digest over the classification, chain
and reasons. Exact replay returns the same proof. Invalid identifiers, expiry,
revocation, changed evidence and recursive secret exclusion fail closed.

A changed same-root contender remains visible as same-root contention while the
original exact winner remains reconstructable. Reconstruction never repairs or
replaces either fixture.

## Non-authority posture

Reconstruction is read-only. It creates no fixture, resolution admission,
activation input or activation winner; issues or consumes no authority; activates
no principal or provider binding; handles no credential or capability; invokes
no provider; and starts no external I/O or retry.

Iron Gate and Lazaretto remain closed. The provider binding remains
`BOUND_INACTIVE`. `UNKNOWN_REPLAY_PROHIBITED` remains binding.
