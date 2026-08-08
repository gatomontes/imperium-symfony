# Artifact Contracts

This directory defines machine-readable exchange contracts shared by Offices.
The contracts do not create cognitive authority. They make the identity,
lineage, state, and evidence required by doctrine structurally explicit.

- [`profile-artifact.md`](profile-artifact.md) defines the constitutional
  Profile artifact and lifecycle.
- [`profile-artifact.schema.json`](profile-artifact.schema.json) validates the
  immutable Profile envelope. Lifecycle attestations are separate records that
  bind the envelope's `profile_id`, `profile_version`, and `content_digest`.
- [`profile-attestation.schema.json`](profile-attestation.schema.json)
  validates those append-only lifecycle records.
- [`bootstrap-manifest.md`](bootstrap-manifest.md) defines the single pinned
  primordial composition required for launch.
- [`bootstrap-state-machine.md`](bootstrap-state-machine.md) and
  [`bootstrap-forward-recovery.md`](bootstrap-forward-recovery.md) govern
  initial bootstrap and its forward-only recovery.
- [`runtime-concurrency-replay.md`](runtime-concurrency-replay.md) defines the
  reservation, commission, idempotency, generation-pinning, and replay
  primitives shared by bootstrap and ordinary spawning without merging their
  state machines.
- [`recruiter-disaster-recovery.md`](recruiter-disaster-recovery.md) defines
  the exceptional, recovery-only path that creates a fresh narrow Recruiter
  to qualify one ordinary successor after confirmed incumbent loss.
