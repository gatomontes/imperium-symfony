# Canonical native-effect reconciliation authority provenance remediation — Batch 2

Status: `BATCH_2_COMPLETE_ROOT_PROVENANCED_ISSUANCE_AND_ATOMIC_CUSTODY`

Batch 2 implements the smallest acyclic no-provider path defined in Batch 1.
`NativeEffectReconciliationAuthoritySourceResolver` begins with a canonical
effect-admission identifier, loads the immutable admission, resolves its committed
native transition, loads that transition's native authority, reconstructs the
active native Imperator principal and thereby re-verifies its signed Operator Root
act. It then resolves the exact callback-start and sealed response lineage.

The issuer accepts no authority array. It deterministically publishes a v2
authority plus separate immutable issuance evidence. An orphaned authority from
an interruption before issuance publication is not resolvable and grants no
custody. Repeated exact issuance converges; conflicting bytes fail immutable.

The resolver accepts only an authority identifier and time. It re-resolves the
complete source chain and exact issuance evidence before returning an exact-object,
process-incarnation-bound, non-cloneable and non-serializable capability. A
freshly digested record with changed issuer prose fails because the immutable
issuance reference no longer matches. Root revocation, native-principal expiry,
generation drift and lineage substitution fail in the existing native loaders.

`NativeEffectReconciliationAuthorityClaimDerivationService` consumes that typed
custody inside the authority lock and publishes one deterministic v2 claim. The
claim itself contains the sealed authority-consumption evidence; publication is
the durable consumption event, so there is no separately published consumption
file that can be stranded before the claim. Process death before publication
leaves no durable consumption and a fresh process may resolve again. Process
death after publication leaves the deterministic claim and fresh resolution
refuses the consumed authority.

The existing v1 `admit(array $authority, int $at)` boundary is deliberately not
changed in Batch 2. Corridor integration and removal of that bypass belong to
Batch 3. No credential resolver, provider callback, network transport or external
I/O is reachable from any Batch 2 production service.

