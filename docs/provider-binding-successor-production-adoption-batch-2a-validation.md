# Provider Binding Successor Production Adoption Batch 2A validation

## Result

BATCH_2A_FAIL_CLOSED_V2_VALIDATORS_AND_IMMUTABLE_OFFLINE_FIXTURE_STORES_COMPLETE

Pure validators now accept only the corrected v2 production decision and
creation authority plus the unchanged v1 adoption target. Validation requires
the intact reconciled target, decision-input and completed-successor lineage,
exact competent actor, source decision authority, finite issuance target,
single-use unconsumed authority posture, operation/replay root and validity.

The defective v1 digest cycle refuses validation. Expiry, revocation, schema
substitution, reference drift, issuance-target drift, secret material and any
claim that the future v3 admission is implemented fail closed.

Three segregated immutable caller-supplied offline fixture stores exist for v2
decisions, v2 authorities and adoption targets. They have no producers and read
or write only the supplied offline evidence root. Exact replay converges.
Changed evidence for the same identity conflicts.

The stores do not issue or consume authority, create a lifecycle successor,
decide adoption, alter the v2 execution admission or implement v3 admission.
The provider binding remains BOUND_INACTIVE.
UNKNOWN_REPLAY_PROHIBITED remains binding.

Batch 2A may not activate a principal or provider binding.
Batch 2A may not issue or consume authority.
Batch 2A may not handle or resolve a credential or capability.
Batch 2A may not invoke a provider.
Batch 2A may not perform external I/O.
Batch 2A may not migrate a live command.
Batch 2A may not open Iron Gate or Lazaretto.
