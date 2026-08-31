# Provider Binding Successor Production Adoption Batch 4 reconstruction

## Result

BATCH_4_READ_ONLY_AGGREGATE_RECONSTRUCTION_COMPLETE

The aggregate reconstructor reads the corrected v2 production decision, v2
creation authority and unchanged adoption target under one replay/contention
root. It validates each artifact before reading the next and verifies the exact
decision-authority, reconciled target, decision input, completed successor,
ACTIVE principal, BOUND_INACTIVE descriptor, assurance and execution-boundary
lineage.

The result is one of:

- `ELIGIBLE_OFFLINE_PRODUCTION_ADOPTION_EVIDENCE`;
- `INCOMPLETE` when an immutable fixture is absent;
- `CONFLICTED` when stored evidence is corrupt or tampered;
- `REFUSED` when supplied lineage or lifecycle is ineligible.

Eligibility remains offline evidence. The required v3 execution admission is
explicitly `NOT_IMPLEMENTED`. Reconstruction persists, repairs, replaces or promotes nothing.
Exact read-only replay returns the same proof digest and leaves every source
byte unchanged.

No reconstruction result issues or consumes authority, creates a successor,
decides adoption, changes execution admission, activates a binding, handles a
credential or starts a provider effect.
The provider binding remains BOUND_INACTIVE.
UNKNOWN_REPLAY_PROHIBITED remains binding.

Batch 4 may not activate a principal or provider binding.
Batch 4 may not issue or consume authority.
Batch 4 may not handle or resolve a credential or capability.
Batch 4 may not invoke a provider.
Batch 4 may not perform external I/O.
Batch 4 may not migrate a live command.
Batch 4 may not open Iron Gate or Lazaretto.
