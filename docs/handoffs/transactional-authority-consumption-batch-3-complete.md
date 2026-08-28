# Transactional Authority Consumption Adoption Batch 3 complete

## Result

Batch 3 adds commit-boundary fault injection to the already-adopted
`OperationalCognitionInvocationClaimService` only. It migrates no additional consumer.

The new `OperationalCognitionInvocationClaimFaultInjector` observes exactly:

1. `PREPARED` before the immutable claim write;
2. `CONSUMPTION_COMMITTED` after the one physical write;
3. `RESULT_COMMITTED` after that same write; and
4. `COMPLETE` before the new claim is returned.

The latter three are deliberately collapsed around one durable record. The cognition-authority
consumption, lease consumption, transactional envelope, and lifecycle result are fields of the same
immutable claim; there is no separately writable consumption artifact and therefore no interval in
which only one authority or only the consumptions can survive.

The checkpoint data-provider proof establishes:

- `PREPARED` failure leaves zero claim artifacts and exact retry commits once;
- every later injected failure leaves exactly one complete immutable claim;
- retry and later replay return the exact same sealed result;
- both exact authorities remain ordered cognition authority then lease and both are consumed;
- the recovery checkpoint is `COMPLETE` with rollback and authority unconsumption prohibited;
- no credential is resolved, provider journal created, provider invoked, network access performed,
  or external I/O started; and
- the existing divergent-envelope test still refuses conflicting replay.

The existing multi-process claim/claim and claim/interruption tests remain the one-winner and
competing-path proof. No lock scope or order changed.

## Preserved boundaries

No authority schema, issuer, holder, competent consumer, scope, expiry, claim schema, replay input,
lock scope, lock order, interruption behavior, provider journal, credential resolution, network
access, or external I/O changed.

Revocation, propagation, telemetry, reassessment, containment, incident, Iron Gate, Lazaretto,
sortie, external-receipt, and credential-platform boundaries remain closed.

## Next separately bounded batch

Only Batch 4 may next be considered: migrate the structurally parallel governance cognition claim
while preserving its exact authority resolver, authority and lease schemas, authority→lease lock
order, claim/interruption competition, provider-journal boundary, and pre-I/O result.

Batch 4 is not authorized by this handoff; it requires an explicit continuation instruction.
