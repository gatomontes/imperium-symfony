# Provider Binding Successor Production Adoption Batch 2 refusal

## Result

BATCH_2_REFUSED_CYCLIC_DECISION_AUTHORITY_DIGEST_DEPENDENCY

Pure fail-closed validation and immutable fixture storage cannot proceed from
the Batch 1 contracts as written.

The production-decision contract requires
`successor_creation_authority` as an exact `id`, `digest`, `schema`
reference. The successor-creation-authority contract simultaneously requires
`source_decision` as an exact `id`, `digest`, `schema` reference.

Therefore the canonical production-decision digest requires the authority
digest before the decision can be sealed, while the canonical authority digest
requires the decision digest before the authority can be sealed. No finite
construction order exists. Placeholder digests, post-seal mutation or digest
iteration would violate immutable canonical evidence.

## Required correction

The competent production decision must bind an authority issuance target, not a
not-yet-existing authority record digest. The future authority record may then
bind the already sealed decision by exact reference and bind the issuance target
by value. This produces an acyclic order:

1. seal the decision with an authority issuance target;
2. issue and seal the single-use authority from that exact decision and target;
3. later atomically consume the authority while creating the successor.

Only Provider Binding Successor Production Adoption Batch 1A authority-empty cyclic-lineage correction contracts may next be considered.

## Closed perimeter

No validator or fixture store was created. No production decision or authority
was produced. No authority was issued or consumed. No successor was created and
no adoption was performed.

This refusal may not activate a principal or provider binding.
It may not handle or resolve a credential or capability.
It may not invoke a provider.
It may not perform external I/O.
It may not migrate a live command.
It may not open Iron Gate or Lazaretto.

The provider binding remains BOUND_INACTIVE.
UNKNOWN_REPLAY_PROHIBITED remains binding.
