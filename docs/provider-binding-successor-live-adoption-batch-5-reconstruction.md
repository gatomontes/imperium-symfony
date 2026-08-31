# Provider Binding Successor Live Adoption Batch 5 reconstruction

## Result

BATCH_5_READ_ONLY_LIVE_ADOPTION_AGGREGATE_RECONSTRUCTION_COMPLETE

The pure reconstructor consumes caller-supplied boundary and proof evidence only.
It classifies the aggregate as `ABSENT`, `INCOMPLETE`, `CONFLICTED`,
`REFUSED` or `EXACT_LIVE_ADOPTION_WINNER`.

Absent evidence remains absent. Partial evidence remains incomplete.
Changed-root, changed-evidence and tampered proof remain conflicted.
Invalid boundary evidence is refused. An exact immutable winner reconstructs
deterministically and exact replay returns the same aggregate digest.

The reconstructor validates the boundary before accepting the proof. It creates,
repairs and replaces no evidence. It issues or consumes no authority and
performs no live transition.

## Closed perimeter

Batch 5 may not produce a decision.
Batch 5 may not issue or consume live authority.
Batch 5 may not admit live execution.
Batch 5 may not adopt a live successor or change live binding state.
Batch 5 may not handle or resolve a credential or capability.
Batch 5 may not invoke a provider.
Batch 5 may not perform external I/O.
Batch 5 may not start a provider effect.
Batch 5 may not authorize retry.
Batch 5 may not migrate a live command.
Batch 5 may not open Iron Gate or Lazaretto.

The provider binding remains BOUND_INACTIVE.
The v3 execution admission remains NOT_IMPLEMENTED.
UNKNOWN_REPLAY_PROHIBITED remains binding.
