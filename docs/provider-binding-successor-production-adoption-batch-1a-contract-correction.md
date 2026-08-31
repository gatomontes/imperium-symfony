# Provider Binding Successor Production Adoption Batch 1A contract correction

## Result

BATCH_1A_AUTHORITY_EMPTY_ACYCLIC_DECISION_AUTHORITY_CONTRACTS_COMPLETE

The refused v1 decision and creation-authority contracts remain immutable
historical evidence of the cyclic digest defect. They are superseded for future
validation by separately versioned v2 contracts; they are not rewritten,
promoted or made usable.

The v2 production decision binds a value-shaped
`successor_creation_authority_issuance_target`. That target names the future
authority identity and schema, exact successor target, permitted transition,
replay/contention root, single-use posture and absence of continuing authority.
It contains no not-yet-existing authority-record digest.

The v2 creation authority then binds the already sealed v2 production decision
by exact immutable reference and reproduces the decision's issuance target
exactly. The seal order is finite and acyclic:

1. seal the v2 production decision and issuance target;
2. seal the v2 authority from the decision and exact target;
3. later, under separate authorization, atomically consume the authority and
   create the successor.

The v1 adoption-target contract remains authority-empty and unchanged because
it participates in no digest cycle.

There is no producer, validator, fixture, store, authority issuer, authority
consumer, successor creator, reconstructor, adoption decision or live consumer.
The provider binding remains BOUND_INACTIVE.
UNKNOWN_REPLAY_PROHIBITED remains binding.

Batch 1A may not activate a principal or provider binding.
Batch 1A may not issue or consume authority.
Batch 1A may not handle or resolve a credential or capability.
Batch 1A may not invoke a provider.
Batch 1A may not perform external I/O.
Batch 1A may not migrate a live command.
Batch 1A may not open Iron Gate or Lazaretto.
