# Canonical Native Effect Continuation and Exclusivity Remediation — Batch 2 tuple winner and custody v1

`BATCH_2_ATOMIC_TUPLE_WINNER_AND_CONTINUATION_CUSTODY_COMPLETE_NO_CALLBACK`
`BATCH_3_NOT_AUTHORIZED`

The atomic admission now uses the authority-independent 64-hex semantic tuple
as its effect replay identity and admission id. Under `NativeState::locked()` it
acquires native and sorted source/trust locks, then authority, tuple and
immutable publication scopes. The v2 admission atomically records the tuple,
exact authority-consumption identity, complete receipt input and unknown replay
checkpoint.

Distinct authorities for one tuple converge on the same admission. The first
valid authority wins. A later authority receives an immutable
`TUPLE_ALREADY_WON_AUTHORITY_UNCONSUMED` disposition; its credential capability
is not consumed and no continuation is minted.

Only the call that newly publishes the admission receives a process-local
continuation object. The object is recognized solely by the same issuer
registry, is single-use, has no serialization/reconstruction API and is minted
after durable locks are released. Exact replay in the original service may
return the already-held object; a fresh service/process receives the durable
admission with no continuation. Process loss between publication and return
therefore leaves reconciliation authority only.

No callback service consumes the object in Batch 2. No provider double,
credential resolver, AgentMail/HTTP transport, network or external I/O is
invoked. Batch 3 is not authorized by this document.
