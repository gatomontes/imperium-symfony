# Transactional Authority Consumption Adoption Batch 5 complete

## Result

Batch 5 adopts the shared transaction and recovery contracts in exactly one additional consumer:
`ProviderInvocationClaimService`.

Each new Delegate pre-I/O claim remains `imperium.clavium-provider-invocation-claim/v1`, retains its
pre-existing deterministic claim ID and idempotency key, and now seals one
`imperium.runtime-transactional-authority-consumption/v1` envelope. The envelope binds:

- the complete sealed provider-invocation activation and its digest;
- the turn authority and credential lease as distinct, ordered consumable authorities;
- target, model binding/configuration, lease scope/expiry, and credential-reference digest;
- the complete `ReplayFingerprint` stored separately from the compatibility-preserved claim ID;
- the exact competent service and bounded pre-I/O act;
- both consumptions and the existing immutable claim result; and
- complete recovery with no rollback, authority unconsumption, provider reinvocation, or external
  effect.

The service still acquires exactly one physical lock:
`provider-invocation-claim:<sha256 activationId>`. Both logical lock-plan entries name that same
scope because it already protects both distinct authorities. No second acquisition, new scope, or
lock-order behavior was introduced.

Historical immutable claims without the Batch 5 envelope remain replayable under their original
fingerprint and are not rewritten. Exact replay of adopted claims validates the complete
fingerprint and envelope. Structurally divergent transaction metadata fails stopped.

A new two-process contention test proves that simultaneous claims converge on one exact immutable
transactional result. The existing provider-journal contention proof and crash demonstrations
continue to govern provider start, unknown outcomes, and sealed-response forward recovery.

## Preserved boundaries

Provider-journal creation, credential resolution, provider invocation, network access, response
envelopes, unknown-outcome disposition, and sealed-response recovery remain outside the
consumption transition and retain their existing behavior.

No authority schema, issuer, holder, competent consumer, scope, expiry, claim schema, claim
identity, physical lock scope, lock acquisition, or recovery authority changed.

Revocation, propagation, telemetry, reassessment, containment, incident, Iron Gate, Lazaretto,
sortie, external-receipt, and credential-platform boundaries remain closed.

## Next separately bounded batch

Only Batch 6 may next be considered: migrate the Delegate Senate engine cluster while preserving
every competent actor, authority type, jurisdiction, source identity, and immutable result.

Batch 6 is not authorized by this handoff; it requires an explicit continuation instruction.
