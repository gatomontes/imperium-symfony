# Transactional Authority Consumption Adoption Batch 1 complete

## Result

Batch 1 defines separately versioned canonical contracts for the transactional authority-
consumption envelope and its recovery semantics:

- `TransactionalAuthorityConsumptionContract`;
- `AuthorityConsumptionRecoveryContract`; and
- `docs/transactional-authority-consumption-contract.md`.

The contracts bind one-or-many unchanged lifecycle authorities, complete replay inputs, exact
competent consumer, ordered existing lock scopes, immutable result, commit checkpoint, retry,
rollback, and unknown-outcome semantics. They grant no authority and perform no transition.

No consumer was migrated. No authority schema, issuer, holder, consumer, scope, expiry, lock scope,
lock order, replay behavior, provider journal, or external-I/O boundary changed.

## Next separately bounded batch

Only Batch 2 may next be considered: adopt the contracts in the operational cognition lease +
cognition-authority claim while preserving
`oca-cognition-authority:<sha256 authorityId>` → `oca-lease:<sha256 leaseId>` and the current
claim/interruption competition.

Batch 2 is not authorized by this handoff; it requires an explicit continuation instruction.

All revocation, propagation, telemetry, reassessment, containment, incident, Iron Gate, Lazaretto,
sortie, external-receipt, provider-journal expansion, and credential-platform boundaries remain
closed.
