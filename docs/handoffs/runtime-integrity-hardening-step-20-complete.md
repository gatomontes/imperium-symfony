# Runtime integrity hardening Step 20 complete

Step 20 establishes the transactional persistence foundation for consolidating lifecycle Steps 44–46.

## Delivered

- `CodexImperiiStore` initializes one canonical Codex per Imperium instance.
- Checkpoint advances append only contiguous, uniquely identified Folia through compare-and-swap persistence.
- Every lawful advance increments `generation` and changes the digest-bound Codex state.
- Exact initialization and advance replays return the existing state; the latest transition's complete input is fingerprint-bound so a partial batch cannot masquerade as replay.
- Omission, substitution, reordering, duplicate identity, stale checkpoint, instance mismatch, and conflicting replay fail stopped.
- The Codex schema and contract now specify monotonic generations.

## Boundary preserved

The store records ordered evidence and checkpoint state. It grants no authority and does not yet coordinate operational qualification, manifestation assembly, or seat binding. Those three service migrations must land as complete recoverable transitions rather than partial writes.

## Next

Introduce the Steps 44–46 transition coordinator, map each existing operational Folium into the Codex, add interruption recovery at every checkpoint, and then route the three public services through that coordinator without changing their governance contracts.
