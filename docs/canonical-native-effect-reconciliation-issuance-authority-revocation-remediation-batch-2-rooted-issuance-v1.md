# Canonical Native Effect Reconciliation Issuance Authority and Revocation-at-Use Remediation — Batch 2

`BATCH_2_COMPLETE_ROOTED_DECISION_CUSTODY_AND_ATOMIC_PUBLICATION`
`BATCH_3_NOT_YET_INTEGRATED`
`NO_PROVIDER_NO_CREDENTIAL_NO_EXTERNAL_IO`

Batch 2 implements the acyclic path defined in Batch 1 without changing the
existing public issuer or corridor boundary.

`NativeEffectReconciliationIssuanceDecisionService` resolves the exact effect
admission, committed native transition, current native authority/principal,
signed Operator Root act, callback start and sealed response. From those records
it deterministically publishes one exact decision and one separately stored,
single-purpose issuance authority. The consumed native transition remains source
evidence with `continuing_authority: false`; it is not reused as the new grant.

`NativeEffectReconciliationIssuanceAuthorityResolver` repeats the complete
present-tense Root/native/source resolution before delivering exact-object,
PID/incarnation-bound, non-cloneable and non-serializable process custody.

`NativeEffectReconciliationAuthorizedIssuanceService` holds one semantic target
lock, establishes one target-wide winner, consumes the exact issuance authority,
then publishes the predetermined reconciliation authority and v2 issuance
evidence. The evidence joins the decision, issuance authority, durable
consumption and issued authority. An interruption after consumption permits a
fresh exact retry to finish; a changed window, source, issuer or target conflicts
at the target-wide winner. No retry creates a second semantic authority.

The existing `NativeEffectReconciliationAuthorityIssuanceService::issue()` and
the corridor still expose the old unguarded path in this batch. Replacing that
boundary and adding claim-use currentness belong to Batch 3.

No provider, credential, callback reinvocation, transport, network or external
I/O edge is reachable from the Batch 2 path.
