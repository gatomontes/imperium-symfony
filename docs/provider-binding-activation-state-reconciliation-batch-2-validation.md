# Provider Binding Activation State Reconciliation Batch 2 validation

## Result

BATCH_2_FAIL_CLOSED_VALIDATORS_AND_IMMUTABLE_FIXTURE_STORES_COMPLETE

The three Batch 1 contracts now have pure fail-closed canonical validation and
segregated immutable caller-supplied offline fixture stores.

Validation requires exact field order, canonical SHA-256 digest integrity,
sealed exact references, one instance, exact ACTIVE-principal and
BOUND_INACTIVE-descriptor lineage, one operation scope, one replay/contention
root, effective unexpired validity and absent revocation.

Provider, operation, principal generation and binding substitution are refused.
Recursive credential, capability, authentication, token and environment-variable
material is excluded. Decision authority must be unconsumed and non-continuing;
a successor must show that exact authority consumed without continuing authority.

Immutable storage provides one identity winner. Exact replay converges and
changed evidence for the same identity conflicts. The three artifact kinds use
separate evidence paths and no fixture becomes live runtime state.

The validators and stores do not produce a decision or activation transition.
They do not activate or mutate the original binding. They do not issue or
consume runtime authority. They do not handle or resolve a credential or
capability. They do not invoke a provider or perform external I/O. They do not
start a provider effect, authorize retry, migrate a live command, or open Iron
Gate or Lazaretto.

The provider binding remains BOUND_INACTIVE.
UNKNOWN_REPLAY_PROHIBITED remains binding.
