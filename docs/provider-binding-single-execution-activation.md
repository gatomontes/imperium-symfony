# Provider Binding single-execution activation

## Status

`BATCH_3_SINGLE_EXECUTION_ACTIVATION_COMPLETE`

La Cortine now consumes one exact intact, exercisable and unexpired Batch 2 activation authority
under an authority-specific atomic lock. It revalidates the exact pre-I/O execution claim and exact
inactive provider binding, including tool authority, effect authorization, assurance profile,
destination policy, execution identity, operation and destination. It then seals one immutable
`imperium.la-cortine.single-execution-provider-binding-activation/v1` lease with status
`ACTIVATED_UNCONSUMED` and no continuing authority.

The activation identity is deterministic over the source authority and digest. Exact replay
converges; altered replay conflicts; contention has one winner. Expired authority, claim or binding,
tampered lineage, a non-pre-I/O claim or a non-inactive binding fails closed. The source provider
binding remains immutable and `BOUND_INACTIVE`: the activation is a separate execution-scoped
lease, not a mutation of provider selection.

## Preserved perimeter

This batch does not issue, reconstruct or take custody of a credential capability; implement
cross-process delivery or atomic execution admission; expose a credential reference, secret or
serialized capability; resolve credentials; invoke a provider; or perform external I/O. Iron Gate
and Lazaretto remain closed. Provider Execution Assurance remains paused.
