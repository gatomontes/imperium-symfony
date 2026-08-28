# Transactional Authority Consumption Adoption Batch 4 complete

## Result

Batch 4 adopts the shared transaction and recovery contracts in exactly one additional consumer:
`GovernanceCognitionInvocationClaimService`.

Each new governance pre-I/O claim remains
`imperium.clavium-governance-cognition-invocation-claim/v1`, retains its pre-existing deterministic
claim ID, and now seals one `imperium.runtime-transactional-authority-consumption/v1` envelope. The
envelope binds:

- the exact normalized governance authority reread through the cluster-specific registry resolver;
- the unchanged governance cognition request, Imperator resource decision, and Clavium lease;
- provider/model configuration, resource ceiling, target, input digest, and every source digest;
- the complete `ReplayFingerprint` stored separately from the compatibility-preserved claim ID;
- the governance authority first and lease second as distinct consumable authorities;
- `gca-authority:<sha256 authorityId>` first and `gca-lease:<sha256 leaseId>` second;
- both consumptions and the existing immutable pre-I/O claim result; and
- complete recovery with no rollback, authority unconsumption, provider reinvocation, or external
  effect.

Historical immutable claims without the Batch 4 envelope remain replayable and are not rewritten.
Exact replay of adopted claims validates the complete fingerprint and envelope. Structurally
divergent transaction metadata fails stopped.

A new two-process contention test proves that simultaneous governance claims converge on one exact
immutable transactional result. Existing governance-lease interruption enforcement continues to
take the same lease scope and the admission guard continues to reject a claim after interruption.

No provider journal is created inside the transition. Credential resolution, provider invocation,
network access, and external I/O remain later boundaries.

## Preserved boundaries

No authority schema, resolver ownership, issuer, holder, competent consumer, scope, expiry, claim
schema, claim identity, lock scope, lock order, interruption behavior, provider-journal behavior,
credential resolution, network access, or external I/O changed.

Revocation, propagation, telemetry, reassessment, containment, incident, Iron Gate, Lazaretto,
sortie, external-receipt, and credential-platform boundaries remain closed.

## Next separately bounded batch

Only Batch 5 may next be considered: migrate the Delegate provider invocation claim while
preserving its turn authority, credential lease, provider-journal, response-envelope,
unknown-outcome, and sealed-response recovery semantics. Provider I/O must remain outside the
consumption transition.

Batch 5 is not authorized by this handoff; it requires an explicit continuation instruction.
