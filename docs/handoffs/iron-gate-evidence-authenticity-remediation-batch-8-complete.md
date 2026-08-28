# Iron Gate Evidence Authenticity Remediation Batch 8 complete

## Result

Batch 8 adds the canonical deterministic-transition caller-authority consumer. It validates the
sealed authority, exact transition and target, issuance/expiry interval, active native principal,
instance, binding, generation and source digest before writing the existing canonical immutable
authority-consumption receipt.

The posture is `EXISTS_CANONICALLY` for the consumer primitive and `EXISTS_FRAGMENTED` for adoption
by the three outbound-email transition services. The primitive performs no external I/O and opens
no Iron Gate, Lazaretto, sortie, credential-platform, revocation, propagation, telemetry,
reassessment, containment or incident boundary.

## Recovery and lock order

The authority lock is acquired before the immutable consumption-directory lock. Consumption is
committed before the separately locked target transition. A crash in that gap is recoverable only
by replaying the exact authority/source/consumer tuple; conflicting replay fails closed. This is a
forward-recovery protocol, not a claim of cross-directory filesystem atomicity.

## Next separately bounded batch

Only Batch 9 may next be considered: adversarial proof for substitution, replay, expiry, principal
generation, idempotency collision, provenance, crash recovery, concurrency, tamper resistance,
threat-model limits and secret exclusion. Batch 9 is not authorized by this handoff.

