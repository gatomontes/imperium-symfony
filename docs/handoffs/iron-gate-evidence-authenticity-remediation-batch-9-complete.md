# Iron Gate Evidence Authenticity Remediation Batch 9 complete

## Result

Batch 9 enforces runtime-principal caller authority at all three deterministic outbound-email
transitions. Request creation requires a Seneschal authority bound to the exact canonical input
intent. Decision and issuance require separate Imperator authorities bound to the sealed request
and sealed decision respectively. Each transition validates the active source principal and writes
an immutable single-use consumption receipt before its target commit.

The prior development-root decision attribution is removed. Decision ownership and issued-
authorization identity are derived from the consumed native Imperator principal authority. Exact
same-consumer recovery remains permitted after the documented consumption-before-target crash gap;
changed consumer, source, transition or target fails closed.

No provider callback, live command, transport, external I/O, Iron Gate, Lazaretto, sortie,
credential-platform, revocation, propagation, telemetry, reassessment, containment or incident
boundary is opened.

## Next separately bounded batch

Only Batch 10 may next be considered: adversarial proof for substitution, replay, expiry, principal
generation, idempotency collision, provenance, crash recovery, concurrency, tamper resistance,
threat-model limits and secret exclusion. Batch 10 is not authorized by this handoff.

