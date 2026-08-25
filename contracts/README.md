# Artifact Contracts

This directory defines machine-readable exchange contracts shared by Offices.
The contracts do not create cognitive authority. They make the identity,
lineage, state, and evidence required by doctrine structurally explicit.

- [`codex-imperii.md`](codex-imperii.md) defines the canonical digest-bound
  compilation of the Folia belonging to one Imperium instance.
- [`codex-imperii.schema.json`](codex-imperii.schema.json) validates its
  identity, checkpoint, ordered Folium references, and canonical digest.

- [`mission-planning.md`](mission-planning.md) converts Operator intent into
  separately disclosed and approved Planning Charter and Mission Plan phases;
  it defines Planning Authorization, Mission Authorization, their distinct
  commissions, amendment, and closure.
- [`seneschal-suitability.md`](seneschal-suitability.md) defines the
  multidimensional executive-disposition demand used to determine whether the
  standard Seneschal is sufficient for one mission instance.
- [`seneschal-suitability-demand.schema.json`](seneschal-suitability-demand.schema.json)
  validates that immutable demand.
- [`seneschal-succession.md`](seneschal-succession.md) defines mandate
  correction, mismatch evaluation, ordinary replacement, emergency suspension,
  succession-state preservation, and atomic Seat transfer.
- [`seneschal-succession-directive.schema.json`](seneschal-succession-directive.schema.json),
  [`seneschal-succession-packet.schema.json`](seneschal-succession-packet.schema.json),
  and [`seneschal-seat-transfer.schema.json`](seneschal-seat-transfer.schema.json)
  validate the corresponding succession artifacts.

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
