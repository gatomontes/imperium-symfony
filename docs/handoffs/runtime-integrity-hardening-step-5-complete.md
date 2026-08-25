# Handoff: runtime-integrity hardening Step 5 complete

## Completed transition

Hardening Step 5 migrates durable Delegate provider-invocation claiming onto the shared transactional persistence primitives.

Checkpoint remains: `INVOCATION_CLAIMED_PENDING_EXTERNAL_IO`

## Migration outcome

- the complete activation read, validation, replay scan, claim decision, and persistence sequence runs under `AtomicTransition`;
- claim persistence is delegated to `ImmutableRecordStore`;
- the claim service no longer owns a private lock file, directory creation, digest sealing, temporary-file commit, or immutable replay implementation;
- existing claim schema, deterministic identity, fingerprint, idempotency key, lease consumption, turn-authority consumption, and recovery flags remain unchanged; and
- exact replay and changed-lineage conflict behavior remain intact.

## Verification

Existing claim tests continue to cover the complete claim contract. They now also assert that the legacy provider-invocation lock is absent and the shared transition-lock infrastructure is used.

PHP is unavailable in the Codex environment. Operator-local PHPUnit confirmation remains required.

## Next bounded transition

Hardening Step 6 should migrate the provider-invocation journal onto `MutableStateStore` and `AtomicTransition`, preserving pre-I/O, in-flight, response-sealed, and unknown-outcome states.
