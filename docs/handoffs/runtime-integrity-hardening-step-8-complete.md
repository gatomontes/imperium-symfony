# Handoff: runtime-integrity hardening Step 8 complete

## Completed transition

Hardening Step 8 closes the provider-response-to-turn-persistence crash gap without replaying the provider.

## Durable sequence

1. `INVOCATION_IN_FLIGHT` exists before provider I/O.
2. The returned credential-free response is sealed in one immutable envelope bound to the exact invocation claim and digest.
3. The journal seals the same SHA-256 response identity.
4. The gateway validates the response contract.
5. The bounded cognition turn is persisted through `ImmutableRecordStore`.

## Enforced recovery behavior

- an in-flight journal without an envelope remains an unknown outcome requiring governed resolution;
- an envelope created before journal sealing proves response receipt and permits forward recovery without provider replay;
- a response-sealed journal requires the exact matching envelope;
- a different response cannot replace an existing claim envelope;
- credentials are excluded from the envelope; and
- bounded-turn exact replay returns the immutable winner while conflicting content fails stopped.

## Verification

Focused tests cover envelope durability, exact replay, conflicting response rejection, response/journal identity equality, credential exclusion, and crash classification on both sides of journal response sealing.

PHP is unavailable in the Codex environment. Operator-local PHPUnit confirmation remains required.

## Next bounded transition

Hardening Step 9 should add an explicit forward-recovery operation that consumes the sealed envelope, revalidates the cognition payload, and persists the missing turn without provider access. The operation must be separately authorized and idempotent.
