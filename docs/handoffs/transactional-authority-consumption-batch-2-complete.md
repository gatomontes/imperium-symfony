# Transactional Authority Consumption Adoption Batch 2 complete

## Result

Batch 2 adopts the Batch 1 contracts in exactly one consumer:
`OperationalCognitionInvocationClaimService`.

Each new operational pre-I/O claim remains
`imperium.clavium-operational-cognition-invocation-claim/v1` and now seals one embedded
`imperium.runtime-transactional-authority-consumption/v1` envelope. The envelope binds:

- the unchanged Curia cognition authority and Clavium lease as separate ordered authorities;
- their exact source identity/digest, issuer, holder, scope, expiry, and expected unconsumed state;
- the complete existing `ReplayFingerprint` inputs;
- the exact runtime service and one pre-I/O claim act;
- `oca-cognition-authority:<sha256 authorityId>` first and
  `oca-lease:<sha256 leaseId>` second;
- both consumptions and the existing immutable claim as the result; and
- complete pre-I/O recovery with no automatic retry, rollback, authority unconsumption, provider
  reinvocation, or external effect.

Exact replay of an adopted claim validates the complete embedded envelope. Structurally divergent
contract metadata fails stopped. Historical immutable claims without the Batch 2 envelope remain
readable and replayable; they are not rewritten or falsely reclassified as adopted.

The existing claim/claim and claim/interruption multi-process proofs remain the concurrency gate.
The opposing-race proof now also verifies that a winning claim carries the two exact ordered
authorities.

No authority schema, issuer, holder, competent consumer, scope, expiry, lock scope, lock order,
provider journal, credential resolution, network access, external I/O, or interruption behavior
changed.

## Next separately bounded batch

Only Batch 3 may next be considered: add commit-boundary fault injection and prove one immutable
result, exact replay equivalence, conflicting replay refusal, and deterministic recovery for the
adopted operational claim without moving provider I/O into the transition.

Batch 3 is not authorized by this handoff; it requires an explicit continuation instruction.

All revocation, propagation, telemetry, reassessment, containment, incident, Iron Gate, Lazaretto,
sortie, external-receipt, provider-journal expansion, and credential-platform boundaries remain
closed.
